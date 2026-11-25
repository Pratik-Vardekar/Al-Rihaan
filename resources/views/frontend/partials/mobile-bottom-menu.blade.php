<nav class="mobile-bottom-menu" id="mobileBottomMenu" aria-label="Mobile Navigation">
    {{-- Home --}}
    <a href="{{ route('home') }}" class="mobile-bottom-menu__item {{ request()->routeIs('home') ? 'active' : '' }}"
        aria-label="Home">
        <div class="mobile-bottom-menu__icon-wrap">
            <i data-lucide="home"></i>
        </div>
        <span class="mobile-bottom-menu__label">Home</span>
    </a>

    {{-- Shop --}}
    <a href="{{ route('product.listing') }}"
        class="mobile-bottom-menu__item {{ request()->routeIs('product.listing', 'category.show', 'best.sellers', 'new.arrivals', 'combo.offers', 'gifting') ? 'active' : '' }}"
        aria-label="Shop">
        <div class="mobile-bottom-menu__icon-wrap">
            <i data-lucide="compass"></i>
        </div>
        <span class="mobile-bottom-menu__label">Shop</span>
    </a>

    {{-- Wishlist --}}
    <a href="{{ route('wishlist') }}"
        class="mobile-bottom-menu__item {{ request()->routeIs('wishlist') ? 'active' : '' }}" aria-label="Wishlist">
        <div class="mobile-bottom-menu__icon-wrap">
            <i data-lucide="heart"></i>
            <span class="mobile-bottom-menu__badge wishlist-count"
                style="{{ ($wishlistCount ?? 0) > 0 ? 'display: flex;' : 'display: none;' }}">
                {{ $wishlistCount ?? 0 }}
            </span>
        </div>
        <span class="mobile-bottom-menu__label">Wishlist</span>
    </a>

    {{-- Account --}}
    <a href="{{ auth()->check() ? route('profile.profile') : route('login') }}"
        class="mobile-bottom-menu__item {{ request()->routeIs('login', 'register', 'profile.*', 'frontend.order.*') ? 'active' : '' }}"
        aria-label="Account">
        <div class="mobile-bottom-menu__icon-wrap">
            <i data-lucide="user-round"></i>
        </div>
        <span class="mobile-bottom-menu__label">Account</span>
    </a>
</nav>
