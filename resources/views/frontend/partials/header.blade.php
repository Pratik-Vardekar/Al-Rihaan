    <header class="site-header" id="siteHeader">

        <!-- Announcement Bar -->
        <div class="announcement-bar">
            <div class="announcement-slider">
                <p class="active">Enjoy 20% off with code BUY2</p>
                <p>Complimentary tester on all prepaid orders</p>
                <p>Discover your signature fragrance with Al Rihan</p>
            </div>
        </div>


        <!-- Main Header -->
        <div class="header-container">

            <!-- Mobile Menu Button -->
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open navigation" aria-expanded="false"
                aria-controls="mainNav">
                <i data-lucide="menu"></i>
            </button>

            <!-- Logo -->
            <a href="{{ route('home') }}" class="site-logo">
                <img src="{{ asset('frontend/assets/images/logoairihan.svg') }}" alt="Al Rihan Perfumes">
            </a>

            <!-- Main Navigation -->
            <nav class="main-nav" id="mainNav">
                @php
                    $reqGenderList = array_map(fn($v) => strtolower(trim((string) $v)), (array) request('gender', []));
                    $reqCategoryList = array_map(fn($v) => (string) $v, (array) request('category', []));
                    $headerCategories =
                        isset($navCategories) && $navCategories->isNotEmpty()
                            ? $navCategories
                            : \App\Models\Category::where('status', 1)->orderBy('sort_order')->orderBy('name')->get();
                @endphp

                <!-- All Fragrances Dropdown -->
                <div class="nav-dropdown">

                    <button type="button"
                        class="nav-link nav-dropdown-toggle {{ request()->routeIs('product.listing') && empty($reqCategoryList) ? 'active' : '' }}"
                        aria-expanded="false">

                        <span>All Fragrances</span>

                        <i data-lucide="chevron-down"></i>

                    </button>

                    <div class="nav-submenu">

                        <a href="{{ route('product.listing') }}"
                            class="{{ request()->routeIs('product.listing') && empty($reqGenderList) && empty($reqCategoryList) ? 'active' : '' }}">
                            All Fragrances
                        </a>

                        <a href="{{ route('product.listing', ['gender' => 'men']) }}"
                            class="{{ in_array('men', $reqGenderList, true) ? 'active' : '' }}">
                            For Men
                        </a>

                        <a href="{{ route('product.listing', ['gender' => 'women']) }}"
                            class="{{ in_array('women', $reqGenderList, true) ? 'active' : '' }}">
                            For Women
                        </a>

                        <a href="{{ route('product.listing', ['gender' => 'both']) }}"
                            class="{{ in_array('both', $reqGenderList, true) || in_array('unisex', $reqGenderList, true) ? 'active' : '' }}">
                            Unisex
                        </a>

                    </div>

                </div>


                <!-- Best Sellers -->
                <a href="{{ route('best.sellers') }}"
                    class="nav-link {{ request()->routeIs('best.sellers') ? 'active' : '' }}">
                    Best Sellers
                </a>


                <!-- Combo Offers -->
                <a href="{{ route('combo.offers') }}"
                    class="nav-link {{ request()->routeIs('combo.offers') ? 'active' : '' }}">
                    Combo Offers
                </a>

                <!-- New Arrivals -->
                <a href="{{ route('new.arrivals') }}"
                    class="nav-link {{ request()->routeIs('new.arrivals') ? 'active' : '' }}">
                    New Arrivals
                </a>


                <!-- Shop By Fragrance (Dynamic Categories Dropdown) -->
                <div class="nav-dropdown">

                    <button type="button"
                        class="nav-link nav-dropdown-toggle {{ request()->routeIs('category.show') || !empty($reqCategoryList) ? 'active' : '' }}"
                        aria-expanded="false">

                        <span>Shop by Fragrance</span>

                        <i data-lucide="chevron-down"></i>

                    </button>

                    <div class="nav-submenu">

                        @forelse ($headerCategories as $navCat)
                            @php
                                $isCatActive =
                                    (request()->routeIs('category.show') &&
                                        (request()->route('slug') === $navCat->slug ||
                                            (string) request()->route('slug') === (string) $navCat->id)) ||
                                    in_array((string) $navCat->id, $reqCategoryList, true) ||
                                    in_array((string) $navCat->slug, $reqCategoryList, true);
                            @endphp
                            <a href="{{ route('category.show', $navCat->slug ?: $navCat->id) }}"
                                class="{{ $isCatActive ? 'active' : '' }}">
                                {{ $navCat->name }}
                            </a>
                        @empty
                            <a href="{{ route('product.listing') }}">All Fragrances</a>
                        @endforelse

                    </div>

                </div>


                <!-- Gifting Collection -->
                <a href="{{ route('gifting') }}" class="nav-link {{ request()->routeIs('gifting') ? 'active' : '' }}">
                    Gifting
                </a>

                <!-- Mobile Drawer Footer -->
                <div class="mobile-nav-footer">

                    <div class="mobile-nav-footer__buttons">

                        <a href="/pages/track-order">
                            <i data-lucide="package-search"></i>
                            <span>TRACK ORDER</span>
                        </a>

                        <a href="{{ route('login') }}">
                            <i data-lucide="user-round"></i>
                            <span>LOG IN</span>
                        </a>

                    </div>

                </div>

            </nav>


            <!-- Navigation Overlay -->
            <div class="nav-overlay" id="navOverlay">
            </div>


            <!-- Header Actions -->
            <div class="header-actions">

                <!-- Search -->
                <button type="button" class="header-action-btn search-toggle" id="searchToggle" aria-label="Search"
                    aria-expanded="false" aria-controls="searchPanel">
                    <i data-lucide="search"></i>
                </button>

                <!-- Account (Desktop only) -->
                <a href="{{ auth()->check() ? route('profile.profile') : route('login') }}"
                    class="header-action-btn header-account-btn d-none d-lg-inline-flex" aria-label="My Account">
                    <i data-lucide="user-round"></i>
                </a>

                <!-- Wishlist (Desktop only) -->
                <a href="{{ route('wishlist') }}"
                    class="header-action-btn header-wishlist-btn d-none d-lg-inline-flex position-relative"
                    aria-label="Wishlist">
                    <i data-lucide="heart"></i>
                    <span class="wishlist-count badge"
                        style="{{ ($wishlistCount ?? 0) > 0 ? 'display: flex;' : 'display: none;' }}">{{ $wishlistCount ?? 0 }}</span>
                </a>

                <!-- Shopping Bag -->
                <a href="{{ route('cart') }}" class="header-action-btn header-cart-btn position-relative"
                    aria-label="Shopping Bag">
                    <i data-lucide="shopping-bag"></i>
                    <span class="cart-count badge"
                        style="{{ ($cartCount ?? 0) > 0 ? 'display: flex;' : 'display: none;' }}">{{ $cartCount ?? 0 }}</span>
                </a>

            </div>

        </div>


        <!-- Search Panel -->
        <div class="search-panel" id="searchPanel">

            <div class="search-inner">


                <!-- Search Input -->
                <div class="search-top">

                    <div class="search-input-wrap">

                        <i data-lucide="search"></i>

                        <input type="search" id="perfumeSearch" placeholder="Search your favourite fragrance..."
                            autocomplete="off">

                        <button type="button" id="searchClear" aria-label="Clear search">

                            <i data-lucide="x"></i>

                        </button>

                    </div>


                    <!-- Close Search -->
                    <button type="button" class="search-close" id="searchClose">

                        Close

                    </button>

                </div>


                <!-- Trending Searches -->
                <div class="trending-section" id="trendingSection">

                    <div class="search-section-heading">

                        <span class="search-eyebrow">
                            DISCOVER SOMETHING SPECIAL
                        </span>

                        <h3>Trending Searches</h3>

                    </div>


                    <div class="trending-tags">

                        <button type="button" class="trending-tag" data-search="Rose Éternelle">

                            Rose Éternelle

                        </button>


                        <button type="button" class="trending-tag" data-search="Oud Royale">

                            Oud Royale

                        </button>


                        <button type="button" class="trending-tag" data-search="Noir Mystique">

                            Noir Mystique

                        </button>


                        <button type="button" class="trending-tag" data-search="Bois Doré">

                            Bois Doré

                        </button>

                    </div>

                </div>


                <!-- Recommended Products -->
                <div class="recommended-section">

                    <div class="search-section-heading recommended-heading">

                        <div>

                            <span class="search-eyebrow">
                                HANDPICKED FOR YOU
                            </span>

                            <h3 id="resultsHeading">
                                Recommended For You
                            </h3>

                        </div>

                        <span class="results-count" id="resultsCount">
                        </span>

                    </div>


                    <!-- Search Product Results -->
                    <div class="search-product-grid" id="searchProductGrid">
                    </div>


                    <!-- No Results -->
                    <div class="search-no-results" id="searchNoResults">

                        <i data-lucide="search-x"></i>

                        <h3>No fragrances found</h3>

                        <p>
                            Try another name or discover our trending fragrances.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </header>
