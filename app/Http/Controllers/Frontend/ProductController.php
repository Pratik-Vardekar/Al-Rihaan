<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\HandlesProductListing;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Feature;
use App\Models\HomeBanner;
use App\Models\Product;
use App\Models\Review;
use App\Models\ShippingSetting;
use App\Services\CartService;
use App\Services\ShiprocketService;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use HandlesProductListing;

    public function __construct(
        protected CartService $cartService,
        protected WishlistService $wishlistService
    ) {}

    /**
     * Show category products directly on the product listing page.
     */
    public function categoryShow(Request $request, $slug)
    {
        $category = Category::where('status', 1)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                    ->orWhere('id', $slug);
            })
            ->firstOrFail();

        return $this->buildListingPage(
            request: $request,
            filterAction: route('product.listing'),
            clearFilterUrl: route('product.listing'),
            overrideCategoryId: (int) $category->id,
            categoryModel: $category
        );
    }

    /**
     * Categories catalog.
     */
    public function categories()
    {
        $categories = Category::where('status', 1)
            ->withCount(['products' => fn($q) => $q->where('status', 1)])
            ->with(['subcategories' => fn($q) => $q->where('status', 1)->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('frontend.pages.categories', compact('categories'));
    }

    /**
     * Product Details Page.
     */
    public function productDetails(Request $request, $slug = null)
    {
        $productSlug = $slug ?: $request->route('slug') ?: $request->query('slug') ?: $request->slug;

        try {
            $product = Product::where('status', 1)
                ->with([
                    'faqs',
                    'variants' => fn($q) => $q->where('status', 1)->orderBy('price'),
                    'variants.variantAttributes.attribute',
                    'variants.variantAttributes.attributeValue',
                ])
                ->when($productSlug, fn($q) => $q->where('slug', $productSlug))
                ->when($request->filled('id') && ! $productSlug, fn($q) => $q->where('id', $request->id))
                ->when(! $productSlug && ! $request->filled('id'), fn($q) => $q->whereRaw('1 = 0'))
                ->first();
        } catch (\Throwable $e) {
            Log::warning('Product details full query failed, falling back to basic query: ' . $e->getMessage());
            $product = Product::where('status', 1)
                ->when($productSlug, fn($q) => $q->where('slug', $productSlug))
                ->when($request->filled('id') && ! $productSlug, fn($q) => $q->where('id', $request->id))
                ->when(! $productSlug && ! $request->filled('id'), fn($q) => $q->whereRaw('1 = 0'))
                ->first();
        }

        if (! $product) {
            abort(404);
        }

        try {
            $this->attachProductReviewMeta(collect([$product]));
        } catch (\Throwable $e) {
            Log::warning('attachProductReviewMeta failed: ' . $e->getMessage());
        }

        $related_products = collect();

        try {
            if ($product->brand_id) {
                $related_products = Product::query()
                    ->where('status', 1)
                    ->where('id', '!=', $product->id)
                    ->where('brand_id', $product->brand_id)
                    ->with(['brand', 'category'])
                    ->latest()
                    ->take(12)
                    ->get();
            }

            if ($related_products->isEmpty() && $product->category_id) {
                $related_products = Product::query()
                    ->where('status', 1)
                    ->where('id', '!=', $product->id)
                    ->where('category_id', $product->category_id)
                    ->with(['brand', 'category'])
                    ->latest()
                    ->take(12)
                    ->get();
            }

            if ($related_products->isEmpty()) {
                $related_products = Product::query()
                    ->where('status', 1)
                    ->where('id', '!=', $product->id)
                    ->with(['brand', 'category'])
                    ->latest()
                    ->take(12)
                    ->get();
            }

            $this->attachProductReviewMeta($related_products);
        } catch (\Throwable $e) {
            Log::warning('Related products query failed: ' . $e->getMessage());
            $related_products = collect();
        }

        try {
            $wishlistIds = $this->wishlistService->getProductIds();
        } catch (\Throwable $e) {
            $wishlistIds = [];
        }

        try {
            $features = Feature::where('status', 1)->latest()->take(6)->get();
        } catch (\Throwable $e) {
            $features = collect();
        }

        $sliderImages = $product->getSliderImages();

        try {
            $reviews = Review::where('product_id', $product->id)
                ->where('status', 1)
                ->with('user')
                ->latest()
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Reviews query failed for product ' . $product->id . ': ' . $e->getMessage());
            $reviews = collect();
        }

        $reviewCount = $reviews->count();
        $averageRatingValue = $reviewCount > 0
            ? round((float) $reviews->avg('rating'), 1)
            : 0.0;

        $ratingBreakdown = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $count = $reviews->where('rating', $star)->count();
            $ratingBreakdown[$star] = [
                'count' => $count,
                'percent' => $reviewCount > 0 ? (int) round(($count / $reviewCount) * 100) : 0,
            ];
        }

        $userHasReviewed = false;
        if (Auth::check()) {
            try {
                $userHasReviewed = Review::where('product_id', $product->id)->where('user_id', Auth::id())->exists();
            } catch (\Throwable $e) {
                $userHasReviewed = false;
            }
        }

        try {
            $cartProductIds = $this->cartService->getCartProductIds();
        } catch (\Throwable $e) {
            $cartProductIds = [];
        }

        $product->setAttribute('reviews_count', $reviewCount);
        $product->setAttribute('rating', $averageRatingValue);

        return view('frontend.pages.product_details', compact(
            'product',
            'related_products',
            'wishlistIds',
            'cartProductIds',
            'features',
            'sliderImages',
            'reviews',
            'reviewCount',
            'averageRatingValue',
            'ratingBreakdown',
            'userHasReviewed'
        ));
    }

    /**
     * Check pincode delivery serviceability.
     */
    public function checkDelivery(Request $request)
    {
        $validated = $request->validate([
            'pincode' => ['required', 'digits:6'],
        ]);

        $pincode = $validated['pincode'];
        $shippingSetting = ShippingSetting::first();
        $shippingCharge = (float) ($shippingSetting->shipping_charge ?? 0);
        $freeShippingAmount = (float) ($shippingSetting->free_shipping_amount ?? 0);

        $estimatedDays = '2-5 working days';
        $serviceability = app(ShiprocketService::class)->checkServiceability($pincode);

        if ($serviceability !== null && ! $serviceability['serviceable']) {
            return response()->json([
                'success' => false,
                'pincode' => $pincode,
                'serviceable' => false,
                'message' => 'Sorry, we do not deliver to this pincode yet.',
            ]);
        }

        if (! empty($serviceability['estimated_days'])) {
            $days = (int) $serviceability['estimated_days'];
            $estimatedDays = $days . '-' . ($days + 2) . ' working days';
        }

        return response()->json([
            'success' => true,
            'pincode' => $pincode,
            'serviceable' => true,
            'estimated_days' => $estimatedDays,
            'shipping_charge' => $shippingCharge,
            'free_shipping_minimum' => $freeShippingAmount,
            'message' => 'Delivery is available for this pincode.',
        ]);
    }

    /**
     * Catalog product listing with filters.
     */
    public function productListing(Request $request)
    {
        $rawBrandInput = $request->input('brand', $request->input('brand_id', []));
        $rawBrandList = array_values(array_filter(
            array_map(fn($v) => is_scalar($v) ? trim((string) $v) : '', (array) $rawBrandInput),
            fn($v) => $v !== ''
        ));

        $allBrands = Brand::all();
        $selectedBrands = collect();
        $hasNumericBrandInput = false;

        foreach ($rawBrandList as $raw) {
            $matched = null;
            if (ctype_digit($raw)) {
                $hasNumericBrandInput = true;
                $matched = $allBrands->firstWhere('id', (int) $raw);
            } else {
                $matched = $allBrands->first(function ($b) use ($raw) {
                    return strcasecmp((string) $b->slug, $raw) === 0
                        || strcasecmp(Str::slug($b->name), $raw) === 0;
                });
            }

            if ($matched && ! $selectedBrands->contains('id', $matched->id)) {
                $selectedBrands->push($matched);
            }
        }

        // Single brand selected -> redirect 301 to dedicated brand URL: /brand/{slug}
        if ($selectedBrands->count() === 1) {
            $brandSlug = $selectedBrands->first()->slug;
            $redirectParams = $request->query();
            unset($redirectParams['brand'], $redirectParams['brand_id']);

            return redirect()->route('brand.products', array_merge(['slug' => $brandSlug], $redirectParams), 301);
        }

        // Redirect numeric brand query (for multiple brands) to canonical slug query
        if ($hasNumericBrandInput && $selectedBrands->isNotEmpty()) {
            $redirectParams = $request->query();
            $redirectParams['brand'] = $selectedBrands->pluck('slug')->values()->all();
            unset($redirectParams['brand_id']);

            return redirect()->route('product.listing', $redirectParams, 301);
        }

        $brandIds = $selectedBrands->pluck('id')->all();
        $selectedBrandSlugs = $selectedBrands->pluck('slug')->all();

        return $this->buildListingPage(
            request: $request,
            brandIds: $brandIds,
            selectedBrandSlugs: $selectedBrandSlugs
        );
    }

    /**
     * AJAX Grid for category product listing.
     */
    public function productListingGrid($slug)
    {
        try {
            $category = Category::where('slug', $slug)->firstOrFail();

            $categoryFilter = request()->input('category');
            $selectedCategoryIds = $categoryFilter
                ? array_map('intval', (array) $categoryFilter)
                : [$category->id];
            $gender = $this->resolveListingGender(request()->input('gender'));

            $baseQuery = Product::query()
                ->where('status', 1)
                ->tap(fn($query) => $this->applyListingGenderFilter($query, $gender, $selectedCategoryIds[0] ?? null))
                ->inCategories($selectedCategoryIds);

            if ($priceFilter = request()->input('price')) {
                $this->applyPriceRangeFilter($baseQuery, (array) $priceFilter);
            }

            $products = $baseQuery
                ->withCount(['reviews as reviews_count' => fn($q) => $q->where('status', 1)])
                ->latest()
                ->paginate(12)
                ->withQueryString();

            $this->attachProductReviewMeta($products->getCollection());

            $priceRanges = $this->buildPriceRangesWithCounts(
                Product::query()
                    ->where('status', 1)
                    ->tap(fn($query) => $this->applyListingGenderFilter($query, $gender, $selectedCategoryIds[0] ?? null))
                    ->inCategories($selectedCategoryIds)
            );

            $html = view('frontend.partials.product-listing-grid', compact('products'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'price_ranges' => $priceRanges,
                'meta' => [
                    'product_total' => $products->total(),
                    'product_count' => $products->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Product listing grid failed: ' . $e->getMessage(), [
                'slug' => $slug,
                'category' => request()->input('category'),
                'price' => request()->input('price'),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Log AJAX product listing errors from frontend JS.
     */
    public function logProductListingClientError(Request $request)
    {
        $payload = [
            'event' => $request->input('event', 'error'),
            'message' => $request->input('message'),
            'url' => $request->input('url'),
            'http_status' => $request->input('status'),
            'response_preview' => $request->input('response'),
            'html_length' => $request->input('html_length'),
            'product_boxes' => $request->input('product_boxes'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        Log::error('Product listing AJAX client event', $payload);

        return response()->json(['success' => true]);
    }

    /**
     * Super Deals catalog page.
     */
    public function superDeals()
    {
        $categories = $this->listingCategories(true);
        [$products, $selectedCategoryIds, $selectedPriceKeys, $priceRanges] = $this->buildSuperDealProducts();

        $category = (object) [
            'name' => 'Super Deals',
            'slug' => 'super-deals',
            'id' => null,
        ];

        $listingBreadcrumb = 'Super Deals';
        $listingTitle = 'Super Deals';
        $breadcrumbLabel = 'Super Deals';
        $brands = Brand::where('status', 1)
            ->whereHas('products', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('name')
            ->get();
        $gender = null;
        $filter = null;
        $maxPrice = null;
        $minPrice = null;
        $sort = null;
        $brandIds = [];
        $selectedBrandSlugs = [];
        $categoryId = null;
        $filterAction = route('super.deals');
        $gridUrl = rtrim(request()->getBaseUrl(), '/') . '/ajax/super-deals-products';
        $pageUrl = url()->current();
        $showDiscountTagOnly = true;

        return view('frontend.pages.product_listing', compact(
            'category',
            'categories',
            'products',
            'brands',
            'selectedCategoryIds',
            'selectedPriceKeys',
            'priceRanges',
            'listingBreadcrumb',
            'listingTitle',
            'breadcrumbLabel',
            'gender',
            'filter',
            'maxPrice',
            'minPrice',
            'sort',
            'brandIds',
            'selectedBrandSlugs',
            'categoryId',
            'filterAction',
            'gridUrl',
            'pageUrl',
            'showDiscountTagOnly'
        ));
    }

    /**
     * AJAX Grid for Super Deals.
     */
    public function superDealsGrid()
    {
        try {
            [$products,,, $priceRanges] = $this->buildSuperDealProducts();
            $products->withPath(rtrim(request()->getBaseUrl(), '/') . '/ajax/super-deals-products');

            $filterAction = route('super.deals');
            $showDiscountTagOnly = true;

            return response()->json([
                'success' => true,
                'html' => view('frontend.partials.product-listing-grid', compact(
                    'products',
                    'filterAction',
                    'showDiscountTagOnly'
                ))->render(),
                'price_ranges' => $priceRanges,
            ]);
        } catch (\Throwable $e) {
            $this->logProductListingError('superDealsGrid', $e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Autocomplete suggestions for search input.
     */
    public function searchSuggest(Request $request)
    {
        $search = trim((string) $request->get('q', ''));

        if ($search === '') {
            $trendingProducts = Product::where('status', 1)
                ->where(function ($query) {
                    $query->where('is_trending', 1)
                        ->orWhere('is_best_selling', 1)
                        ->orWhere('is_new_arrival', 1);
                })
                ->with(['brand', 'category'])
                ->orderByDesc('is_trending')
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->map(fn($product) => [
                    'type' => 'product',
                    'id' => $product->id,
                    'name' => $product->name,
                    'url' => route('product.details', ['slug' => $product->slug]),
                    'image' => $product->thumbnailUrl(),
                    'price' => ($product->selling_price > 0 ? $product->selling_price : $product->price) ?? $product->price,
                    'brand' => $product->brand->name ?? '',
                ])
                ->values();

            if ($trendingProducts->isEmpty()) {
                $trendingProducts = Product::where('status', 1)
                    ->orderByDesc('id')
                    ->limit(6)
                    ->get()
                    ->map(fn($product) => [
                        'type' => 'product',
                        'id' => $product->id,
                        'name' => $product->name,
                        'url' => route('product.details', ['slug' => $product->slug]),
                        'image' => $product->thumbnailUrl(),
                        'price' => ($product->selling_price > 0 ? $product->selling_price : $product->price) ?? $product->price,
                    ])
                    ->values();
            }

            return response()->json([
                'success' => true,
                'query' => '',
                'results' => [
                    'products' => $trendingProducts,
                    'brands' => [],
                    'categories' => [],
                ],
            ]);
        }

        $cleanSearch = preg_replace('/[\s\-_]+/', '', $search);

        $products = Product::where('status', 1)
            ->where(function ($query) use ($search, $cleanSearch) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('details', 'LIKE', "%{$search}%")
                    ->orWhere('model_no', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%");

                if ($cleanSearch !== '' && $cleanSearch !== $search && strlen($cleanSearch) >= 2) {
                    $cleanLike = "%{$cleanSearch}%";
                    $query->orWhereRaw("REPLACE(REPLACE(REPLACE(COALESCE(model_no, ''), '-', ''), ' ', ''), '_', '') LIKE ?", [$cleanLike])
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(COALESCE(sku, ''), '-', ''), ' ', ''), '_', '') LIKE ?", [$cleanLike]);
                }

                $query->orWhereHas('brand', function ($bq) use ($search) {
                    $bq->where('status', 1)->where('name', 'LIKE', "%{$search}%");
                });
            })
            ->with(['brand', 'category'])
            ->limit(8)
            ->get()
            ->map(fn($product) => [
                'type' => 'product',
                'id' => $product->id,
                'name' => $product->name,
                'url' => route('product.details', ['slug' => $product->slug]),
                'image' => $product->thumbnailUrl(),
                'price' => ($product->selling_price > 0 ? $product->selling_price : $product->price) ?? $product->price,
                'brand' => $product->brand->name ?? '',
            ])
            ->values();

        $brands = Brand::where('status', 1)
            ->where('name', 'LIKE', "%{$search}%")
            ->limit(4)
            ->get()
            ->map(fn($brand) => [
                'type' => 'brand',
                'id' => $brand->id,
                'name' => $brand->name,
                'url' => route('brand.products', ['slug' => $brand->slug]),
            ])
            ->values();

        $categories = Category::where('status', 1)
            ->where('name', 'LIKE', "%{$search}%")
            ->limit(4)
            ->get()
            ->map(fn($category) => [
                'type' => 'category',
                'id' => $category->id,
                'name' => $category->name,
                'url' => route('category.show', ['slug' => $category->slug]),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'query' => $search,
            'results' => [
                'products' => $products,
                'brands' => $brands,
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * Search Results page.
     */
    public function searchResults(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        if ($search === '') {
            return redirect()->route('home');
        }

        $gender = $this->resolveListingGender($request->input('gender'));
        $filter = $request->input('filter');
        $categoryId = $request->filled('category') ? (int) $request->input('category') : null;
        $subcategoryId = $request->filled('subcategory') ? (int) $request->input('subcategory') : null;
        $maxPrice = $request->filled('max_price') ? (float) $request->input('max_price') : null;
        $minPrice = $request->filled('min_price') ? (float) $request->input('min_price') : null;
        $sort = $request->input('sort');

        $rawBrandInput = $request->input('brand', $request->input('brand_id', []));
        $rawBrandList = array_values(array_filter(
            array_map(fn($v) => is_scalar($v) ? trim((string) $v) : '', (array) $rawBrandInput),
            fn($v) => $v !== ''
        ));

        $allBrands = Brand::all();
        $selectedBrands = collect();
        $hasNumericBrandInput = false;

        foreach ($rawBrandList as $raw) {
            $matched = null;
            if (ctype_digit($raw)) {
                $hasNumericBrandInput = true;
                $matched = $allBrands->firstWhere('id', (int) $raw);
            } else {
                $matched = $allBrands->first(function ($b) use ($raw) {
                    return strcasecmp((string) $b->slug, $raw) === 0
                        || strcasecmp(Str::slug($b->name), $raw) === 0;
                });
            }

            if ($matched && ! $selectedBrands->contains('id', $matched->id)) {
                $selectedBrands->push($matched);
            }
        }

        if ($hasNumericBrandInput && $selectedBrands->isNotEmpty()) {
            $redirectParams = $request->query();
            $redirectParams['brand'] = $selectedBrands->pluck('slug')->values()->all();
            unset($redirectParams['brand_id']);

            return redirect()->route('search', $redirectParams, 301);
        }

        $brandIds = $selectedBrands->pluck('id')->all();
        $selectedBrandSlugs = $selectedBrands->pluck('slug')->all();

        return $this->buildListingPage(
            request: $request,
            brandIds: $brandIds,
            selectedBrandSlugs: $selectedBrandSlugs,
            filterAction: route('search'),
            clearFilterUrl: route('search', ['search' => $search])
        );
    }

    /**
     * Brands listing page.
     */
    public function brands(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $letter = strtoupper(trim((string) $request->input('letter', '')));
        $sort = $request->input('sort');

        $brandsQuery = Brand::where('status', 1)
            ->whereHas('products', function ($q) {
                $q->where('status', 1);
            })
            ->withCount(['products' => function ($q) {
                $q->where('status', 1);
            }]);

        if ($search !== '') {
            $brandsQuery->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($letter !== '' && ctype_alpha($letter)) {
            $brandsQuery->where('name', 'LIKE', "{$letter}%");
        }

        match ($sort) {
            'name_asc' => $brandsQuery->orderBy('name', 'asc'),
            'name_desc' => $brandsQuery->orderBy('name', 'desc'),
            'popular' => $brandsQuery->orderByDesc('products_count')->orderBy('name', 'asc'),
            'latest' => $brandsQuery->latest('id'),
            default => $brandsQuery->orderBy('position')->orderBy('name', 'asc'),
        };

        $brands = $brandsQuery->get();
        $totalCount = $brands->count();
        $shownCount = $totalCount;

        $allActiveBrands = Brand::where('status', 1)
            ->whereHas('products', function ($q) {
                $q->where('status', 1);
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $availableLetters = $allActiveBrands
            ->map(fn($b) => strtoupper(substr(trim($b->name), 0, 1)))
            ->filter(fn($c) => ctype_alpha($c))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $brandsBanner = HomeBanner::getBannerUrl('brands');

        return view('frontend.pages.brands', compact(
            'brands',
            'search',
            'letter',
            'sort',
            'availableLetters',
            'totalCount',
            'shownCount',
            'brandsBanner'
        ));
    }

    /**
     * Dedicated brand products page.
     */
    public function brandProducts(Request $request, $slug)
    {
        $allBrands = Brand::all();
        $brand = null;

        if (ctype_digit((string) $slug)) {
            $brand = $allBrands->firstWhere('id', (int) $slug);
        } else {
            $brand = $allBrands->first(function ($b) use ($slug) {
                return strcasecmp((string) $b->slug, (string) $slug) === 0
                    || strcasecmp(Str::slug($b->name), (string) $slug) === 0;
            });
        }

        if (! $brand) {
            abort(404);
        }

        if ((string) $slug !== (string) $brand->slug) {
            return redirect()->route('brand.products', array_merge(['slug' => $brand->slug], $request->query()), 301);
        }

        $submittedBrands = array_values(array_filter(
            array_map(fn($v) => is_scalar($v) ? trim((string) $v) : '', (array) $request->input('brand', [])),
            fn($v) => $v !== ''
        ));

        if (! empty($submittedBrands)) {
            $isOnlyCurrentBrand = count($submittedBrands) === 1 && (
                strcasecmp($submittedBrands[0], $brand->slug) === 0 ||
                (ctype_digit($submittedBrands[0]) && (int) $submittedBrands[0] === (int) $brand->id)
            );

            if (! $isOnlyCurrentBrand) {
                return redirect()->route('product.listing', $request->query());
            }
        }

        return $this->buildListingPage(
            request: $request,
            brandIds: [$brand->id],
            selectedBrandSlugs: [$brand->slug],
            currentBrand: $brand,
            filterAction: route('brand.products', ['slug' => $brand->slug]),
            clearFilterUrl: route('brand.products', ['slug' => $brand->slug])
        );
    }

    /**
     * Best Sellers Page.
     */
    public function bestSellers(Request $request)
    {
        return $this->buildListingPage(
            request: $request,
            filterAction: route('best.sellers'),
            clearFilterUrl: route('best.sellers'),
            presetFilter: 'best_selling',
            customTitle: 'Best Sellers',
            customSubtitle: 'Discover our best-selling fragrances, loved by customers for their style, quality, and popularity.',
            customBreadcrumb: 'Best Sellers'
        );
    }

    /**
     * New Arrivals Page.
     */
    public function newarrivals(Request $request)
    {
        return $this->buildListingPage(
            request: $request,
            filterAction: route('new.arrivals'),
            clearFilterUrl: route('new.arrivals'),
            presetFilter: 'new_arrival',
            customTitle: 'New Arrivals',
            customSubtitle: 'Explore our newest fragrance arrivals and latest luxury collections.',
            customBreadcrumb: 'New Arrivals'
        );
    }

    /**
     * Combo Offers Page.
     */
    public function comboOffers(Request $request)
    {
        return $this->buildListingPage(
            request: $request,
            filterAction: route('combo.offers'),
            clearFilterUrl: route('combo.offers'),
            presetFilter: 'combo_offers',
            customTitle: 'Combo Offers',
            customSubtitle: 'Explore our exclusive fragrance sets and curated bundle offers.',
            customBreadcrumb: 'Combo Offers'
        );
    }

    /**
     * Gifting Collection Page.
     */
    public function gifting(Request $request)
    {
        return $this->buildListingPage(
            request: $request,
            filterAction: route('gifting'),
            clearFilterUrl: route('gifting'),
            presetFilter: 'gifting',
            customTitle: 'Gifting Collection',
            customSubtitle: 'Thoughtfully curated fragrance combo sets crafted to delight your loved ones on every special occasion.',
            customBreadcrumb: 'Gifting'
        );
    }
}
