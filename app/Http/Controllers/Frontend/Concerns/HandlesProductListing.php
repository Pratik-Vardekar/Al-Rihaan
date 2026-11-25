<?php

namespace App\Http\Controllers\Frontend\Concerns;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait HandlesProductListing
{
    /**
     * Attach reviews rating, is_new flag, and dynamic discount percentage to product collections.
     */
    public function attachProductReviewMeta($products): void
    {
        $collection = method_exists($products, 'getCollection') ? $products->getCollection() : $products;

        $collection->each(function ($product) {
            try {
                $product->setAttribute('rating', $product->averageRating() ?? 0);
            } catch (\Throwable $e) {
                $product->setAttribute('rating', 0);
            }
            $product->setAttribute('is_new', (bool) ($product->is_new_arrival ?? false));

            $price = (float) ($product->price ?? 0);
            $selling = (float) ($product->selling_price ?? 0);
            $stored = (int) ($product->discount ?? 0);

            if (($selling <= 0 || $selling >= $price) && $stored > 0 && $price > 0) {
                $selling = max(0, round($price - ($price * ($stored / 100)), 2));
                $product->selling_price = $selling;
            }

            if ($selling <= 0) {
                $selling = $price;
            }

            $discount = 0;
            if ($selling < $price) {
                $discount = $stored > 0 ? $stored : (int) round((($price - $selling) / $price) * 100);
            } elseif ($stored > 0) {
                $discount = $stored;
            }

            $product->setAttribute('discount_percent', $discount);
        });
    }

