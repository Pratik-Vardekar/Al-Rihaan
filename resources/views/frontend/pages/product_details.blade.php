@extends('layouts.front')

@section('title', ($product->name ?? 'Fragrance') . ' | Al Rihan Luxury Perfumes')

@section('content')
    @php
        $mrp = (float) ($product->price ?? 0);
        $sellingPrice = (float) ($product->selling_price ?? 0);
        $storedDiscount = (int) ($product->discount_percent ?? ($product->discount ?? 0));

        if (($sellingPrice <= 0 || $sellingPrice >= $mrp) && $storedDiscount > 0 && $mrp > 0) {
            $sellingPrice = max(0, round($mrp - $mrp * ($storedDiscount / 100), 2));
        }
        if ($sellingPrice <= 0) {
            $sellingPrice = $mrp;
        }

        $hasDiscount = ($mrp > 0 && $sellingPrice > 0 && $mrp > $sellingPrice) || ($storedDiscount > 0 && $mrp > 0);
        $discount = 0;
        if ($hasDiscount) {
            $discount = $storedDiscount > 0 ? $storedDiscount : (int) round((($mrp - $sellingPrice) / $mrp) * 100);
        }

        // Main image
        $mainImg = $product->thumbnail
            ? asset('backend/assets/images/product/' . $product->thumbnail)
            : asset('frontend/assets/images/products/1.jpg');

        // Gallery Images List
        $galleryImages = [];
        $galleryImages[] = $mainImg;

        $rawGallery = is_array($product->gallery) ? $product->gallery : json_decode($product->gallery ?? '[]', true);
        if (!empty($rawGallery)) {
            foreach ($rawGallery as $gImg) {
                if ($gImg) {
                    $url = asset('backend/assets/images/product/' . $gImg);
                    if (!in_array($url, $galleryImages, true)) {
                        $galleryImages[] = $url;
                    }
                }
            }
        }

        $rawGalleryOrder = is_array($product->gallery_order)
            ? $product->gallery_order
            : json_decode($product->gallery_order ?? '[]', true);
        if (!empty($rawGalleryOrder)) {
            foreach ($rawGalleryOrder as $gImg) {
                if ($gImg) {
                    $url = asset('backend/assets/images/product/' . $gImg);
                    if (!in_array($url, $galleryImages, true)) {
                        $galleryImages[] = $url;
                    }
                }
            }
        }

        $isWishlisted = in_array((int) $product->id, (array) ($wishlistIds ?? []), true);
        $rating = (float) ($averageRatingValue ?? ($product->rating ?? 0));
        $ratingFormatted = $rating > 0 ? number_format($rating, 1) : '4.8';
        $totalReviewsCount = (int) ($reviewCount ?? ($product->reviews_count ?? 0));

        $inStock = (int) ($product->quantity ?? 0) > 0 || (int) ($product->stock ?? 1) > 0;
        $categoryName = optional($product->category)->name ?? (optional($product->brand)->name ?? 'LUXURY PERFUMES');

        $genderLabel = match (strtolower((string) $product->gender)) {
            'women' => 'Women',
            'men' => 'Men',
            'both', 'unisex' => 'Unisex',
            default => 'Unisex',
        };

        // Dynamic size calculations: use database variants when variable product has variants, fallback to standard sizes
        $dbVariants =
            $product->relationLoaded('variants') && $product->variants
                ? $product->variants->where('status', 1)->values()
                : collect();

        $sizes = [];

        if ($dbVariants->isNotEmpty()) {
            foreach ($dbVariants as $idx => $v) {
                $sizeAttr = $v->variantAttributes
                    ? $v->variantAttributes->first(function ($va) {
                        return strcasecmp(optional($va->attribute)->name ?? '', 'size') === 0;
                    })
                    : null;
                $sizeName = $sizeAttr
                    ? optional($sizeAttr->attributeValue)->value
                    : ($v->sku ?:
                    'Variant ' . ($idx + 1));

                $vMrp = (float) ($v->price > 0 ? $v->price : $v->selling_price);
                $vSelling = (float) ($v->selling_price > 0 ? $v->selling_price : $v->price);
                if ($vSelling <= 0) {
                    $vSelling = $vMrp;
                }

                $vDiscountStr = '';
                if ($vMrp > $vSelling && $vMrp > 0) {
                    $pct = (int) round((($vMrp - $vSelling) / $vMrp) * 100);
                    $vDiscountStr = $pct . '% OFF';
                }

                $vImg = $v->image ? asset('backend/assets/images/product/' . $v->image) : null;

                $sizes[] = [
                    'id' => $v->id,
                    'ml' => $sizeName,
                    'price' => (int) round($vSelling),
                    'old_price' => (int) round($vMrp),
                    'discount' => $vDiscountStr ?: ($discount > 0 ? $discount . '% OFF' : ''),
                    'stock' => (int) ($v->quantity ?? 0),
                    'sku' => $v->sku,
                    'image' => $vImg,
                    'active' => $idx === 0,
                ];
            }
        }

        $hasVariants = !empty($sizes);
        $actualProductStock = (int) ($product->quantity ?? 0);

        if ($hasVariants) {
            $initialSize = $sizes[0]['ml'];
            $initialPrice = $sizes[0]['price'];
            $initialOldPrice = $sizes[0]['old_price'];
            $initialDiscount = $sizes[0]['discount'];
            $initialVariantId = $sizes[0]['id'] ?? null;
            $initialStock = isset($sizes[0]['stock']) ? (int) $sizes[0]['stock'] : $actualProductStock;
        } else {
            $initialSize = null;
            $initialPrice = (int) round($sellingPrice > 0 ? $sellingPrice : $mrp);
            $initialOldPrice = (int) round($mrp);
            $initialDiscount = ($mrp > $sellingPrice && $discount > 0) ? ($discount . '% OFF') : '';
            $initialVariantId = null;
            $initialStock = $actualProductStock;
        }

        $inStock = $initialStock > 0 || $actualProductStock > 0;
        $maxQty = $initialStock > 0 ? $initialStock : ($actualProductStock > 0 ? $actualProductStock : 1);
    @endphp

    <section class="product-details-page">

        <div class="container">
            <!-- BREADCRUMB -->
            <div class="product-breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <span>/</span>
                @if ($product->category)
                    <a href="{{ route('category.show', $product->category->slug) }}">{{ $product->category->name }}</a>
                @else
                    <a href="{{ route('product.listing') }}">Perfumes</a>
                @endif
                <span>/</span>
                <span>{{ $product->name }}</span>
            </div>

            <!-- PRODUCT MAIN -->
            <div class="product-details-container">
                <div style="width:100%;
                        overflow:hidden">
                    <!-- LEFT: PRODUCT GALLERY -->
                    <div class="product-gallery">

                        <div class="product-main-image">
                            @if (!empty($product->is_best_selling))
                                <span class="product-image-badge">BEST SELLER</span>
                            @elseif (!empty($product->is_new_arrival))
                                <span class="product-image-badge">NEW ARRIVAL</span>
                            @elseif (!empty($product->badge))
                                <span class="product-image-badge">{{ strtoupper($product->badge) }}</span>
                            @endif

                            <button
                                class="product-gallery-wishlist js-wishlist-toggle {{ $isWishlisted ? 'is-active' : '' }}"
                                type="button" data-product-id="{{ $product->id }}"
                                data-toggle-url="{{ route('wishlist.toggle', $product->id) }}"
                                aria-label="{{ $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}">
                                <i data-lucide="heart"></i>
                            </button>

                            <img id="mainProductImage" src="{{ $mainImg }}" alt="{{ $product->name }}">
                        </div>

                        @if (count($galleryImages) > 1)
                            <div class="product-thumbnails-wrapper">
                                <button class="thumb-nav-btn thumb-prev" type="button" aria-label="Previous thumbnail">
                                    <i data-lucide="chevron-left"></i>
                                </button>
                                <div class="swiper productThumbnailsSwiper">
                                    <div class="swiper-wrapper product-thumbnails">
                                        @foreach ($galleryImages as $index => $imgUrl)
                                            <div class="swiper-slide">
                                                <button class="thumbnail {{ $index === 0 ? 'active' : '' }}" type="button"
                                                    data-image="{{ $imgUrl }}"
                                                    aria-label="View image {{ $index + 1 }}">
                                                    <img src="{{ $imgUrl }}"
                                                        alt="{{ $product->name }} view {{ $index + 1 }}">
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <button class="thumb-nav-btn thumb-next" type="button" aria-label="Next thumbnail">
                                    <i data-lucide="chevron-right"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                </div>

                <!-- RIGHT: STICKY PRODUCT INFORMATION -->
                <div class="product-details-info">

                    <div class="product-detail-topline">
                        <span class="product-category">{{ strtoupper($categoryName) }}</span>
                        <span class="product-stock">
                            @if ($inStock)
                                <span></span> In Stock
                            @else
                                <span class="out-of-stock-dot"></span> Out of Stock
                            @endif
                        </span>
                    </div>

                    <h1 class="product-detail-title">{{ $product->name }}</h1>

                    <div class="product-detail-rating">
                        <div class="rating-stars">
                            @php
                                $fullStars = floor($rating);
                                $hasHalf = $rating - $fullStars >= 0.5;
                            @endphp
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= $fullStars)
                                    ★
                                @elseif ($i == $fullStars + 1 && $hasHalf)
                                    ★
                                @else
                                    ☆
                                @endif
                            @endfor
                        </div>
                        <span>{{ $ratingFormatted }}</span>
                        <a href="#product-reviews">({{ $totalReviewsCount }}
                            {{ \Illuminate\Support\Str::plural('Review', $totalReviewsCount) }})</a>
                    </div>

                    <div class="product-detail-price">
                        <span class="current-price">₹{{ number_format($initialPrice) }}</span>
                        @if ($initialOldPrice > $initialPrice)
                            <span class="old-price">₹{{ number_format($initialOldPrice) }}</span>
                        @endif
                        @if (!empty($initialDiscount))
                            <span class="discount-label">{{ $initialDiscount }}</span>
                        @endif
                    </div>

                    <p class="product-short-description">
                        @if (!empty($product->details))
                            {{ strip_tags($product->details) }}
                        @elseif (!empty($product->description))
                            {{ \Illuminate\Support\Str::limit(strip_tags($product->description), 500) }}
                        @else
                            Experience the richness of premium oud with a sophisticated fragrance crafted for those who
                            appreciate luxury and elegance.
                        @endif
                    </p>

                    <div class="product-detail-divider"></div>

                    @if ($hasVariants)
                        <!-- SIZE SELECTION -->
                        <div class="product-variant-section">

                            <div class="variant-heading">
                                <span>Select Your Size</span>
                                <span class="selected-size-label">{{ $initialSize }}</span>
                            </div>

                            <div class="product-ml-options">
                                @foreach ($sizes as $size)
                                    <button type="button" class="ml-option {{ $size['active'] ? 'active' : '' }}"
                                        data-ml="{{ $size['ml'] }}" data-price="₹{{ number_format($size['price']) }}"
                                        data-old-price="₹{{ number_format($size['old_price']) }}"
                                        data-discount="{{ $size['discount'] }}" data-variant-id="{{ $size['id'] ?? '' }}"
                                        data-stock="{{ $size['stock'] }}" data-sku="{{ $size['sku'] ?? '' }}"
                                        data-image="{{ $size['image'] ?? '' }}">
                                        <span>{{ $size['ml'] }}</span>
                                        <small>₹{{ number_format($size['price']) }}</small>
                                    </button>
                                @endforeach
                            </div>

                        </div>
                    @endif

                    <input type="hidden" id="selectedProductML" value="{{ $initialSize ?? '' }}">
                    <input type="hidden" id="selectedVariantId" value="{{ $initialVariantId ?? '' }}">

                    <!-- PRODUCT HIGHLIGHTS -->
                    <div class="product-highlights">

                        <div class="highlight-item">
                            <i data-lucide="droplets"></i>
                            <span>Long Lasting Fragrance</span>
                        </div>

                        <div class="highlight-item">
                            <i data-lucide="sparkles"></i>
                            <span>Premium Quality</span>
                        </div>

                        <div class="highlight-item">
                            <i data-lucide="gift"></i>
                            <span>Perfect For Gifting</span>
                        </div>

                    </div>

                    <!-- QUANTITY -->
                    <div class="product-purchase-row">

                        <div class="product-quantity">
                            <button type="button" id="quantityMinus" class="qty-btn minus" aria-label="Decrease quantity"
                                disabled style="opacity: 0.45; cursor: not-allowed;">
                                <i data-lucide="minus"></i>
                            </button>

                            <input type="number" id="productQuantity" name="quantity" value="1" min="1"
                                max="{{ $maxQty }}" data-stock="{{ $maxQty }}" aria-label="Quantity" readonly>

                            <button type="button" id="quantityPlus" class="qty-btn plus" aria-label="Increase quantity"
                                {{ $maxQty <= 1 ? 'disabled style="opacity: 0.45; cursor: not-allowed;"' : '' }}>
                                <i data-lucide="plus"></i>
                            </button>
                        </div>

                        <button type="button" class="product-add-cart js-detail-add-cart"
                            data-product-id="{{ $product->id }}" data-url="{{ route('cart.add', $product->id) }}"
                            {{ !$inStock ? 'disabled' : '' }}>
                            {{ $inStock ? 'Add To Cart' : 'Out of Stock' }}
                        </button>

                        <button type="button" class="product-buy-now js-detail-buy-now"
                            data-buy-url="{{ route('checkout.buy', $product->id) }}" {{ !$inStock ? 'disabled' : '' }}>
                            Buy Now
                        </button>

                    </div>

                    <!-- DELIVERY INFORMATION -->
                    <div class="product-delivery-info">

                        <div class="delivery-item">
                            <i data-lucide="truck"></i>
                            <div>
                                <strong>Free Shipping</strong>
                                <span>On eligible orders</span>
                            </div>
                        </div>

                        <div class="delivery-item">
                            <i data-lucide="shield-check"></i>
                            <div>
                                <strong>Secure Payment</strong>
                                <span>100% secure checkout</span>
                            </div>
                        </div>

                        <div class="delivery-item">
                            <i data-lucide="refresh-cw"></i>
                            <div>
                                <strong>Easy Support</strong>
                                <span>We're here to help</span>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- PRODUCT DESCRIPTION -->
            <section class="product-description-section" id="product-reviews">

                <div class="product-description-tabs">
                    <button class="description-tab active" data-tab="description" type="button">
                        Description
                    </button>

                    <button class="description-tab" data-tab="details" type="button">
                        Product Details
                    </button>

                    <button class="description-tab" data-tab="reviews" type="button">
                        Reviews ({{ $totalReviewsCount }})
                    </button>
                </div>

                <div class="description-content active" id="description">
                    <h3>About {{ $product->name }}</h3>
                    @if (!empty($product->description))
                        <div class="description-body">
                            {!! $product->description !!}
                        </div>
                    @elseif (!empty($product->details))
                        <div class="description-body">
                            {!! $product->details !!}
                        </div>
                    @else
                        <p>
                            Discover the luxurious world of {{ $product->name }}. A rich,
                            sophisticated fragrance designed to leave a memorable impression.
                        </p>
                    @endif
                </div>

                <div class="description-content" id="details">
                    <h3>Product Information</h3>
                    @php
                        $rawSpec = (string) ($product->specification ?? '');
                        $specItems = [];

                        if (!empty(trim($rawSpec))) {
                            $decodedSpec = html_entity_decode($rawSpec, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                            if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $decodedSpec, $matches)) {
                                $specItems = $matches[1];
                        } elseif (preg_match('/<p[^>]*>|<br\s*\/@endphp/i', $decodedSpec)) {
        $normalized = preg_replace('/<br\s*\/?>/i', "\n", $decodedSpec);
        $normalized = preg_replace('/<\/p>/i', "\n", $normalized);
        $lines = explode("\n", strip_tags($normalized));
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $specItems[] = $t;
            }
        }
    } else {
        $lines = preg_split('/\r\n|\r|\n/', strip_tags($decodedSpec));
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $specItems[] = $t;
            }
        }
    }

    $cleanedSpecItems = [];
    foreach ($specItems as $item) {
        $text = trim(strip_tags($item, '<strong><b><em><i><span>'));
        $text = preg_replace('/^(\d+[\.\)]\s*|\(\d+\)\s*|[•\-\*]\s*)/u', '', $text);
        $text = trim($text);
        if ($text !== '') {
            $cleanedSpecItems[] = $text;
        }
    }
    $specItems = $cleanedSpecItems;
}
?>

                    @if (!empty($specItems))
                        <div class="specification-body mt-3">
                            <h4 class="specification-title">Fragrance Notes & Specifications</h4>
                            <div class="specification-simple-list">
                                @foreach ($specItems as $idx => $spec)
                                    @php
                                        $specText = $spec;
                                        if (
                                            str_contains($specText, ':') &&
                                            !str_starts_with(trim($specText), '<strong') &&
                                            !str_starts_with(trim($specText), '<b>')
                                        ) {
                                            $colonPos = strpos($specText, ':');
                                            $specKey = substr($specText, 0, $colonPos);
                                            $specVal = substr($specText, $colonPos + 1);
                                            $formattedSpec =
                                                '<strong class="spec-key">' .
                                                htmlspecialchars(trim($specKey)) .
                                                ':</strong> <span class="spec-val">' .
                                                htmlspecialchars(trim($specVal)) .
                                                '</span>';
                                        } else {
                                            $formattedSpec = '<span class="spec-val">' . $specText . '</span>';
                                        }
                                    @endphp
                                    <div class="spec-simple-row">
                                        <span class="spec-simple-num">{{ $idx + 1 }}.</span>
                                        <div class="spec-simple-content">
                                            {!! $formattedSpec !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif (!empty($product->specification))
                        <div class="specification-body mt-3">
                            <h4 class="specification-title">Fragrance Notes & Specifications</h4>
                            <div class="specification-raw-text">
                                {!! nl2br(e($product->specification)) !!}
                            </div>
                        </div>
                    @else
                        <p class="text-muted mt-3">No specifications provided for this product.</p>
                    @endif
                </div>

                <div class="description-content" id="reviews">
                    <h3>Customer Reviews</h3>

                    @if (!empty($reviews) && $reviews->isNotEmpty())
                        <div class="reviews-list mb-4">
                            @foreach ($reviews as $rev)
                                @php
                                    $revImg = $rev->review_image_url;
                                    $revFallback = $rev->image
                                        ? asset('backend/assets/images/review/' . basename($rev->image))
                                        : '';
                                @endphp
                                <div class="review-item p-3 mb-3 border rounded-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="reviewer-meta">
                                            <strong
                                                class="text-dark">{{ optional($rev->user)->name ?? 'Customer' }}</strong>
                                            <div class="review-stars">
                                                @for ($s = 1; $s <= 5; $s++)
                                                    {{ $s <= (int) $rev->rating ? '★' : '☆' }}
                                                @endfor
                                            </div>
                                        </div>
                                        <small
                                            class="text-muted">{{ $rev->created_at ? $rev->created_at->format('M d, Y') : '' }}</small>
                                    </div>
                                    <p class="review-text mb-2 text-secondary">{{ $rev->review }}</p>
                                    @if ($revImg)
                                        <div class="review-photo-wrapper mt-2">
                                            <a href="{{ $revImg }}" target="_blank" title="View customer photo">
                                                <img src="{{ $revImg }}" alt="Customer review photo"
                                                    class="review-photo-thumb"
                                                    onerror="if(this.src!=='{{ $revFallback }}'&&'{{ $revFallback }}'){this.src='{{ $revFallback }}';}">
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-4">Customer reviews will appear here.</p>
                    @endif

                    @auth
                        @if (!($userHasReviewed ?? false))
                            <div class="write-review-form p-4 border rounded-3 bg-white mt-4">
                                <h4 class="mb-3">Write a Review</h4>
                                <form action="{{ route('review.store', $product->id) }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Your Rating</label>
                                        <select name="rating" class="form-select form-select-rating" required>
                                            <option value="5">★★★★★ (5 Stars - Excellent)</option>
                                            <option value="4">★★★★☆ (4 Stars - Good)</option>
                                            <option value="3">★★★☆☆ (3 Stars - Average)</option>
                                            <option value="2">★★☆☆☆ (2 Stars - Fair)</option>
                                            <option value="1">★☆☆☆☆ (1 Star - Poor)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Review Title (Optional)</label>
                                        <input type="text" name="title" class="form-control"
                                            placeholder="Summarize your review">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Your Feedback <span
                                                class="text-danger">*</span></label>
                                        <textarea name="comment" class="form-control" rows="4"
                                            placeholder="How did this fragrance smell and perform for you?" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Upload Photo (Optional)</label>
                                        <input type="file" name="image" id="reviewImageInput" class="form-control"
                                            accept="image/jpeg,image/png,image/jpg,image/webp">
                                        <small class="text-muted d-block mt-1">Upload a photo of your perfume or bottle (Max:
                                            5MB)</small>
                                        <div id="reviewImagePreview" class="mt-2" style="display: none;">
                                            <img id="reviewPreviewImg" src="" alt="Preview"
                                                style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;">
                                        </div>
                                    </div>
                                    <button type="submit" class="apply-filters btn-submit-review">
                                        Submit Review
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="alert alert-success mt-3">
                                Thank you! You have already reviewed this fragrance.
                            </div>
                        @endif
                    @else
                        <div class="review-login-prompt p-3 border rounded-3 bg-light text-center mt-3">
                            <p class="mb-2 text-muted">Please sign in to write a review for this product.</p>
                            <a href="{{ route('login') }}" class="apply-filters btn-login-review">
                                Log In to Review
                            </a>
                        </div>
                    @endauth
                </div>

            </section>
        </div>

        <!-- YOU MAY ALSO LIKE / RELATED PRODUCTS -->
        @if (!empty($related_products) && $related_products->isNotEmpty())
            <section class="bestseller-section section-container" id="bestsellers">

                <!-- Section Heading -->
                <div class="bestseller-heading reveal">

                    <span class="section-subtitle">
                        CURATED FOR YOU
                    </span>

                    <h2 class="section-heading">
                        You May Also Like
                    </h2>

                </div>

                <!-- Product Slider -->
                <div class="bestseller-slider-wrapper">

                    <div class="swiper bestsellerSwiper">
                        <div class="swiper-wrapper">

                            @foreach ($related_products as $rel)
                                <div class="swiper-slide">
                                    @include('frontend.partials.product-card', ['product' => $rel])
                                </div>
                            @endforeach

                        </div>
                    </div>

                </div>

                <!-- Slider Controls -->
                <div class="bestseller-controls">

                    <button class="bestseller-prev" aria-label="Previous products" type="button">
                        <i data-lucide="chevron-left"></i>
                    </button>

                    <div class="bestseller-pagination"></div>

                    <button class="bestseller-next" aria-label="Next products" type="button">
                        <i data-lucide="chevron-right"></i>
                    </button>

                </div>

                <!-- Explore All -->
                <div class="explore-wrapper">

                    <a href="{{ route('product.listing') }}" class="explore-btn">
                        Explore All
                    </a>

                </div>

            </section>
        @endif

    </section>
@endsection

@push('styles')
    <style>
        .specification-body {
            margin-top: 1.25rem;
        }

        .specification-title {
            font-family: "Playfair Display", serif;
            font-size: 19px;
            font-weight: 600;
            color: #34271e;
            margin-bottom: 16px;
            letter-spacing: 0.3px;
        }

        /* Clean Simple Numbered List */
        .specification-simple-list {
            display: flex;
            flex-direction: column;
            margin-top: 10px;
            background: #ffffff;
            border: 1px solid #ebdccf;
            border-radius: 8px;
            overflow: hidden;
            max-width: 680px;
        }

        .spec-simple-row {
            display: flex;
            align-items: baseline;
            gap: 12px;
            padding: 11px 16px;
            border-bottom: 1px solid #f3ece4;
            font-size: 14.5px;
            line-height: 1.5;
        }

        .spec-simple-row:nth-child(even) {
            background: #faf7f2;
        }

        .spec-simple-row:last-child {
            border-bottom: none;
        }

        .spec-simple-num {
            font-weight: 700;
            color: #a47b45;
            min-width: 22px;
            font-size: 14px;
        }

        .spec-simple-content {
            color: #4a3e36;
            flex-grow: 1;
        }

        .spec-simple-content .spec-key {
            color: #241913;
            font-weight: 600;
        }

        .spec-simple-content .spec-val {
            color: #554a42;
        }

        /* Review Photo Attachments */
        .review-photo-wrapper {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .review-photo-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #dfd5c8;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .review-photo-thumb:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }

        @media (max-width: 576px) {
            .spec-simple-row {
                padding: 9px 12px;
                gap: 8px;
                font-size: 13.5px;
            }

            .review-photo-thumb {
                width: 65px;
                height: 65px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // ===== SIZE OPTION DYNAMIC OVERRIDE =====
            const mlButtons = document.querySelectorAll('.product-ml-options .ml-option');
            const currentPriceEl = document.querySelector('.product-detail-price .current-price');
            const oldPriceEl = document.querySelector('.product-detail-price .old-price');
            const discountLabelEl = document.querySelector('.product-detail-price .discount-label');
            const selectedSizeLabel = document.querySelector('.selected-size-label');
            const selectedMLInput = document.getElementById('selectedProductML');
            const selectedVariantIdInput = document.getElementById('selectedVariantId');
            const mainProductImg = document.getElementById('mainProductImage');
            var updateQtyButtons = function() {};

            mlButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    mlButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const price = this.getAttribute('data-price');
                    const oldPrice = this.getAttribute('data-old-price');
                    const discount = this.getAttribute('data-discount');
                    const ml = this.getAttribute('data-ml');
                    const variantId = this.getAttribute('data-variant-id');
                    const stock = parseInt(this.getAttribute('data-stock'), 10);
                    const image = this.getAttribute('data-image');

                    if (price && currentPriceEl) currentPriceEl.textContent = price;
                    if (oldPrice && oldPriceEl) oldPriceEl.textContent = oldPrice;
                    if (discount && discountLabelEl) discountLabelEl.textContent = discount;
                    if (ml && selectedSizeLabel) selectedSizeLabel.textContent = ml;
                    if (ml && selectedMLInput) selectedMLInput.value = ml;
                    if (selectedVariantIdInput) selectedVariantIdInput.value = variantId || '';

                    // If variant has its own image, update the main image & sync thumbnail
                    if (image && mainProductImg) {
                        mainProductImg.src = image;
                        if (typeof window.setActiveThumbByUrl === 'function') {
                            window.setActiveThumbByUrl(image);
                        }
                    }

                    // Stock check
                    const addCartBtn = document.querySelector('.js-detail-add-cart');
                    const buyNowBtn = document.querySelector('.js-detail-buy-now');
                    const availableStock = (!isNaN(stock) && stock >= 0) ? stock : 0;

                    if (availableStock <= 0) {
                        if (addCartBtn) {
                            addCartBtn.disabled = true;
                            addCartBtn.textContent = 'Out of Stock';
                        }
                        if (buyNowBtn) buyNowBtn.disabled = true;
                        if (qtyInput) {
                            qtyInput.setAttribute('max', 1);
                            qtyInput.setAttribute('data-stock', 0);
                            qtyInput.value = 1;
                        }
                    } else {
                        if (addCartBtn) {
                            addCartBtn.disabled = false;
                            addCartBtn.textContent = 'Add To Cart';
                        }
                        if (buyNowBtn) buyNowBtn.disabled = false;
                        if (qtyInput) {
                            // Exact backend stock quantity (e.g. 6), no extra!
                            qtyInput.setAttribute('max', availableStock);
                            qtyInput.setAttribute('data-stock', availableStock);
                            if (parseInt(qtyInput.value, 10) > availableStock) {
                                qtyInput.value = availableStock;
                            }
                        }
                    }
                    if (typeof updateQtyButtons === 'function') {
                        updateQtyButtons();
                    }
                });
            });

            // ===== PRODUCT THUMBNAILS SWIPER CAROUSEL =====
            const thumbSwiperEl = document.querySelector('.productThumbnailsSwiper');
            let thumbSwiper = null;
            if (thumbSwiperEl && typeof Swiper !== 'undefined') {
                const wrapper = thumbSwiperEl.closest('.product-thumbnails-wrapper');
                thumbSwiper = new Swiper(thumbSwiperEl, {
                    slidesPerView: 4,
                    spaceBetween: 10,
                    speed: 400,
                    watchOverflow: true,
                    slideToClickedSlide: true,
                    navigation: {
                        nextEl: '.thumb-nav-btn.thumb-next',
                        prevEl: '.thumb-nav-btn.thumb-prev',
                    },
                    on: {
                        init: function() {
                            if (wrapper) {
                                if (this.isLocked) {
                                    wrapper.classList.add('is-locked');
                                } else {
                                    wrapper.classList.remove('is-locked');
                                }
                            }
                        },
                        lock: function() {
                            if (wrapper) wrapper.classList.add('is-locked');
                        },
                        unlock: function() {
                            if (wrapper) wrapper.classList.remove('is-locked');
                        }
                    },
                    breakpoints: {
                        320: {
                            slidesPerView: 3.5,
                            spaceBetween: 8
                        },
                        480: {
                            slidesPerView: 4,
                            spaceBetween: 10
                        },
                        768: {
                            slidesPerView: 3.5,
                            spaceBetween: 10
                        },
                        1024: {
                            slidesPerView: 4,
                            spaceBetween: 12
                        }
                    }
                });
            }

            // Thumbnail click listener for updating main product image
            const thumbButtons = document.querySelectorAll('.productThumbnailsSwiper .thumbnail');
            thumbButtons.forEach(function(btn, idx) {
                btn.addEventListener('click', function() {
                    thumbButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const imgUrl = this.getAttribute('data-image');
                    const mainProductImg = document.getElementById('mainProductImage');
                    if (imgUrl && mainProductImg) {
                        mainProductImg.style.opacity = '0';
                        setTimeout(() => {
                            mainProductImg.src = imgUrl;
                            mainProductImg.style.opacity = '1';
                        }, 150);
                    }
                    if (thumbSwiper) {
                        thumbSwiper.slideTo(idx);
                    }
                });
            });

            // Synchronize size variant image with thumbnail carousel
            window.setActiveThumbByUrl = function(url) {
                if (!url) return;
                thumbButtons.forEach(function(btn, idx) {
                    if (btn.getAttribute('data-image') === url) {
                        thumbButtons.forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        if (thumbSwiper) {
                            thumbSwiper.slideTo(idx);
                        }
                    }
                });
            };

            // ===== DESCRIPTION TABS =====
            const descTabs = document.querySelectorAll('.description-tab');
            const descContents = document.querySelectorAll('.description-content');

            descTabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    const target = this.dataset.tab;

                    descTabs.forEach(item => item.classList.remove('active'));
                    descContents.forEach(content => content.classList.remove('active'));

                    this.classList.add('active');

                    const targetContent = document.getElementById(target);
                    if (targetContent) {
                        targetContent.classList.add('active');
                    }
                });
            });

            // ===== QUANTITY PLUS / MINUS =====
            const qtyInput = document.getElementById('productQuantity');
            let qtyMinus = document.getElementById('quantityMinus');
            let qtyPlus = document.getElementById('quantityPlus');

            // Strip any conflicting duplicate click listeners from external scripts (e.g. custom.js)
            if (qtyMinus && qtyMinus.parentNode) {
                const freshMinus = qtyMinus.cloneNode(true);
                qtyMinus.parentNode.replaceChild(freshMinus, qtyMinus);
                qtyMinus = freshMinus;
            }
            if (qtyPlus && qtyPlus.parentNode) {
                const freshPlus = qtyPlus.cloneNode(true);
                qtyPlus.parentNode.replaceChild(freshPlus, qtyPlus);
                qtyPlus = freshPlus;
            }

            if (qtyInput) {
                const getQuantity = function() {
                    const parsed = parseInt(qtyInput.value, 10);
                    return isNaN(parsed) || parsed < 1 ? 1 : parsed;
                };

                const getMaxQuantity = function() {
                    const parsedMax = parseInt(qtyInput.getAttribute('max'), 10);
                    return isNaN(parsedMax) || parsedMax < 1 ? 1 : parsedMax;
                };

                updateQtyButtons = function() {
                    const current = getQuantity();
                    const maxVal = getMaxQuantity();

                    if (qtyMinus) {
                        const isMin = current <= 1;
                        qtyMinus.disabled = isMin;
                        qtyMinus.style.opacity = isMin ? '0.45' : '1';
                        qtyMinus.style.cursor = isMin ? 'not-allowed' : 'pointer';
                    }

                    if (qtyPlus) {
                        const isMax = current >= maxVal;
                        qtyPlus.disabled = isMax;
                        qtyPlus.style.opacity = isMax ? '0.45' : '1';
                        qtyPlus.style.cursor = isMax ? 'not-allowed' : 'pointer';
                    }
                };

                if (qtyMinus) {
                    qtyMinus.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof e.stopImmediatePropagation === 'function') {
                            e.stopImmediatePropagation();
                        }
                        const current = getQuantity();
                        if (current > 1) {
                            qtyInput.value = current - 1;
                        } else {
                            qtyInput.value = 1;
                        }
                        updateQtyButtons();
                        qtyInput.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    });
                }

                if (qtyPlus) {
                    qtyPlus.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof e.stopImmediatePropagation === 'function') {
                            e.stopImmediatePropagation();
                        }
                        const current = getQuantity();
                        const maxVal = getMaxQuantity();
                        if (current < maxVal) {
                            qtyInput.value = current + 1;
                        } else {
                            qtyInput.value = maxVal;
                            if (typeof window.showToast === 'function') {
                                window.showToast('Only ' + maxVal + ' item(s) available in stock',
                                    'warning');
                            }
                        }
                        updateQtyButtons();
                        qtyInput.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    });
                }

                qtyInput.addEventListener('input', function() {
                    let val = parseInt(this.value, 10);
                    const maxVal = getMaxQuantity();
                    if (!isNaN(val) && val > maxVal) {
                        this.value = maxVal;
                        if (typeof window.showToast === 'function') {
                            window.showToast('Only ' + maxVal + ' item(s) available in stock', 'warning');
                        }
                    }
                    updateQtyButtons();
                });

                qtyInput.addEventListener('blur', function() {
                    let val = parseInt(this.value, 10);
                    const maxVal = getMaxQuantity();
                    if (isNaN(val) || val < 1) {
                        this.value = 1;
                    } else if (val > maxVal) {
                        this.value = maxVal;
                    }
                    updateQtyButtons();
                });

                // Initialize button states on page load
                updateQtyButtons();
            }

            // ===== AJAX ADD TO CART (MAIN PRODUCT) =====
            const mainAddCartBtn = document.querySelector('.js-detail-add-cart');
            if (mainAddCartBtn) {
                mainAddCartBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const btn = this;
                    if (btn.getAttribute('data-busy') === '1') return;

                    const addUrl = btn.getAttribute('data-url');
                    const quantity = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
                    const selectedSize = selectedMLInput ? selectedMLInput.value : '';
                    const selectedVariantId = selectedVariantIdInput ? selectedVariantIdInput.value : '';

                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                    if (!addUrl) return;

                    btn.setAttribute('data-busy', '1');
                    btn.disabled = true;
                    const origHtml = btn.innerHTML;
                    btn.innerHTML = '<span>Adding...</span>';

                    fetch(addUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                quantity: quantity,
                                size: selectedSize,
                                variant_id: selectedVariantId
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            btn.removeAttribute('data-busy');
                            btn.disabled = false;
                            btn.innerHTML = origHtml;

                            if (data.status || data.success) {
                                if (typeof window.showToast === 'function') {
                                    window.showToast(data.message || 'Added to bag successfully!',
                                        'success');
                                }
                                if (typeof window.updateCartBadge === 'function' && typeof data
                                    .cart_count !== 'undefined') {
                                    window.updateCartBadge(data.cart_count);
                                }
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast(data.message || 'Could not add to bag.', 'error');
                                }
                            }
                        })
                        .catch(err => {
                            btn.removeAttribute('data-busy');
                            btn.disabled = false;
                            btn.innerHTML = origHtml;
                            if (typeof window.showToast === 'function') {
                                window.showToast('Something went wrong. Please try again.', 'error');
                            }
                        });
                });
            }

            // ===== BUY NOW =====
            const buyNowBtn = document.querySelector('.js-detail-buy-now');
            if (buyNowBtn) {
                buyNowBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const baseUrl = this.getAttribute('data-buy-url');
                    const quantity = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
                    const selectedSize = selectedMLInput ? selectedMLInput.value : '';
                    const selectedVariantId = selectedVariantIdInput ? selectedVariantIdInput.value : '';

                    if (!baseUrl) return;

                    const url = new URL(baseUrl, window.location.origin);
                    url.searchParams.set('quantity', quantity);
                    if (selectedSize) {
                        url.searchParams.set('size', selectedSize);
                    }
                    if (selectedVariantId) {
                        url.searchParams.set('variant_id', selectedVariantId);
                    }

                    // Pass active variant price or base product price
                    const activeOption = document.querySelector('.product-ml-options .ml-option.active');
                    if (activeOption) {
                        const rawPrice = activeOption.getAttribute('data-price');
                        if (rawPrice) {
                            url.searchParams.set('price', rawPrice.replace(/[^\d.]/g, ''));
                        }
                    } else if (currentPriceEl) {
                        const rawPrice = currentPriceEl.textContent;
                        if (rawPrice) {
                            url.searchParams.set('price', rawPrice.replace(/[^\d.]/g, ''));
                        }
                    }

                    window.location.href = url.toString();
                });
            }

            // ===== RELATED / COMBO PRODUCTS ADD TO CART =====
            document.querySelectorAll('.js-rel-add-cart').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const btnEl = this;
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
                            body: JSON.stringify({
                                quantity: 1
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            btnEl.removeAttribute('data-busy');
                            btnEl.disabled = false;
                            btnEl.innerHTML = originalHtml;
                            if (typeof lucide !== 'undefined') lucide.createIcons();

                            if (data.status || data.success) {
                                if (typeof window.showToast === 'function') {
                                    window.showToast(data.message || 'Added to cart!',
                                        'success');
                                }
                                if (typeof window.updateCartBadge === 'function' && typeof data
                                    .cart_count !== 'undefined') {
                                    window.updateCartBadge(data.cart_count);
                                }
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast(data.message || 'Could not add to cart.',
                                        'error');
                                }
                            }
                        })
                        .catch(err => {
                            btnEl.removeAttribute('data-busy');
                            btnEl.disabled = false;
                            btnEl.innerHTML = originalHtml;
                            if (typeof lucide !== 'undefined') lucide.createIcons();
                            if (typeof window.showToast === 'function') {
                                window.showToast('Something went wrong. Please try again.',
                                    'error');
                            }
                        });
                });
            });

            // Initialize Swiper if needed
            if (typeof Swiper !== 'undefined') {
                const swiperEl = document.querySelector('.bestsellerSwiper');
                if (swiperEl && !swiperEl.swiper) {
                    new Swiper(swiperEl, {
                        slidesPerView: 4,
                        spaceBetween: 20,
                        speed: 750,
                        grabCursor: true,
                        navigation: {
                            nextEl: '.bestseller-next',
                            prevEl: '.bestseller-prev'
                        },
                        pagination: {
                            el: '.bestseller-pagination',
                            clickable: true
                        },
                        breakpoints: {
                            320: {
                                slidesPerView: 1.2,
                                spaceBetween: 12
                            },
                            480: {
                                slidesPerView: 2,
                                spaceBetween: 15
                            },
                            768: {
                                slidesPerView: 3,
                                spaceBetween: 18
                            },
                            1024: {
                                slidesPerView: 4,
                                spaceBetween: 20
                            }
                        }
                    });
                }
            }

            // Customer Review Image Preview
            const revImgInput = document.getElementById('reviewImageInput');
            const revImgPreview = document.getElementById('reviewImagePreview');
            const revPreviewImg = document.getElementById('reviewPreviewImg');
            if (revImgInput && revImgPreview && revPreviewImg) {
                revImgInput.addEventListener('change', function() {
                    const file = this.files && this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            revPreviewImg.src = e.target.result;
                            revImgPreview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        revPreviewImg.src = '';
                        revImgPreview.style.display = 'none';
                    }
                });
            }
        });
    </script>
@endpush
