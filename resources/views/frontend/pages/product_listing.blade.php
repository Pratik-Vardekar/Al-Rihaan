@extends('layouts.front')

@section('title', $listingTitle ?? 'All Fragrances | Al Rihan Perfumes')

@section('content')
    @php
        $shownCount = method_exists($products, 'count') ? $products->count() : 0;
        $totalCount = method_exists($products, 'total') ? $products->total() : $shownCount;
        $firstItem = method_exists($products, 'firstItem') ? $products->firstItem() ?? 1 : 1;
        $lastItem = method_exists($products, 'lastItem') ? $products->lastItem() ?? $shownCount : $shownCount;

        $selectedCategories = array_map(fn($v) => (string) $v, (array) request('category', []));
        $selectedGenders = array_map(
            fn($v) => strtolower(trim((string) $v)),
            (array) request('gender', $selectedGenders ?? []),
        );
        $selectedPrices = (array) request('price', []);
        $selectedNotes = (array) request('notes', []);
        $selectedBrandSlugs = (array) request('brand', $selectedBrandSlugs ?? []);
        $currentSort = request('sort', $sort ?? 'default');

        $isGenderFiltered = !empty($selectedGenders) || request()->filled('gender') || !empty($gender);
        $isCategoryFiltered = !empty($selectedCategories) || isset($categoryId) || !empty($selectedCategoryIds) || request()->filled('category');
        $isPriceFiltered = !empty($selectedPrices) || request()->filled('price');
        $isNotesFiltered = !empty($selectedNotes) || request()->filled('notes');
        $isBrandFiltered = !empty($selectedBrandSlugs) || !empty($brandIds) || request()->filled('brand');
        $isCollectionFiltered = !empty($filter) || request()->routeIs('best.sellers', 'combo.offers', 'new.arrivals', 'gifting');

        $hasSpecificFilter = $isGenderFiltered || $isCategoryFiltered || $isPriceFiltered || $isNotesFiltered || $isBrandFiltered || $isCollectionFiltered;

        $openCollections = $isCollectionFiltered || (!$hasSpecificFilter);
        $openCategories = $isCategoryFiltered;
        $openGender = $isGenderFiltered;
        $openPrice = $isPriceFiltered;
        $openNotes = $isNotesFiltered;
        $openBrands = $isBrandFiltered;
    @endphp

    <section class="shop-page">

        <!-- SHOP HEADER -->
        <div class="shop-header">
            <div class="shop-header-content">
                <span class="shop-subtitle">DISCOVER OUR COLLECTION</span>
                <h1>{{ $listingTitle ?? 'All Fragrances' }}</h1>
                <p>{{ $listingSubtitle ?? 'Explore our collection of signature fragrances, crafted for every occasion.' }}
                </p>
            </div>

            <nav class="shop-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <span>/</span>
                <a href="{{ route('product.listing') }}">Shop</a>
                @if (!empty($currentBrand))
                    <span>/</span>
                    <a href="{{ route('brands') }}">Brands</a>
                    <span>/</span>
                    <strong>{{ $currentBrand->name }}</strong>
                @elseif (!empty($breadcrumbLabel) && $breadcrumbLabel !== 'All Fragrances' && $breadcrumbLabel !== 'Fragrances')
                    <span>/</span>
                    <strong>{{ $breadcrumbLabel }}</strong>
                @else
                    <span>/</span>
                    <strong>All Fragrances</strong>
                @endif
            </nav>
        </div>

        <!-- SHOP LAYOUT -->
        <div class="shop-layout">

            <!-- FILTER SIDEBAR -->
            <aside class="shop-sidebar" id="shopSidebar">

                <form id="shopFilterForm" method="GET" action="{{ $filterAction ?? route('product.listing') }}">
                    @if (!empty($search))
                        <input type="hidden" name="search" value="{{ $search }}">
                    @endif
                    @if (!empty($subcategoryId))
                        <input type="hidden" name="subcategory" value="{{ $subcategoryId }}">
                    @endif
                    @if (!empty($onSale))
                        <input type="hidden" name="on_sale" value="1">
                    @endif
                    @if (!empty($filter) && empty($filterAction))
                        <input type="hidden" name="filter" value="{{ $filter }}">
                    @endif
                    <input type="hidden" name="sort" id="shopSortInput" value="{{ $currentSort }}">

                    <div class="filter-heading">
                        <h3>Filters</h3>
                        <a href="{{ $clearFilterUrl ?? route('product.listing') }}"
                            class="clear-filters text-decoration-none">Clear All</a>
                        <button type="button" class="close-filters" aria-label="Close filters">
                            <i data-lucide="x"></i>
                        </button>
                    </div>

                    <!-- COLLECTIONS FILTER -->
                    <div class="filter-group">
                        <button class="filter-group-title" type="button" aria-expanded="{{ $openCollections ? 'true' : 'false' }}">
                            Collections
                            <i data-lucide="{{ $openCollections ? 'minus' : 'plus' }}"></i>
                        </button>

                        <div class="filter-options" style="{{ $openCollections ? 'display:block;' : 'display:none;' }}">
                            <label>
                                <input type="checkbox" name="col_all" value="1" class="collection-filter-checkbox"
                                    data-collection-url="{{ route('product.listing') }}"
                                    data-collection-filter=""
                                    {{ empty($filter) && !$hasSpecificFilter && !request()->routeIs('best.sellers', 'combo.offers', 'new.arrivals', 'gifting') ? 'checked' : '' }}>
                                <span>All Fragrances</span>
                            </label>
                            <label>
                                <input type="checkbox" name="col_best_sellers" value="1" class="collection-filter-checkbox"
                                    data-collection-url="{{ route('best.sellers') }}"
                                    data-collection-filter="best_selling"
                                    {{ ($filter ?? '') === 'best_selling' || request()->routeIs('best.sellers') ? 'checked' : '' }}>
                                <span>Best Sellers</span>
                            </label>
                            <label>
                                <input type="checkbox" name="col_combo_offers" value="1" class="collection-filter-checkbox"
                                    data-collection-url="{{ route('combo.offers') }}"
                                    data-collection-filter="combo_offers"
                                    {{ ($filter ?? '') === 'combo_offers' || request()->routeIs('combo.offers') ? 'checked' : '' }}>
                                <span>Combo Offers</span>
                            </label>
                            <label>
                                <input type="checkbox" name="col_new_arrivals" value="1" class="collection-filter-checkbox"
                                    data-collection-url="{{ route('new.arrivals') }}"
                                    data-collection-filter="new_arrival"
                                    {{ ($filter ?? '') === 'new_arrival' || request()->routeIs('new.arrivals') ? 'checked' : '' }}>
                                <span>New Arrivals</span>
                            </label>
                            <label>
                                <input type="checkbox" name="col_gifting" value="1" class="collection-filter-checkbox"
                                    data-collection-url="{{ route('gifting') }}"
                                    data-collection-filter="gifting"
                                    {{ ($filter ?? '') === 'gifting' || request()->routeIs('gifting') ? 'checked' : '' }}>
                                <span>Gifting</span>
                            </label>
                        </div>
                    </div>

                    <!-- CATEGORY FILTER -->
                    @if (!empty($categories) && $categories->isNotEmpty())
                        <div class="filter-group">
                            <button class="filter-group-title" type="button" aria-expanded="{{ $openCategories ? 'true' : 'false' }}">
                                Categories
                                <i data-lucide="{{ $openCategories ? 'minus' : 'plus' }}"></i>
                            </button>

                            <div class="filter-options" style="{{ $openCategories ? 'display:block;' : 'display:none;' }}">
                                @foreach ($categories as $category)
                                    @php
                                        $catChecked =
                                            in_array((string) $category->id, $selectedCategories, true) ||
                                            in_array((string) $category->slug, $selectedCategories, true) ||
                                            (isset($categoryId) && (int) $categoryId === (int) $category->id) ||
                                            (isset($selectedCategoryIds) &&
                                                in_array((int) $category->id, (array) $selectedCategoryIds, true));
                                    @endphp
                                    <label>
                                        <input type="checkbox" name="category[]" value="{{ $category->id }}"
                                            {{ $catChecked ? 'checked' : '' }}>
                                        <span>{{ $category->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- GENDER FILTER -->
                    <div class="filter-group">
                        <button class="filter-group-title" type="button" aria-expanded="{{ $openGender ? 'true' : 'false' }}">
                            Gender
                            <i data-lucide="{{ $openGender ? 'minus' : 'plus' }}"></i>
                        </button>

                        <div class="filter-options" style="{{ $openGender ? 'display:block;' : 'display:none;' }}">
                            <label>
                                <input type="checkbox" name="gender[]" value="men"
                                    {{ in_array('men', $selectedGenders, true) || request('gender') === 'men' || ($gender ?? null) === 'men' ? 'checked' : '' }}>
                                <span>For Men</span>
                            </label>
                            <label>
                                <input type="checkbox" name="gender[]" value="women"
                                    {{ in_array('women', $selectedGenders, true) || request('gender') === 'women' || ($gender ?? null) === 'women' ? 'checked' : '' }}>
                                <span>For Women</span>
                            </label>
                            <label>
                                <input type="checkbox" name="gender[]" value="both"
                                    {{ in_array('both', $selectedGenders, true) || in_array('unisex', $selectedGenders, true) || request('gender') === 'both' || ($gender ?? null) === 'both' ? 'checked' : '' }}>
                                <span>Unisex</span>
                            </label>
                        </div>
                    </div>

                    <!-- PRICE FILTER -->
                    <div class="filter-group">
                        <button class="filter-group-title" type="button" aria-expanded="{{ $openPrice ? 'true' : 'false' }}">
                            Price Range
                            <i data-lucide="{{ $openPrice ? 'minus' : 'plus' }}"></i>
                        </button>

                        <div class="filter-options" style="{{ $openPrice ? 'display:block;' : 'display:none;' }}">
                            <label>
                                <input type="checkbox" name="price[]" value="under-500"
                                    {{ in_array('under-500', $selectedPrices, true) ? 'checked' : '' }}>
                                <span>Under ₹500</span>
                            </label>
                            <label>
                                <input type="checkbox" name="price[]" value="500-1000"
                                    {{ in_array('500-1000', $selectedPrices, true) ? 'checked' : '' }}>
                                <span>₹500 – ₹1,000</span>
                            </label>
                            <label>
                                <input type="checkbox" name="price[]" value="1000-2000"
                                    {{ in_array('1000-2000', $selectedPrices, true) ? 'checked' : '' }}>
                                <span>₹1,000 – ₹2,000</span>
                            </label>
                            <label>
                                <input type="checkbox" name="price[]" value="above-2000"
                                    {{ in_array('above-2000', $selectedPrices, true) ? 'checked' : '' }}>
                                <span>Above ₹2,000</span>
                            </label>
                        </div>
                    </div>

                    <!-- FRAGRANCE NOTES FILTER -->
                    <div class="filter-group">
                        <button class="filter-group-title" type="button" aria-expanded="{{ $openNotes ? 'true' : 'false' }}">
                            Fragrance Notes
                            <i data-lucide="{{ $openNotes ? 'minus' : 'plus' }}"></i>
                        </button>

                        <div class="filter-options" style="{{ $openNotes ? 'display:block;' : 'display:none;' }}">
                            @foreach ([
            'oud' => 'Oud',
            'musk' => 'Musk',
            'woody' => 'Woody',
            'floral' => 'Floral',
            'amber' => 'Amber',
            'fresh' => 'Fresh',
        ] as $noteKey => $noteLabel)
                                <label>
                                    <input type="checkbox" name="notes[]" value="{{ $noteKey }}"
                                        {{ in_array($noteKey, $selectedNotes, true) ? 'checked' : '' }}>
                                    <span>{{ $noteLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- BRANDS FILTER (When not filtering single brand) -->
                    @if (!empty($brands) && $brands->isNotEmpty() && empty($currentBrand))
                        <div class="filter-group">
                            <button class="filter-group-title" type="button" aria-expanded="{{ $openBrands ? 'true' : 'false' }}">
                                Brands
                                <i data-lucide="{{ $openBrands ? 'minus' : 'plus' }}"></i>
                            </button>

                            <div class="filter-options" style="{{ $openBrands ? 'display:block;' : 'display:none;' }}">
                                @foreach ($brands as $brand)
                                    <label>
                                        <input type="checkbox" name="brand[]" value="{{ $brand->slug }}"
                                            {{ in_array($brand->slug, $selectedBrandSlugs, true) || in_array($brand->id, (array) ($brandIds ?? []), true) ? 'checked' : '' }}>
                                        <span>{{ $brand->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <button type="submit" class="apply-filters">Apply Filters</button>
                </form>

            </aside>

            <div class="shop-overlay"></div>

            <!-- PRODUCTS AREA -->
            <div class="shop-products">

                <!-- TOOLBAR -->
                <div class="shop-toolbar">

                    <div class="shop-toolbar-left">
                        <button type="button" class="filter-toggle" aria-label="Open filters">
                            <i data-lucide="sliders-horizontal"></i>
                            Filters
                        </button>

                        @if ($totalCount > 0)
                            <p id="shopToolbarCount">Showing <strong>{{ $firstItem }}–{{ $lastItem }}</strong> of
                                <strong>{{ $totalCount }}</strong> products
                            </p>
                        @else
                            <p id="shopToolbarCount">Showing <strong>0</strong> products</p>
                        @endif
                    </div>

                    <div class="shop-toolbar-right">
                        <label for="shopSort">Sort By:</label>

                        <select class="shop-sort" id="shopSort">
                            <option value="default"
                                {{ in_array($currentSort, ['default', 'relevance', ''], true) ? 'selected' : '' }}>Featured
                            </option>
                            <option value="low"
                                {{ in_array($currentSort, ['low', 'price_asc'], true) ? 'selected' : '' }}>Price: Low to
                                High</option>
                            <option value="high"
                                {{ in_array($currentSort, ['high', 'price_desc'], true) ? 'selected' : '' }}>Price: High to
                                Low</option>
                            <option value="new"
                                {{ in_array($currentSort, ['new', 'new_arrival'], true) ? 'selected' : '' }}>Newest First
                            </option>
                        </select>
                    </div>

                </div>

                <!-- PRODUCT GRID -->
                <div class="shop-product-grid" id="shopProductGrid">
                    @include('frontend.partials.product-listing-grid-items', ['products' => $products, 'wishlistIds' => $wishlistIds ?? []])
                </div>

                <!-- PAGINATION -->
                <div id="shopPaginationWrapper">
                    @include('frontend.partials.product-listing-pagination', ['products' => $products])
                </div>

            </div>

        </div>

    </section>
@endsection

@push('styles')
    <style>
        .shop-product-grid.is-loading {
            opacity: 0.45;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            const shopPage = document.querySelector('.shop-page');
            const filterForm = document.getElementById('shopFilterForm');
            const sortSelect = document.getElementById('shopSort');
            const sortHidden = document.getElementById('shopSortInput');
            const productGrid = document.getElementById('shopProductGrid');
            const paginationWrapper = document.getElementById('shopPaginationWrapper');
            const toolbarCount = document.getElementById('shopToolbarCount');
            const clearFiltersBtn = document.querySelector('.clear-filters');
            const applyFiltersBtn = document.querySelector('.apply-filters');

            let currentAbortController = null;
            let filterTimeout = null;

            function isSpecificFilterActive() {
                if (!filterForm) return false;
                const specificInputs = filterForm.querySelectorAll(
                    'input[name="category[]"]:checked, input[name="gender[]"]:checked, input[name="price[]"]:checked, input[name="notes[]"]:checked, input[name="brand[]"]:checked'
                );
                return specificInputs.length > 0;
            }

            function syncCollectionCheckboxes() {
                if (!filterForm) return;
                const colAll = filterForm.querySelector('input[name="col_all"]');
                const specificActive = isSpecificFilterActive();
                const otherColChecked = filterForm.querySelector(
                    'input[name="col_best_sellers"]:checked, input[name="col_combo_offers"]:checked, input[name="col_new_arrivals"]:checked, input[name="col_gifting"]:checked'
                );

                if (colAll) {
                    if (specificActive || otherColChecked) {
                        colAll.checked = false;
                    } else {
                        colAll.checked = true;
                    }
                }
            }

            function executeFilter(fetchUrl = null) {
                if (!filterForm || !productGrid) return;

                if (currentAbortController) {
                    currentAbortController.abort();
                }
                currentAbortController = new AbortController();

                let url;
                if (fetchUrl) {
                    url = new URL(fetchUrl, window.location.origin);
                } else {
                    const formAction = filterForm.getAttribute('action') || window.location.pathname;
                    url = new URL(formAction, window.location.origin);

                    // Add all checked checkboxes and hidden inputs from filterForm
                    const formData = new FormData(filterForm);
                    for (const [key, val] of formData.entries()) {
                        if (key === 'col_all') continue;
                        if (val !== '') {
                            url.searchParams.append(key, val);
                        }
                    }

                    // Sort
                    if (sortSelect) {
                        url.searchParams.set('sort', sortSelect.value);
                    }
                }

                // Add loading class
                productGrid.classList.add('is-loading');

                fetch(url.toString(), {
                    signal: currentAbortController.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    const contentType = res.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        return res.json().then(data => ({ isJson: true, data }));
                    } else {
                        return res.text().then(text => ({ isJson: false, text }));
                    }
                })
                .then(result => {
                    productGrid.classList.remove('is-loading');

                    if (result.isJson && result.data && result.data.success) {
                        const data = result.data;
                        if (data.grid_html !== undefined) {
                            productGrid.innerHTML = data.grid_html;
                        }
                        if (paginationWrapper && data.pagination_html !== undefined) {
                            paginationWrapper.innerHTML = data.pagination_html;
                        }
                        if (toolbarCount && data.showing_text !== undefined) {
                            toolbarCount.innerHTML = data.showing_text;
                        }
                        if (data.listing_title) {
                            const titleEl = document.querySelector('.shop-header-content h1');
                            if (titleEl) titleEl.textContent = data.listing_title;
                        }
                        if (data.listing_subtitle) {
                            const subEl = document.querySelector('.shop-header-content p');
                            if (subEl) subEl.textContent = data.listing_subtitle;
                        }
                        if (data.breadcrumb_label) {
                            const breadcrumbStrong = document.querySelector('.shop-breadcrumb strong');
                            if (breadcrumbStrong) breadcrumbStrong.textContent = data.breadcrumb_label;
                        }
                    } else if (!result.isJson) {
                        // Fallback: parse returned HTML
                        const doc = new DOMParser().parseFromString(result.text, 'text/html');
                        const newGrid = doc.getElementById('shopProductGrid') || doc.querySelector('.shop-product-grid');
                        const newPag = doc.getElementById('shopPaginationWrapper') || doc.querySelector('.shop-pagination');
                        const newCount = doc.getElementById('shopToolbarCount') || doc.querySelector('.shop-toolbar-left p');

                        if (newGrid) productGrid.innerHTML = newGrid.innerHTML;
                        if (paginationWrapper && newPag) paginationWrapper.innerHTML = newPag.innerHTML;
                        if (toolbarCount && newCount) toolbarCount.innerHTML = newCount.innerHTML;
                    }

                    // Update browser address bar
                    window.history.pushState({ path: url.toString() }, '', url.toString());

                    // Re-init lucide icons for newly loaded cards
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                })
                .catch(err => {
                    if (err.name === 'AbortError') return;
                    productGrid.classList.remove('is-loading');
                    console.error('Filter fetch error:', err);
                });
            }

            function triggerFilter(url = null) {
                if (filterTimeout) clearTimeout(filterTimeout);
                filterTimeout = setTimeout(() => {
                    executeFilter(url);
                }, 150);
            }

            // Expose globally so pagination / others can call
            window.shopListingPage = {
                triggerFilter: triggerFilter,
                goToPage: function(pageUrl) {
                    executeFilter(pageUrl);
                    if (productGrid) {
                        productGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            };

            // 1. Listen for checkbox changes on filter options
            if (filterForm) {
                filterForm.querySelectorAll('.filter-options input[type="checkbox"]').forEach(function(chk) {
                    chk.addEventListener('change', function(e) {
                        const name = this.name;

                        if (name.startsWith('col_')) {
                            // User clicked a collection checkbox
                            if (this.checked) {
                                // Uncheck all other collection checkboxes
                                filterForm.querySelectorAll('.collection-filter-checkbox').forEach(function(other) {
                                    if (other !== chk) other.checked = false;
                                });
                                // Update form action to this collection's URL
                                const collectionUrl = this.getAttribute('data-collection-url');
                                if (collectionUrl) {
                                    filterForm.setAttribute('action', collectionUrl);
                                }
                            } else {
                                // If unchecking, revert back to col_all
                                const colAll = filterForm.querySelector('input[name="col_all"]');
                                if (colAll) {
                                    colAll.checked = true;
                                    const allUrl = colAll.getAttribute('data-collection-url');
                                    if (allUrl) filterForm.setAttribute('action', allUrl);
                                }
                            }
                        } else {
                            // Category, gender, price, notes, brand
                            syncCollectionCheckboxes();
                        }

                        // Trigger AJAX filter
                        triggerFilter();
                    });
                });
            }

            // 2. Sort change listener
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    if (sortHidden) sortHidden.value = this.value;
                    triggerFilter();
                });
            }

            // 3. Clear filters click
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!filterForm) return;

                    filterForm.querySelectorAll('.filter-options input[type="checkbox"]').forEach(function(chk) {
                        chk.checked = false;
                    });

                    // Keep collection if on collection route, otherwise check col_all
                    const colAll = filterForm.querySelector('input[name="col_all"]');
                    if (colAll) colAll.checked = true;

                    const clearUrl = this.getAttribute('href') || (colAll ? colAll.getAttribute('data-collection-url') : window.location.pathname);
                    if (filterForm) filterForm.setAttribute('action', clearUrl);

                    executeFilter(clearUrl);
                });
            }

            // 4. Apply filters click (Mobile drawer)
            if (applyFiltersBtn) {
                applyFiltersBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    triggerFilter();
                    if (shopPage) {
                        shopPage.classList.remove('filters-open');
                        document.body.style.overflow = '';
                    }
                });
            }

            // 5. Pagination clicks via event delegation
            document.addEventListener('click', function(e) {
                const pageBtn = e.target.closest('#shopPaginationWrapper .page-number, #shopPaginationWrapper .page-arrow');
                if (pageBtn && !pageBtn.disabled) {
                    const pageUrl = pageBtn.getAttribute('data-page-url');
                    if (pageUrl) {
                        e.preventDefault();
                        window.shopListingPage.goToPage(pageUrl);
                    }
                }
            });

            // 6. Browser popstate (back/forward)
            window.addEventListener('popstate', function() {
                window.location.reload();
            });

            // 7. Delegated AJAX Add to Cart
            document.addEventListener('click', function(e) {
                const btnEl = e.target.closest('.js-add-to-cart');
                if (!btnEl) return;
                e.preventDefault();
                e.stopPropagation();

                if (btnEl.getAttribute('data-busy') === '1') return;

                const addUrl = btnEl.getAttribute('data-url');
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                if (!addUrl) return;

                btnEl.setAttribute('data-busy', '1');
                btnEl.disabled = true;
                const originalHtml = btnEl.innerHTML;
                btnEl.innerHTML = '<span>Adding...</span>';

                fetch(addUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ quantity: 1 })
                })
                .then(res => res.json())
                .then(data => {
                    btnEl.removeAttribute('data-busy');
                    btnEl.disabled = false;
                    btnEl.innerHTML = originalHtml;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }

                    if (data.status || data.success) {
                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message || 'Product added to cart!', 'success');
                        }
                        if (typeof window.updateCartBadge === 'function' && typeof data.cart_count !== 'undefined') {
                            window.updateCartBadge(data.cart_count);
                        }
                    } else {
                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message || 'Could not add product to cart.', 'error');
                        }
                    }
                })
                .catch(err => {
                    btnEl.removeAttribute('data-busy');
                    btnEl.disabled = false;
                    btnEl.innerHTML = originalHtml;
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                    if (typeof window.showToast === 'function') {
                        window.showToast('Something went wrong. Please try again.', 'error');
                    }
                });
            });

        });
    </script>
@endpush
