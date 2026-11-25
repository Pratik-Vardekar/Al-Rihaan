<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesCsv;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    use HandlesCsv;

    /**
     * Build the query for listing and exporting brands.
     */
    protected function buildBrandQuery(Request $request)
    {
        $query = Brand::withCount('products');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('slug', 'LIKE', '%' . $search . '%')
                  ->orWhere('description', 'LIKE', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }

        return $query;
    }

    /**
     * Display a listing of the brands.
     */
    public function index(Request $request)
    {
        $brands = $this->buildBrandQuery($request)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.brand.index', compact('brands'));
    }

    /**
     * Show the form for creating a new brand.
     */
    public function create()
    {
        return view('admin.brand.create');
    }

    /**
     * Store a newly created brand in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:brands,slug',
            'description' => 'nullable|string|max:1000',
            'logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'banner'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link'        => 'nullable|url|max:255',
            'status'      => 'required|in:0,1',
        ], [
            'slug.unique' => 'Brand slug already exists.',
        ]);

        $logoName = null;
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoName = 'brand_' . time() . '_' . Str::random(6) . '.' . $logoFile->getClientOriginalExtension();
            $this->saveBrandImage($logoFile, $logoName);
        }

        $bannerName = null;
        if ($request->hasFile('banner')) {
            $bannerFile = $request->file('banner');
            $bannerName = 'brand_banner_' . time() . '_' . Str::random(6) . '.' . $bannerFile->getClientOriginalExtension();
            $this->saveBrandImage($bannerFile, $bannerName);
        }

        $slug = $request->filled('slug')
            ? Str::slug($request->slug)
            : Brand::generateUniqueSlug($request->name);

        $nextPosition = ((int) Brand::max('position')) + 1;

        Brand::create([
            'name'        => $request->name,
            'slug'        => $slug,
            'description' => $request->description,
            'logo'        => $logoName,
            'banner'      => $bannerName,
            'link'        => $request->link,
            'status'      => (int) $request->status,
            'position'    => $nextPosition,
        ]);

        return redirect()
            ->route('brands.index')
            ->with('success', 'Brand created successfully.');
    }

    /**
     * Display the specified brand.
     */
    public function show(Brand $brand)
    {
        return redirect()->route('brands.edit', $brand->id);
    }

    /**
     * Show the form for editing the specified brand.
     */
    public function edit(Brand $brand)
    {
        return view('admin.brand.edit', compact('brand'));
    }

    /**
     * Update the specified brand in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'nullable|string|max:255|unique:brands,slug,' . $brand->id,
            'description'   => 'nullable|string|max:1000',
            'logo'          => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'banner'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link'          => 'nullable|url|max:255',
            'status'        => 'required|in:0,1',
            'remove_banner' => 'nullable|boolean',
        ], [
            'slug.unique' => 'Brand slug already exists.',
        ]);

        $data = [
            'name'        => $request->name,
            'description' => $request->description,
            'link'        => $request->link,
            'status'      => (int) $request->status,
        ];

        if ($request->filled('slug')) {
            $data['slug'] = Str::slug($request->slug);
        } elseif (empty($brand->slug)) {
            $data['slug'] = Brand::generateUniqueSlug($request->name, $brand->id);
        }

        if ($request->hasFile('logo')) {
            $this->deleteBrandImage($brand->logo);
            $logoFile = $request->file('logo');
            $logoName = 'brand_' . time() . '_' . Str::random(6) . '.' . $logoFile->getClientOriginalExtension();
            $this->saveBrandImage($logoFile, $logoName);
            $data['logo'] = $logoName;
        }

        if ($request->boolean('remove_banner')) {
            $this->deleteBrandImage($brand->banner);
            $data['banner'] = null;
        } elseif ($request->hasFile('banner')) {
            $this->deleteBrandImage($brand->banner);
            $bannerFile = $request->file('banner');
            $bannerName = 'brand_banner_' . time() . '_' . Str::random(6) . '.' . $bannerFile->getClientOriginalExtension();
            $this->saveBrandImage($bannerFile, $bannerName);
            $data['banner'] = $bannerName;
        }

        $brand->update($data);

        return redirect()
            ->route('brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    /**
     * Remove the specified brand from storage.
     */
    public function destroy(Brand $brand)
    {
        $this->deleteBrandImage($brand->logo);
        $this->deleteBrandImage($brand->banner);

        $brand->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Brand deleted successfully.',
            ]);
        }

        return redirect()
            ->route('brands.index')
            ->with('success', 'Brand deleted successfully.');
    }

    /**
     * Reorder brands via drag-and-drop AJAX request.
     */
    public function reorder(Request $request)
    {
        if ($request->has('orders') && is_array($request->orders)) {
            foreach ($request->orders as $item) {
                if (isset($item['id'], $item['position'])) {
                    Brand::where('id', $item['id'])->update(['position' => (int) $item['position']]);
                }
            }
        } elseif ($request->has('order') && is_array($request->order)) {
            foreach ($request->order as $index => $id) {
                Brand::where('id', $id)->update(['position' => $index + 1]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Brand order updated successfully.',
        ]);
    }

    /**
     * Export brands to CSV.
     */
    public function export(Request $request)
    {
        $brands = $this->buildBrandQuery($request)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $fileName = 'brands_' . now()->format('Y-m-d_His') . '.csv';
        $headers  = ['ID', 'Name', 'Slug', 'Link', 'Status', 'Position', 'Products Count', 'Created At'];

        return $this->streamCsvDownload($fileName, $headers, function ($file) use ($brands) {
            foreach ($brands as $brand) {
                fputcsv($file, [
                    $brand->id,
                    $brand->name,
                    $brand->slug,
                    $brand->link ?? '',
                    $brand->status ? 'Active' : 'Inactive',
                    $brand->position ?? 0,
                    $brand->products_count ?? $brand->products()->count(),
                    $brand->created_at ? $brand->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }
        });
    }

    /**
     * Helper to save brand image to public and base paths.
     */
    protected function saveBrandImage($file, string $fileName): void
    {
        $publicDir = public_path('backend/assets/images/brands');
        $baseDir   = base_path('backend/assets/images/brands');

        if (! file_exists($publicDir)) {
            @mkdir($publicDir, 0755, true);
        }
        if (! file_exists($baseDir)) {
            @mkdir($baseDir, 0755, true);
        }

        $file->move($publicDir, $fileName);

        $publicFilePath = $publicDir . DIRECTORY_SEPARATOR . $fileName;
        $baseFilePath   = $baseDir . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($publicFilePath) && ! file_exists($baseFilePath)) {
            @copy($publicFilePath, $baseFilePath);
        }
    }

    /**
     * Helper to delete brand image from disk.
     */
    protected function deleteBrandImage(?string $fileName): void
    {
        if (empty($fileName)) {
            return;
        }

        $clean = ltrim(str_replace('\\', '/', $fileName), '/');
        $relative = str_starts_with($clean, 'backend/') ? $clean : 'backend/assets/images/brands/' . $clean;

        $publicFile = public_path($relative);
        $baseFile   = base_path($relative);

        if (file_exists($publicFile) && is_file($publicFile)) {
            @unlink($publicFile);
        }
        if (file_exists($baseFile) && is_file($baseFile)) {
            @unlink($baseFile);
        }
    }
}

