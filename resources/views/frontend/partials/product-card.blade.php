{{-- Unified Product Card (Al Rihan standard classes) --}}
@php
    $cardProduct = $product ?? ($item ?? $rel);

    $mrp = (float) ($cardProduct->price ?? 0);
    $sellingPrice = (float) ($cardProduct->selling_price ?? 0);
    $discount = (int) ($cardProduct->discount_percent ?? ($cardProduct->discount ?? 0));

    if (($sellingPrice <= 0 || $sellingPrice >= $mrp) && $discount > 0 && $mrp > 0) {
        $sellingPrice = max(0, round($mrp - ($mrp * $discount) / 100, 2));
    }

    if ($sellingPrice <= 0) {
        $sellingPrice = $mrp;
    }

    if ($mrp > $sellingPrice && $discount <= 0) {
        $discount = (int) round((($mrp - $sellingPrice) / $mrp) * 100);
    }

    $mainImage = !empty($cardProduct->thumbnail)
        ? asset('backend/assets/images/product/' . $cardProduct->thumbnail)
        : asset('frontend/assets/images/products/1.jpg');

    $gallery = is_array($cardProduct->gallery)
        ? $cardProduct->gallery
        : (json_decode($cardProduct->gallery ?? '[]', true) ?:
        []);

    $hoverImage = !empty($gallery[0]) ? asset('backend/assets/images/product/' . $gallery[0]) : $mainImage;

    $productRating = (float) ($cardProduct->rating ?? ($cardProduct->averageRating() ?? 0));

    $isWishlisted = in_array((int) $cardProduct->id, array_map('intval', (array) ($wishlistIds ?? [])), true);

    $gender = match (strtolower((string) $cardProduct->gender)) {
        'men' => 'Men',
        'women' => 'Women',
        'both', 'unisex' => 'Unisex',
        default => 'Unisex',
    };

    $productMeta = !empty($cardProduct->model_no)
        ? $cardProduct->model_no
        : (!empty($cardProduct->specification)
            ? strip_tags($cardProduct->specification)
            : '');

    $isOutOfStock = isset($cardProduct->quantity) && (int) $cardProduct->quantity <= 0;
@endphp

<div class="product-card">

    <!-- Product Image -->
    <div class="product-image">

        <a href="{{ route('product.details', $cardProduct->slug) }}" class="product-image-wrapper">

            <img src="{{ $mainImage }}" alt="{{ $cardProduct->name }}" class="main-image" loading="lazy">

            <img src="{{ $hoverImage }}" alt="{{ $cardProduct->name }} alternate view" class="hover-image"
                loading="lazy">
        </a>

        @if ($productRating > 0)
            <span class="product-badge rating">
                ★ {{ number_format($productRating, 1) }}
            </span>
        @elseif(!empty($cardProduct->is_new_arrival) || !empty($cardProduct->is_new))
            <span class="product-badge new">New</span>
        @endif

        <!-- Wishlist -->
        <button class="wishlist-btn js-wishlist-toggle {{ $isWishlisted ? 'is-active' : '' }}" type="button"
            data-product-id="{{ $cardProduct->id }}" data-toggle-url="{{ route('wishlist.toggle', $cardProduct->id) }}"
            aria-label="{{ $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}">

            <i data-lucide="heart"></i>
        </button>

    </div>

    <!-- Product Information -->
    <div class="product-info">

        <h3>
            <a href="{{ route('product.details', $cardProduct->slug) }}">
                {{ $cardProduct->name }}
            </a>
        </h3>

        <p class="product-meta">
            {{ $gender }}

            {{-- @if ($productMeta)
                &nbsp; | &nbsp;
                {{ \Illuminate\Support\Str::limit($productMeta, 35) }}
            @endif --}}
        </p>

        <!-- Price -->
        <div class="product-price">
            <strong>
                ₹{{ number_format($sellingPrice) }}
            </strong>

            @if ($mrp > $sellingPrice)
                <del>
                    ₹{{ number_format($mrp) }}
                </del>

                <span>{{ $discount }}%</span>
            @endif
        </div>

        <!-- Add to Cart -->
        <button class="add-cart-btn js-rel-add-cart" type="button" data-product-id="{{ $cardProduct->id }}"
            data-url="{{ route('cart.add', $cardProduct->id) }}" @if ($isOutOfStock) disabled @endif>

            <i data-lucide="shopping-cart"></i>
            {{ $isOutOfStock ? 'Out of Stock' : 'Add to Cart' }}
        </button>

    </div>

</div>
