<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with('user', 'product');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('review', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('rating')) {
            $rating = $request->rating;
            if ($rating == '5') {
                $query->where('rating', 5);
            } else {
                $query->where('rating', '>=', $rating);
            }
        }

        if ($request->filled('status') && in_array($request->status, ['0', '1'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $query->latest();

        // Auto-sync review image files between public_path and base_path
        $publicReviewDir = public_path('backend/assets/images/review');
        $baseReviewDir   = base_path('backend/assets/images/review');

        if (!file_exists($publicReviewDir)) {
            @mkdir($publicReviewDir, 0755, true);
        }
        if (!file_exists($baseReviewDir)) {
            @mkdir($baseReviewDir, 0755, true);
        }

        if (is_dir($publicReviewDir) && is_dir($baseReviewDir)) {
            foreach (glob($publicReviewDir . '/*.*') ?: [] as $file) {
                $target = $baseReviewDir . DIRECTORY_SEPARATOR . basename($file);
                if (!file_exists($target)) {
                    @copy($file, $target);
                }
            }
            foreach (glob($baseReviewDir . '/*.*') ?: [] as $file) {
                $target = $publicReviewDir . DIRECTORY_SEPARATOR . basename($file);
                if (!file_exists($target)) {
                    @copy($file, $target);
                }
            }
        }

        $reviews = $query->paginate(10)->withQueryString();
        $products = Product::orderBy('name')->get();

        return view('admin.review.index', compact('reviews', 'products'));
    }

    public function create()
    {
        $products = Product::where('status', 1)->orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'avatar']);

        return view('admin.review.create', compact('products', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'review' => 'required|string|max:5000',
            'status' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $title = trim((string) ($validated['title'] ?? ''));
        $body = trim($validated['review']);
        $reviewText = $title !== '' ? $title . "\n\n" . $body : $body;

        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = $this->saveReviewImage($request->file('image'));
        }

        Review::create([
            'user_id' => $validated['user_id'],
            'product_id' => $validated['product_id'],
            'rating' => (int) $validated['rating'],
            'review' => $reviewText,
            'status' => (int) $validated['status'],
            'image' => $imageName,
        ]);

        return redirect()
            ->route('admin.review.index')
            ->with('success', 'Review added successfully');
    }

    public function show(Review $review)
    {
        return redirect()->route('admin.review.index');
    }

    public function edit(Review $review)
    {
        $review->load(['user', 'product']);
        $products = Product::orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'avatar']);

        return view('admin.review.edit', compact('review', 'products', 'users'));
    }

    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'review' => 'required|string|max:5000',
            'status' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $title = trim((string) ($validated['title'] ?? ''));
        $body = trim($validated['review']);
        $reviewText = $title !== '' ? $title . "\n\n" . $body : $body;

        $data = [
            'user_id' => $validated['user_id'],
            'product_id' => $validated['product_id'],
            'rating' => (int) $validated['rating'],
            'review' => $reviewText,
            'status' => (int) $validated['status'],
        ];

        if ($request->hasFile('image')) {
            $this->deleteReviewImage($review->image);
            $data['image'] = $this->saveReviewImage($request->file('image'));
        }

        $review->update($data);

        return redirect()
            ->route('admin.review.index')
            ->with('success', 'Review updated successfully');
    }

    public function status(Request $request, Review $review)
    {
        $status = $request->has('status')
            ? $request->status
            : ($review->status == 1 ? 0 : 1);

        $review->update([
            'status' => $status,
        ]);

        $message = $review->status == 1
            ? 'Review approved successfully'
            : 'Review marked as pending';

        return back()->with('success', $message);
    }

    public function destroy(Review $review)
    {
        $this->deleteReviewImage($review->image);
        $review->deleteMediaFiles();
        $review->delete();

        return back()->with('success', 'Review deleted successfully');
    }

    public function export(Request $request)
    {
        $query = Review::with('user', 'product');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('review', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('rating')) {
            $rating = $request->rating;
            if ($rating == '5') {
                $query->where('rating', 5);
            } else {
                $query->where('rating', '>=', $rating);
            }
        }

        if ($request->filled('status') && in_array($request->status, ['0', '1'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $reviews = $query->latest()->get();

        $filename = 'reviews_export_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'w');

        fputcsv($handle, [
            'ID',
            'User Name',
            'User Email',
            'Product Name',
            'Rating',
            'Review',
            'Has Image',
            'Status',
            'Created Date',
            'Created Time',
        ]);

        foreach ($reviews as $review) {
            fputcsv($handle, [
                $review->id,
                $review->user->name ?? 'N/A',
                $review->user->email ?? 'N/A',
                $review->product->name ?? 'N/A',
                $review->rating . ' ★',
                $review->review,
                $review->image ? 'Yes' : 'No',
                $review->status == 1 ? 'Approved' : 'Pending',
                $review->created_at->format('Y-m-d'),
                $review->created_at->format('H:i:s'),
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:reviews,id',
            'action' => 'required|in:approve,pending,delete',
        ]);

        $count = 0;

        switch ($request->action) {
            case 'approve':
                $count = Review::whereIn('id', $request->ids)->update(['status' => 1]);
                $message = $count . ' review(s) approved successfully';
                break;
            case 'pending':
                $count = Review::whereIn('id', $request->ids)->update(['status' => 0]);
                $message = $count . ' review(s) marked as pending';
                break;
            case 'delete':
                $reviews = Review::whereIn('id', $request->ids)->get();
                foreach ($reviews as $review) {
                    $this->deleteReviewImage($review->image);
                    $review->deleteMediaFiles();
                }
                $count = Review::whereIn('id', $request->ids)->delete();
                $message = $count . ' review(s) deleted successfully';
                break;
        }

        return back()->with('success', $message);
    }

    /**
     * Helper to save review image to both public_path and base_path.
     */
    protected function saveReviewImage($file): string
    {
        $imageName = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

        $publicDir = public_path('backend/assets/images/review');
        $baseDir   = base_path('backend/assets/images/review');

        if (!file_exists($publicDir)) {
            @mkdir($publicDir, 0755, true);
        }
        if (!file_exists($baseDir)) {
            @mkdir($baseDir, 0755, true);
        }

        $file->move($publicDir, $imageName);

        $publicFile = $publicDir . DIRECTORY_SEPARATOR . $imageName;
        $baseFile   = $baseDir . DIRECTORY_SEPARATOR . $imageName;

        if (file_exists($publicFile) && !file_exists($baseFile)) {
            @copy($publicFile, $baseFile);
        }

        return $imageName;
    }

    /**
     * Helper to delete review image from disk.
     */
    protected function deleteReviewImage(?string $imageName): void
    {
        if (empty($imageName)) {
            return;
        }

        $clean = ltrim(str_replace('\\', '/', $imageName), '/');
        $filename = basename($clean);

        $publicFile = public_path('backend/assets/images/review/' . $filename);
        $baseFile   = base_path('backend/assets/images/review/' . $filename);

        if (file_exists($publicFile) && is_file($publicFile)) {
            @unlink($publicFile);
        }
        if (file_exists($baseFile) && is_file($baseFile)) {
            @unlink($baseFile);
        }
    }
}