    /**
     * Build the standard catalog listing page with filters, search, sort, and pagination.
     */
    public function buildListingPage(
        Request $request,
        array $brandIds = [],
        array $selectedBrandSlugs = [],
        ?Brand $currentBrand = null,
        ?string $filterAction = null,
        ?string $clearFilterUrl = null,
        ?int $overrideCategoryId = null,
        ?Category $categoryModel = null,
        ?string $presetFilter = null,
        ?string $customTitle = null,
        ?string $customSubtitle = null,
        ?string $customBreadcrumb = null
    ) {
        $selectedGenders = $this->resolveListingGenders($request->input('gender'));
        $gender = count($selectedGenders) === 1 ? $selectedGenders[0] : null;
        $filter = $presetFilter ?: $request->input('filter');
        if (!$filter) {
            if ($request->filled('col_best_sellers')) {
                $filter = 'best_selling';
            } elseif ($request->filled('col_new_arrivals')) {
                $filter = 'new_arrival';
            } elseif ($request->filled('col_combo_offers')) {
                $filter = 'combo_offers';
            } elseif ($request->filled('col_gifting')) {
                $filter = 'gifting';
            }
        }
        $onSale = $request->boolean('on_sale') || $filter === 'sale' || $request->boolean('sale');

        // Support array, integer, or slug category inputs
        $rawCategoryInput = (array) $request->input('category', []);
        $selectedCategoryIds = [];
        foreach ($rawCategoryInput as $cVal) {
            if (is_numeric($cVal) && (int) $cVal > 0) {
                $selectedCategoryIds[] = (int) $cVal;
            } elseif (is_string($cVal) && trim($cVal) !== '') {
                $matchedCatId = Category::where('slug', trim($cVal))->orWhere('name', trim($cVal))->value('id');
                if ($matchedCatId) {
                    $selectedCategoryIds[] = (int) $matchedCatId;
                }
            }
        }
        if ($overrideCategoryId) {
            $selectedCategoryIds[] = (int) $overrideCategoryId;
        }
        $selectedCategoryIds = array_values(array_unique($selectedCategoryIds));
        $categoryId = count($selectedCategoryIds) === 1 ? $selectedCategoryIds[0] : null;

        $subcategoryId = $request->filled('subcategory') ? (int) $request->input('subcategory') : null;
        $maxPrice = $request->filled('max_price') ? (float) $request->input('max_price') : null;
        $minPrice = $request->filled('min_price') ? (float) $request->input('min_price') : null;
        $sort = $request->input('sort');
        $search = trim((string) $request->input('search', ''));

        $categories = Category::where('status', 1)
            ->withStartingFromPrice()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $priceSql = Category::productPriceSql('products');

        $productsQuery = Product::query()
            ->where('products.status', 1)
            ->with(['brand', 'category'])
            ->when($search !== '', function ($query) use ($search) {
                $cleanSearch = preg_replace('/[\s\-_]+/', '', $search);
                $query->where(function ($q) use ($search, $cleanSearch) {
                    $q->where('products.name', 'LIKE', "%{$search}%")
                        ->orWhere('products.model_no', 'LIKE', "%{$search}%")
                        ->orWhere('products.sku', 'LIKE', "%{$search}%")
                        ->orWhere('products.description', 'LIKE', "%{$search}%")
                        ->orWhere('products.details', 'LIKE', "%{$search}%");

                    if ($cleanSearch !== '' && $cleanSearch !== $search && strlen($cleanSearch) >= 2) {
                        $cleanLike = "%{$cleanSearch}%";
                        $q->orWhereRaw("REPLACE(REPLACE(REPLACE(COALESCE(products.model_no, ''), '-', ''), ' ', ''), '_', '') LIKE ?", [$cleanLike])
                            ->orWhereRaw("REPLACE(REPLACE(REPLACE(COALESCE(products.sku, ''), '-', ''), ' ', ''), '_', '') LIKE ?", [$cleanLike]);
                    }

                    $q->orWhereHas('variants', function ($vq) use ($search, $cleanSearch) {
                        $vq->where('sku', 'LIKE', "%{$search}%");
                        if ($cleanSearch !== '' && $cleanSearch !== $search && strlen($cleanSearch) >= 2) {
                            $cleanLike = "%{$cleanSearch}%";
                            $vq->orWhereRaw("REPLACE(REPLACE(REPLACE(COALESCE(sku, ''), '-', ''), ' ', ''), '_', '') LIKE ?", [$cleanLike]);
                        }
                    })
                        ->orWhereHas('brand', function ($bq) use ($search) {
                            $bq->where('status', 1)->where('name', 'LIKE', "%{$search}%");
                        })
                        ->orWhereHas('category', function ($cq) use ($search) {
                            $cq->where('status', 1)->where(function ($ccq) use ($search) {
                                $ccq->where('name', 'LIKE', "%{$search}%")
                                    ->orWhere('slug', 'LIKE', "%{$search}%");
                            });
                        })
                        ->orWhereHas('subcategory', function ($sq) use ($search) {
                            $sq->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('slug', 'LIKE', "%{$search}%");
                        });
                });
            })
            ->tap(fn($query) => $this->applyListingGenderFilter($query, $selectedGenders, $categoryId, $subcategoryId, $onSale))
            ->when(!empty($selectedCategoryIds), fn($query) => $query->inCategories($selectedCategoryIds))
            ->when(empty($selectedCategoryIds) && $subcategoryId, fn($query) => $query->inSubcategory($subcategoryId))
            ->when(empty($selectedCategoryIds) && ! $subcategoryId && $categoryId, fn($query) => $query->inCategory($categoryId))
            ->when($filter === 'best_selling', function ($query) {
                if (Product::where('status', 1)->where('is_best_selling', 1)->exists()) {
                    $query->where('products.is_best_selling', 1);
                }
            })
            ->when($filter === 'new_arrival', function ($query) {
                if (Product::where('status', 1)->where('is_new_arrival', 1)->exists()) {
                    $query->where('products.is_new_arrival', 1);
                }
            })
            ->when($filter === 'combo_offers' || $filter === 'combo', function ($query) {
                if (Product::where('status', 1)->where('is_combo_offer', 1)->exists()) {
                    $query->where('products.is_combo_offer', 1);
                } else {
                    $query->where(function ($q) {
                        $q->where('products.name', 'LIKE', '%combo%')
                            ->orWhere('products.name', 'LIKE', '%bundle%')
                            ->orWhere('products.name', 'LIKE', '%pack%')
                            ->orWhere('products.name', 'LIKE', '%set%');
                    });
                }
            })
            ->when($filter === 'gifting', function ($query) {
                $query->where(function ($q) {
                    $q->where('products.is_combo_offer', 1)
                        ->orWhere('products.name', 'LIKE', '%gift%')
                        ->orWhere('products.name', 'LIKE', '%combo%')
                        ->orWhere('products.name', 'LIKE', '%box%')
                        ->orWhere('products.name', 'LIKE', '%set%')
                        ->orWhereHas('category', function ($cq) {
                            $cq->where('name', 'LIKE', '%gift%')
                                ->orWhere('slug', 'LIKE', '%gift%')
                                ->orWhere('name', 'LIKE', '%combo%')
                                ->orWhere('slug', 'LIKE', '%combo%');
                        });
                });
            })
            ->when($onSale, function ($query) {
                $query->where(function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereNotNull('products.selling_price')
                            ->where('products.selling_price', '>', 0)
                            ->whereColumn('products.price', '>', 'products.selling_price');
                    })
                        ->orWhere(function ($sq) {
                            $sq->whereNotNull('products.discount')
                                ->where('products.discount', '>', 0);
                        })
                        ->orWhere(function ($sq) {
                            $sq->whereNotNull('products.badge')
                                ->where(function ($bq) {
                                    $bq->where('products.badge', 'LIKE', '%sale%')
                                        ->orWhere('products.badge', 'LIKE', '%discount%')
                                        ->orWhere('products.badge', 'LIKE', '%off%');
                                });
                        })
                        ->orWhereHas('variants', function ($vq) {
                            $vq->whereNotNull('selling_price')
                                ->where('selling_price', '>', 0)
                                ->whereColumn('price', '>', 'selling_price');
                        });
                });
            })
            ->when(!empty($brandIds), fn($query) => $query->whereIn('products.brand_id', $brandIds))
            ->when(
                $minPrice !== null && $minPrice > 0,
                fn($query) => $query->whereRaw("({$priceSql}) >= ?", [$minPrice])
            )
            ->when(
                $maxPrice !== null && $maxPrice > 0,
                fn($query) => $query->whereRaw("({$priceSql}) > 0 AND ({$priceSql}) <= ?", [$maxPrice])
            )
            ->when(!empty(array_filter((array) $request->input('price', []))), function ($query) use ($request, $priceSql) {
                $rawPrices = array_filter((array) $request->input('price', []));
                $query->where(function ($pq) use ($rawPrices, $priceSql) {
                    foreach ($rawPrices as $pKey) {
                        if ($pKey === 'under-500') {
                            $pq->orWhereRaw("({$priceSql}) <= 500");
                        } elseif ($pKey === '500-1000') {
                            $pq->orWhereRaw("({$priceSql}) >= 500 AND ({$priceSql}) <= 1000");
                        } elseif ($pKey === '1000-2000') {
                            $pq->orWhereRaw("({$priceSql}) >= 1000 AND ({$priceSql}) <= 2000");
                        } elseif ($pKey === 'above-2000') {
                            $pq->orWhereRaw("({$priceSql}) >= 2000");
                        }
                    }
                });
            })
            ->when(!empty(array_filter((array) $request->input('notes', []))), function ($query) use ($request) {
                $notes = array_filter((array) $request->input('notes', []));
                $query->where(function ($nq) use ($notes) {
                    foreach ($notes as $note) {
                        $note = trim($note);
                        if ($note !== '') {
                            $nq->orWhere('products.name', 'LIKE', "%{$note}%")
                                ->orWhere('products.description', 'LIKE', "%{$note}%")
                                ->orWhere('products.details', 'LIKE', "%{$note}%")
                                ->orWhere('products.specification', 'LIKE', "%{$note}%");
                        }
                    }
                });
            })
            ->withCount([
                'reviews as reviews_count' => fn($query) => $query->where('reviews.status', 1),
            ]);

        // Apply sorting
        match ($sort) {
            'price_asc', 'low' => $productsQuery->orderByRaw("{$priceSql} asc"),
            'price_desc', 'high' => $productsQuery->orderByRaw("{$priceSql} desc"),
            'new_arrival', 'new' => $productsQuery->latest('products.created_at'),
            'brand_asc' => $productsQuery->orderBy(
                Brand::select('name')->whereColumn('brands.id', 'products.brand_id')->limit(1)
            ),
            'brand_desc' => $productsQuery->orderByDesc(
                Brand::select('name')->whereColumn('brands.id', 'products.brand_id')->limit(1)
            ),
            'relevance', 'default' => $productsQuery
                ->orderByDesc('products.is_featured')
                ->orderByDesc('products.is_best_selling')
                ->latest('products.created_at'),
            default => $productsQuery
                ->orderByDesc('products.is_featured')
                ->orderByDesc('products.is_best_selling')
                ->latest('products.created_at'),
        };

        $products = $productsQuery->paginate(12)->withQueryString();
        $this->attachProductReviewMeta($products);

        $brands = Brand::where('status', 1)
            ->whereHas('products', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('name')
            ->get();

        $resolvedCategoryModel = $categoryModel;
        if (! $resolvedCategoryModel && $categoryId) {
            $resolvedCategoryModel = $categories->firstWhere('id', $categoryId)
                ?: Category::where('status', 1)->find($categoryId);
        }

        $listingTitle = $customTitle
            ?: ($resolvedCategoryModel
                ? $resolvedCategoryModel->name
                : ($currentBrand ? ($currentBrand->name . ' Fragrances') : $this->resolveListingTitle($gender, $filter, $maxPrice, $categoryId, $categories, $brandIds, $subcategoryId, $onSale)));

        $listingSubtitle = $customSubtitle
            ?: match ($filter) {
                'best_selling' => 'Discover our best-selling fragrances, loved by customers for their style, quality, and popularity.',
                'new_arrival' => 'Explore our newest fragrance arrivals and latest luxury collections.',
                'combo_offers', 'combo' => 'Explore our exclusive fragrance sets and curated bundle offers.',
                'gifting' => 'Thoughtfully curated fragrance combo sets crafted to delight your loved ones on every special occasion.',
                default => ($resolvedCategoryModel ? ($resolvedCategoryModel->description ?: 'Explore our collection of signature fragrances, crafted for every occasion.') : 'Explore our collection of signature fragrances, crafted for every occasion.'),
            };

        $breadcrumbLabel = $customBreadcrumb
            ?: ($resolvedCategoryModel
                ? $resolvedCategoryModel->name
                : ($currentBrand ? $currentBrand->name : $this->resolveListingBreadcrumb($gender, $filter, $brandIds, $maxPrice, $onSale)));

        // Resolve listing header banner
        $listingBanner = null;
        if ($currentBrand && !empty($currentBrand->banner_url)) {
            $listingBanner = $currentBrand->banner_url;
        } elseif ($gender === 'men') {
            $listingBanner = HomeBanner::getBannerUrl('men');
        } elseif ($gender === 'women') {
            $listingBanner = HomeBanner::getBannerUrl('women');
        } else {
            $listingBanner = HomeBanner::getBannerUrl('default');
        }

        $wishlistIds = [];
        try {
            $wishlistIds = app(\App\Services\WishlistService::class)->getProductIds();
        } catch (\Throwable $e) {
            $wishlistIds = [];
        }

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->boolean('ajax')) {
            $firstItem = $products->firstItem() ?? 0;
            $lastItem = $products->lastItem() ?? 0;
            $totalCount = $products->total();

            $gridHtml = view('frontend.partials.product-listing-grid-items', compact('products', 'wishlistIds'))->render();
            $paginationHtml = view('frontend.partials.product-listing-pagination', compact('products'))->render();

            return response()->json([
                'success' => true,
                'total_count' => $totalCount,
                'first_item' => $firstItem,
                'last_item' => $lastItem,
                'showing_text' => $totalCount > 0
                    ? "Showing <strong>{$firstItem}–{$lastItem}</strong> of <strong>{$totalCount}</strong> products"
                    : "Showing <strong>0</strong> products",
                'grid_html' => $gridHtml,
                'pagination_html' => $paginationHtml,
                'listing_title' => $listingTitle,
                'listing_subtitle' => $listingSubtitle,
                'breadcrumb_label' => $breadcrumbLabel,
            ]);
        }

        return view('frontend.pages.product_listing', compact(
            'categories',
            'products',
            'wishlistIds',
            'brands',
            'listingTitle',
            'listingSubtitle',
            'breadcrumbLabel',
            'listingBanner',
            'gender',
            'selectedGenders',
            'filter',
            'maxPrice',
            'minPrice',
            'sort',
            'brandIds',
            'selectedBrandSlugs',
            'selectedCategoryIds',
            'categoryId',
            'subcategoryId',
            'search',
            'onSale',
            'currentBrand',
            'filterAction',
            'clearFilterUrl'
        ));
    }

    public function resolveListingGenders($gender): array
    {
        $rawList = is_array($gender) ? $gender : [$gender];
        $resolved = [];

        foreach ($rawList as $item) {
            if (! is_scalar($item)) {
                continue;
            }
            $val = strtolower(trim((string) $item));
            if ($val === 'unisex') {
                $val = 'both';
            }
            if (in_array($val, ['women', 'men', 'both'], true)) {
                $resolved[] = $val;
            }
        }

        return array_values(array_unique($resolved));
    }

    public function resolveListingGender($gender): ?string
    {
        $genders = $this->resolveListingGenders($gender);

        return count($genders) === 1 ? $genders[0] : null;
    }

    public function applyListingGenderFilter($query, string|array|null $gender, ?int $categoryId = null, ?int $subcategoryId = null, bool $onSale = false)
    {
        $genders = $this->resolveListingGenders($gender);

        if (empty($genders)) {
            return $query;
        }

        if (count($genders) === 1) {
            return match ($genders[0]) {
                'women', 'men' => $query->forMenuGender($genders[0]),
                'both' => $query->forBothGender(),
                default => $query,
            };
        }

        return $query->where(function ($q) use ($genders) {
            foreach ($genders as $g) {
                if (in_array($g, ['women', 'men'], true)) {
                    $q->orWhere(function ($sub) use ($g) {
                        $sub->forMenuGender($g);
                    });
                } elseif ($g === 'both') {
                    $q->orWhere(function ($sub) {
                        $sub->forBothGender();
                    });
                }
            }
        });
    }

    public function resolveListingTitle(?string $gender, ?string $filter, ?float $maxPrice, ?int $categoryId, $categories, array $brandIds = [], ?int $subcategoryId = null, bool $onSale = false): string
    {
        if ($subcategoryId) {
            $subcategory = SubCategory::where('status', 1)->find($subcategoryId);
            if ($subcategory) {
                return $subcategory->name;
            }
        }

        if ($categoryId) {
            $category = $categories->firstWhere('id', $categoryId);
            if (! $category) {
                $category = Category::where('status', 1)->find($categoryId);
            }
            if ($category) {
                return $category->name;
            }
        }

        if (! empty($brandIds)) {
            $brandNames = Brand::where('status', 1)
                ->whereIn('id', $brandIds)
                ->orderBy('name')
                ->pluck('name')
                ->all();

            if (count($brandNames) === 1) {
                return $brandNames[0] . ' Fragrances';
            }

            if (! empty($brandNames)) {
                return implode(', ', $brandNames) . ' Fragrances';
            }
        }

        $genderLabel = match ($gender) {
            'women' => 'Women',
            'men' => 'Men',
            'both' => 'Unisex',
            default => null,
        };

        if ($onSale) {
            return $genderLabel ? "{$genderLabel} Fragrances on Sale" : 'Fragrances on Sale';
        }

        if ($filter === 'best_selling') {
            return $genderLabel ? "{$genderLabel} Best Sellers" : 'Best Sellers';
        }

        if ($filter === 'new_arrival') {
            return $genderLabel ? "{$genderLabel} New Arrivals" : 'New Arrivals';
        }

        if ($filter === 'combo_offers' || $filter === 'combo') {
            return $genderLabel ? "{$genderLabel} Combo Offers" : 'Combo Offers';
        }

        if ($filter === 'gifting') {
            return $genderLabel ? "{$genderLabel} Gifting Collection" : 'Gifting Collection';
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $priceLabel = 'Under ₹' . number_format($maxPrice);
            $minPrice = request()->filled('min_price') ? (float) request('min_price') : null;
            if ($minPrice !== null && $minPrice > 0) {
                $priceLabel = '₹' . number_format($minPrice) . ' - ₹' . number_format($maxPrice);
            }
            return $genderLabel ? "{$genderLabel} Fragrances {$priceLabel}" : "Fragrances {$priceLabel}";
        }

        return $genderLabel ? "{$genderLabel} Fragrances" : 'All Fragrances';
    }

    public function resolveListingBreadcrumb(?string $gender, ?string $filter, array $brandIds = [], ?float $maxPrice = null, bool $onSale = false): string
    {
        if (! empty($brandIds)) {
            $brandNames = Brand::where('status', 1)
                ->whereIn('id', $brandIds)
                ->orderBy('name')
                ->pluck('name')
                ->all();

            if (count($brandNames) === 1) {
                return $brandNames[0];
            }

            if (! empty($brandNames)) {
                return 'Brands';
            }
        }

        if ($onSale) {
            return match ($gender) {
                'women' => 'Women · Sale',
                'men' => 'Men · Sale',
                'both' => 'Unisex · Sale',
                default => 'Sale',
            };
        }

        if ($filter === 'best_selling') {
            return 'Best Sellers';
        }

        if ($filter === 'new_arrival') {
            return 'New Arrivals';
        }

        if ($filter === 'combo_offers' || $filter === 'combo') {
            return 'Combo Offers';
        }

        if ($filter === 'gifting') {
            return 'Gifting';
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $priceLabel = 'Under ₹' . number_format($maxPrice);
            $minPrice = request()->filled('min_price') ? (float) request('min_price') : null;
            if ($minPrice !== null && $minPrice > 0) {
                $priceLabel = '₹' . number_format($minPrice) . ' - ₹' . number_format($maxPrice);
            }
            return match ($gender) {
                'women' => "Women · {$priceLabel}",
                'men' => "Men · {$priceLabel}",
                'both' => "Unisex · {$priceLabel}",
                default => $priceLabel,
            };
        }

        return match ($gender) {
            'women' => 'Women',
            'men' => 'Men',
            'both' => 'Unisex',
            default => 'Fragrances',
        };
    }

    public function priceRangeDefinitions(): array
    {
        return [
            '500-1000' => [
                'key' => '500-1000',
                'label' => '₹ 500 - ₹ 1000',
                'min' => 500,
                'max' => 1000,
            ],
            '1001-1500' => [
                'key' => '1001-1500',
                'label' => '₹ 1001 - ₹ 1500',
                'min' => 1001,
                'max' => 1500,
            ],
            '1501-2000' => [
                'key' => '1501-2000',
                'label' => '₹ 1501 - ₹ 2000',
                'min' => 1501,
                'max' => 2000,
            ],
            '2001-2500' => [
                'key' => '2001-2500',
                'label' => '₹ 2001 - ₹ 2500',
                'min' => 2001,
                'max' => 2500,
            ],
            '2500+' => [
                'key' => '2500+',
                'label' => 'More than ₹ 2500',
                'min' => 2500,
                'max' => null,
                'exclusive_min' => true,
            ],
        ];
    }

    public function selectedPriceKeys(): array
    {
        $allowed = array_keys($this->priceRangeDefinitions());

        return collect(request()->input('price', []))
            ->filter(fn($key) => in_array($key, $allowed, true))
            ->unique()
            ->values()
            ->all();
    }

    public function effectiveSellingPriceSql(): string
    {
        return 'COALESCE(NULLIF(selling_price, 0), price)';
    }

    public function applyPriceRangeFilter($query, array $selectedPriceKeys)
    {
        if (empty($selectedPriceKeys)) {
            return $query;
        }

        $ranges = $this->priceRangeDefinitions();
        $priceSql = $this->effectiveSellingPriceSql();

        return $query->where(function ($priceQuery) use ($selectedPriceKeys, $ranges, $priceSql) {
            foreach ($selectedPriceKeys as $key) {
                if (!isset($ranges[$key])) {
                    continue;
                }

                $range = $ranges[$key];

                $priceQuery->orWhere(function ($rangeQuery) use ($range, $priceSql) {
                    if (!empty($range['exclusive_min'])) {
                        $rangeQuery->whereRaw("{$priceSql} > ?", [$range['min']]);
                        return;
                    }

                    $rangeQuery->whereRaw("{$priceSql} >= ?", [$range['min']]);

                    if ($range['max'] !== null) {
                        $rangeQuery->whereRaw("{$priceSql} <= ?", [$range['max']]);
                    }
                });
            }
        });
    }

    public function buildPriceRangesWithCounts($baseQuery): array
    {
        $priceSql = $this->effectiveSellingPriceSql();
        $ranges = [];

        foreach ($this->priceRangeDefinitions() as $key => $range) {
            $countQuery = clone $baseQuery;

            if (!empty($range['exclusive_min'])) {
                $countQuery->whereRaw("{$priceSql} > ?", [$range['min']]);
            } else {
                $countQuery->whereRaw("{$priceSql} >= ?", [$range['min']]);
                if ($range['max'] !== null) {
                    $countQuery->whereRaw("{$priceSql} <= ?", [$range['max']]);
                }
            }

            $ranges[] = [
                'key' => $key,
                'label' => $range['label'],
                'count' => (int) $countQuery->count(),
            ];
        }

        return $ranges;
    }

    public function appendListingFilters($paginator): void
    {
        $appends = [];

        if ($categoryFilter = request()->input('category')) {
            $appends['category'] = $categoryFilter;
        }

        if ($priceFilter = request()->input('price')) {
            $appends['price'] = $priceFilter;
        }

        if ($appends) {
            $paginator->appends($appends);
        }
    }

    public function listingCategories(bool $discountedOnly = false)
    {
        return Category::where('status', 1)
            ->withCount([
                'products as products_count' => function ($query) use ($discountedOnly) {
                    $query->where('status', 1);
                    if ($discountedOnly) {
                        $query->where('discount', '>', 0);
                    }
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function buildCategoryListingProducts(Category $category): array
    {
        $selectedCategoryIds = collect(request()->input('category', [$category->id]))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $selectedPriceKeys = $this->selectedPriceKeys();

        $baseQuery = Product::query()
            ->where('status', 1)
            ->when(
                !empty($selectedCategoryIds),
                function ($query) use ($selectedCategoryIds) {
                    return $query->whereHas('categories', function ($q) use ($selectedCategoryIds) {
                        $q->whereIn('categories.id', $selectedCategoryIds);
                    });
                },
                function ($query) use ($category) {
                    return $query->whereHas('categories', function ($q) use ($category) {
                        $q->where('categories.id', $category->id);
                    });
                }
            );

        $priceRanges = $this->buildPriceRangesWithCounts(clone $baseQuery);

        $products = (clone $baseQuery)
            ->tap(fn($query) => $this->applyPriceRangeFilter($query, $selectedPriceKeys))
            ->withCount([
                'reviews as reviews_count' => fn($query) => $query->where('status', 1),
            ])
            ->latest()
            ->paginate(8);

        $this->appendListingFilters($products);
        $this->attachProductReviewMeta($products->getCollection());

        return [$products, $selectedCategoryIds, $selectedPriceKeys, $priceRanges];
    }

    public function buildSuperDealProducts(): array
    {
        $selectedCategoryIds = collect(request()->input('category', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $selectedPriceKeys = $this->selectedPriceKeys();

        $baseQuery = Product::query()
            ->where('status', 1)
            ->where('discount', '>', 0)
            ->when(
                !empty($selectedCategoryIds),
                fn($query) => $query->inCategories($selectedCategoryIds)
            );

        $priceRanges = $this->buildPriceRangesWithCounts(clone $baseQuery);

        $products = (clone $baseQuery)
            ->tap(fn($query) => $this->applyPriceRangeFilter($query, $selectedPriceKeys))
            ->withCount([
                'reviews as reviews_count' => fn($query) => $query->where('status', 1),
            ])
            ->orderByDesc('discount')
            ->latest()
            ->paginate(6);

        $this->appendListingFilters($products);
        $this->attachProductReviewMeta($products->getCollection());
        $products->getCollection()->each(function ($product) {
            $product->setAttribute('is_new', false);
        });

        return [$products, $selectedCategoryIds, $selectedPriceKeys, $priceRanges];
    }

    public function logProductListingError(string $context, \Throwable $e, array $extra = []): void
    {
        \Illuminate\Support\Facades\Log::error('Product listing grid failed: ' . $context, array_merge([
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'category_filter' => request()->input('category'),
            'price_filter' => request()->input('price'),
            'page' => request()->input('page'),
            'url' => request()->fullUrl(),
        ], $extra));

        report($e);
    }
}
