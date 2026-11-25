<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'review',
        'status',
        'image',
        'attachments',
        'like_count',
        'dislike_count',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    protected static function booted(): void    
    {
        static::saving(function (Review $review) {
            $images = self::decodeMediaFieldStatic($review->image);
            $videos = self::decodeMediaFieldStatic($review->video);

            if (empty($review->attachments) && (!empty($images) || !empty($videos))) {
                $review->attachments = self::encodeAttachments($images, $videos);
            }

            // Always ensure the primary image filename is retained in $review->image
            if (!empty($images)) {
                $review->setAttribute('image', $images[0]);
            }
        });
    }

    protected $appends = [
        'image_urls',
        'video_urls',
        'attachment_urls',
        'review_image_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reactions()
    {
        return $this->hasMany(ReviewReaction::class);
    }

    public function getStoredAttachments(): array
    {
        $items = [];

        if (!empty($this->attachments)) {
            $decoded = is_array($this->attachments)
                ? $this->attachments
                : json_decode($this->attachments, true);

            if (is_array($decoded) && !empty($decoded)) {
                $items = $decoded;
            }
        }

        if (empty($items)) {
            foreach ($this->decodeMediaField($this->image) as $file) {
                $items[] = ['type' => 'image', 'file' => $file];
            }

            foreach ($this->decodeMediaField($this->video) as $file) {
                $items[] = ['type' => 'video', 'file' => $file];
            }
        }

        return self::dedupeAttachmentItems($items);
    }

    public function getImageFilenames(): array
    {
        return array_values(array_map(
            fn ($item) => $item['file'],
            array_filter($this->getStoredAttachments(), fn ($item) => ($item['type'] ?? '') === 'image')
        ));
    }

    public function getVideoFilenames(): array
    {
        return array_values(array_map(
            fn ($item) => $item['file'],
            array_filter($this->getStoredAttachments(), fn ($item) => ($item['type'] ?? '') === 'video')
        ));
    }

    public static function resolveReviewMediaUrl(?string $file, string $type = 'image'): ?string
    {
        if (empty($file)) {
            return null;
        }

        $file = trim((string) $file);
        if ($file === '') {
            return null;
        }

        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
            return $file;
        }

        $clean = ltrim(str_replace('\\', '/', $file), '/');
        $folder = $type === 'video' ? 'videos' : 'images';
        $relative = str_starts_with($clean, 'backend/')
            ? $clean
            : 'backend/assets/' . $folder . '/review/' . $clean;

        return asset($relative);
    }

    public function getImageUrlsAttribute(): array
    {
        $urls = [];
        foreach ($this->getImageFilenames() as $file) {
            $url = self::resolveReviewMediaUrl($file, 'image');
            if ($url) {
                $urls[] = $url;
            }
        }

        if (empty($urls) && !empty($this->image)) {
            $url = self::resolveReviewMediaUrl($this->image, 'image');
            if ($url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function getVideoUrlsAttribute(): array
    {
        return array_values(array_filter(array_map(
            fn ($file) => self::resolveReviewMediaUrl($file, 'video'),
            $this->getVideoFilenames()
        )));
    }

    public function getReviewImageUrlAttribute(): ?string
    {
        return $this->image_urls[0] ?? self::resolveReviewMediaUrl($this->image, 'image');
    }

    public function getAttachmentUrlsAttribute(): array
    {
        return array_map(function ($item) {
            $type = $item['type'] ?? 'image';
            $file = $item['file'] ?? '';

            return [
                'type' => $type,
                'file' => $file,
                'url' => self::resolveReviewMediaUrl($file, $type),
            ];
        }, $this->getStoredAttachments());
    }

    public static function encodeFromAttachmentItems(array $items, array $existingItems = []): ?array
    {
        $payload = self::dedupeAttachmentItems(array_merge($existingItems, $items));

        return !empty($payload) ? $payload : null;
    }

    public static function dedupeAttachmentItems(array $items): array
    {
        $unique = [];
        $seen = [];

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['file'])) {
                continue;
            }

            $type = $item['type'] ?? 'image';
            $file = (string) $item['file'];
            $key = $type . ':' . $file;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = ['type' => $type, 'file' => $file];
        }

        return array_values($unique);
    }

    public static function encodeAttachments(array $imageFilenames, array $videoFilenames): ?array
    {
        return self::encodeFromAttachmentItems(
            self::buildAttachmentsPayload($imageFilenames, $videoFilenames)
        );
    }

    public function setAttachmentsFromUploads(array $imageFilenames, array $videoFilenames): void
    {
        $this->attachments = self::encodeAttachments($imageFilenames, $videoFilenames);
    }

    public function removeAttachmentsByFilenames(array $filenames): void
    {
        $filenames = self::normalizeAttachmentReferences($filenames);

        foreach ($filenames as $filename) {
            $this->deleteAttachmentFile($filename);
        }

        $remaining = array_values(array_filter(
            $this->getStoredAttachments(),
            fn ($item) => !in_array($item['file'] ?? '', $filenames, true)
        ));

        $this->attachments = self::encodeFromAttachmentItems($remaining);
    }

    public function appendAttachmentsFromUploads(array $imageFilenames, array $videoFilenames): void
    {
        $this->attachments = self::encodeFromAttachmentItems(
            self::buildAttachmentsPayload($imageFilenames, $videoFilenames),
            $this->getStoredAttachments()
        );
    }

    public function keepOnlyAttachments(array $filenames): void
    {
        $filenames = self::normalizeAttachmentReferences($filenames);
        $keepLookup = array_fill_keys($filenames, true);

        $toRemove = [];

        foreach ($this->getStoredAttachments() as $item) {
            $file = $item['file'] ?? '';

            if ($file !== '' && !isset($keepLookup[$file])) {
                $toRemove[] = $file;
            }
        }

        if (!empty($toRemove)) {
            $this->removeAttachmentsByFilenames($toRemove);
        }
    }

    public function clearAttachments(): void
    {
        foreach ($this->getStoredAttachments() as $item) {
            $file = $item['file'] ?? '';

            if ($file !== '') {
                $this->deleteAttachmentFile($file);
            }
        }

        $this->attachments = null;
    }

    public static function normalizeAttachmentReferences($values): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            $values = is_array($decoded) ? $decoded : [$values];
        }

        if (!is_array($values)) {
            return [];
        }

        $normalized = [];

        foreach ($values as $value) {
            if (is_array($value)) {
                if (isset($value['file']) || isset($value['url'])) {
                    $normalized = array_merge(
                        $normalized,
                        self::normalizeAttachmentReferences($value['file'] ?? $value['url'])
                    );
                    continue;
                }

                if (isset($value['type'])) {
                    continue;
                }
            }

            if (!is_string($value) && !is_numeric($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $path = parse_url($value, PHP_URL_PATH);
            $filename = basename($path ?: $value);
            $filename = rawurldecode($filename);

            if ($filename === '' || $filename === '.' || $filename === '..') {
                continue;
            }

            $normalized[] = $filename;
        }

        return array_values(array_unique($normalized));
    }

    public static function normalizeAttachmentRemovalReferences($values, array $currentAttachments): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            $values = is_array($decoded) ? $decoded : [$values];
        }

        if (!is_array($values)) {
            return [];
        }

        $normalized = self::normalizeAttachmentReferences($values);
        $indexMapped = [];

        foreach ($values as $value) {
            if (!is_numeric($value)) {
                continue;
            }

            $index = (int) $value;

            if (isset($currentAttachments[$index]['file'])) {
                $indexMapped[] = $currentAttachments[$index]['file'];
            }
        }

        return array_values(array_unique(array_merge($normalized, $indexMapped)));
    }

    public static function buildAttachmentsPayload(array $imageFilenames, array $videoFilenames): array
    {
        $attachments = [];

        foreach ($imageFilenames as $file) {
            $attachments[] = ['type' => 'image', 'file' => $file];
        }

        foreach ($videoFilenames as $file) {
            $attachments[] = ['type' => 'video', 'file' => $file];
        }

        return $attachments;
    }

    public function deleteMediaFiles(): void
    {
        $allFiles = [];
        foreach ($this->getStoredAttachments() as $item) {
            $type = $item['type'] ?? 'image';
            $file = $item['file'] ?? '';
            if ($file !== '') {
                $allFiles[] = ['type' => $type, 'file' => $file];
            }
        }

        if (!empty($this->image)) {
            $allFiles[] = ['type' => 'image', 'file' => $this->image];
        }

        foreach ($allFiles as $item) {
            $type = $item['type'] ?? 'image';
            $file = basename($item['file']);
            $folder = $type === 'video' ? 'videos' : 'images';

            $publicPath = public_path('backend/assets/' . $folder . '/review/' . $file);
            $basePath   = base_path('backend/assets/' . $folder . '/review/' . $file);

            if (file_exists($publicPath) && is_file($publicPath)) {
                @unlink($publicPath);
            }
            if (file_exists($basePath) && is_file($basePath)) {
                @unlink($basePath);
            }
        }
    }

    public function deleteAttachmentFile(string $filename): void
    {
        $filename = basename($filename);
        foreach ($this->getStoredAttachments() as $item) {
            if (basename($item['file'] ?? '') !== $filename) {
                continue;
            }

            $type = $item['type'] ?? 'image';
            $folder = $type === 'video' ? 'videos' : 'images';
            $publicPath = public_path('backend/assets/' . $folder . '/review/' . $filename);
            $basePath   = base_path('backend/assets/' . $folder . '/review/' . $filename);

            if (file_exists($publicPath) && is_file($publicPath)) {
                @unlink($publicPath);
            }
            if (file_exists($basePath) && is_file($basePath)) {
                @unlink($basePath);
            }

            break;
        }
    }

    private function decodeMediaField($value): array
    {
        return self::decodeMediaFieldStatic($value);
    }

    private static function decodeMediaFieldStatic($value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            return array_values(array_filter($decoded));
        }

        return [$value];
    }
}
