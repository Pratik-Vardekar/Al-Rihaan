@extends('layouts.front')
@section('title', 'My Wishlist | Al Rihan Luxury Perfumes')

@section('content')
    <main class="pageWrapper profile-page wishlist-page">

        <section class="py-5" style="background-color: #ffffff !important;">
            <div class="container">

                <div class="row g-4 mb-5">
                    @auth
                        @include('frontend.partials.myaccount_nav', [
                            'active' => 'wishlist',
                            'user' => $user,
                        ])
                    @endauth

                    <div class="{{ auth()->check() ? 'col-lg-9' : 'col-12' }} mb-lg-4">

                        <div
                            class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 pb-2 border-bottom">
                            <h2 class="sectTitle lh-1 mb-0 d-inline-flex align-items-center gap-3">
                                My Wishlist
                                <span class="badge rounded-pill bg-danger fs-6 px-3 py-1" id="wishlistCount">
                                    {{ $wishlistItems->count() }}
                                </span>
                            </h2>
                        </div>

                        <div id="wishlistMessage"></div>

                        @if ($wishlistItems->isNotEmpty())
                            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-xl-3 g-4 mb-5"
                                id="wishlistContainer">
                                @foreach ($wishlistItems as $item)
                                    @if ($item->product)
                                        @php
                                            $product = $item->product;
                                            $mrp = (float) ($product->price ?? 0);
                                            $sellingPrice = (float) ($product->selling_price ?? 0);
                                            if ($sellingPrice <= 0) {
                                                $sellingPrice = $mrp;
                                            }
                                            $hasDiscount = $mrp > 0 && $sellingPrice > 0 && $mrp > $sellingPrice;
                                            $discount = 0;
                                            if ($hasDiscount) {
                                                $discount =
                                                    (int) ($product->discount_percent ?? ($product->discount ?? 0));
                                                if ($discount <= 0) {
                                                    $discount = (int) round((($mrp - $sellingPrice) / $mrp) * 100);
                                                }
                                            }
                                            $image = !empty($product->thumbnail)
                                                ? asset('backend/assets/images/product/' . $product->thumbnail)
                                                : asset('frontend/assets/images/products/1.jpg');
                                            $galleryList = is_array($product->gallery)
                                                ? $product->gallery
                                                : json_decode($product->gallery ?? '[]', true);
                                            $hoverImage = !empty($galleryList[0])
                                                ? asset('backend/assets/images/product/' . $galleryList[0])
                                                : $image;
                                            $isNew = !empty($product->is_new_arrival) || !empty($product->is_new);
                                            $productRating =
                                                (float) ($product->rating ?? ($product->averageRating() ?? 0));
                                            $gender = match (strtolower((string) $product->gender)) {
                                                'men' => 'Men',
                                                'women' => 'Women',
                                                'both', 'unisex' => 'Unisex',
                                                default => 'Unisex',
                                            };
                                            $productMeta = !empty($product->model_no)
                                                ? $product->model_no
                                                : (!empty($product->specification)
                                                    ? strip_tags($product->specification)
                                                    : '');
                                            $inStock = (int) ($product->quantity ?? 0) > 0;
                                        @endphp
                                        <div class="col wishlist-item" id="wishlist-col-{{ $product->id }}"
                                            data-product-id="{{ $product->id }}" data-wishlist-id="{{ $item->id }}">
                                            <div class="product-card">

                                                <!-- Product Image -->
                                                <div class="product-image">

                                                    <a href="{{ route('product.details', ['slug' => $product->slug]) }}"
                                                        class="product-image-wrapper">

                                                        <img src="{{ $image }}" alt="{{ $product->name }}"
                                                            class="main-image" loading="lazy">

                                                        <img src="{{ $hoverImage }}"
                                                            alt="{{ $product->name }} alternate view" class="hover-image"
                                                            loading="lazy">
                                                    </a>

                                                    @if ($productRating > 0)
                                                        <span class="product-badge rating">
                                                            ★ {{ number_format($productRating, 1) }}
                                                        </span>
                                                    @elseif($isNew)
                                                        <span class="product-badge new">New</span>
                                                    @endif

                                                    <!-- Wishlist -->
                                                    <button type="button"
                                                        class="wishlist-btn is-active remove-wishlist-btn js-wishlist-remove"
                                                        data-product-id="{{ $product->id }}" title="Remove from wishlist"
                                                        aria-label="Remove from wishlist">

                                                        <i data-lucide="heart"></i>
                                                    </button>

                                                </div>

                                                <!-- Product Information -->
                                                <div class="product-info">

                                                    <h3>
                                                        <a href="{{ route('product.details', ['slug' => $product->slug]) }}"
                                                            title="{{ $product->name }}">
                                                            {{ $product->name }}
                                                        </a>
                                                    </h3>

                                                    <p class="product-meta">
                                                        {{ $gender }}

                                                        @if ($productMeta)
                                                            &nbsp; | &nbsp;
                                                            {{ \Illuminate\Support\Str::limit($productMeta, 35) }}
                                                        @endif
                                                    </p>

                                                    <!-- Price -->
                                                    <div class="product-price">
                                                        <strong>
                                                            ₹{{ number_format($sellingPrice) }}
                                                        </strong>

                                                        @if ($hasDiscount)
                                                            <del>
                                                                ₹{{ number_format($mrp) }}
                                                            </del>

                                                            <span>{{ $discount }}%</span>
                                                        @endif
                                                    </div>

                                                    <!-- Add to Cart -->
                                                    @if (!$inStock)
                                                        <button type="button" class="add-cart-btn js-out-of-stock-btn"
                                                            data-oos-message="Product is out of stock" disabled>
                                                            <i data-lucide="shopping-cart"></i>
                                                            Out of Stock
                                                        </button>
                                                    @else
                                                        <button type="button" class="add-cart-btn add-to-cart-btn"
                                                            data-product-id="{{ $product->id }}"
                                                            data-add-url="{{ route('cart.add', $product->id) }}">
                                                            <i data-lucide="shopping-cart"></i>
                                                            Add to Cart
                                                        </button>
                                                    @endif

                                                </div>

                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <div class="col-12 d-none" id="emptyWishlist">
                                <div class="rounded-4 p-5 text-center bg-white border shadow-sm">
                                    <div class="mb-3">
                                        <i data-lucide="heart"
                                            style="width: 48px; height: 48px; stroke: #a47b45; opacity: 0.6;"></i>
                                    </div>
                                    <h4 class="mb-2"
                                        style="font-family: 'Cormorant Upright', serif; font-size: 24px; font-weight: 700;">
                                        Your wishlist is empty</h4>
                                    <p class="text-secondary mb-4">Save fragrances you love and shop them later.</p>
                                    <a href="{{ route('product.listing') }}" class="ctaBtn">
                                        <span class="btnTxt">Browse Fragrances</span>
                                        <span class="arw"></span>
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="col-12" id="emptyWishlist">
                                <div class="rounded-4 p-5 text-center bg-white border shadow-sm">
                                    <div class="mb-3">
                                        <i data-lucide="heart"
                                            style="width: 48px; height: 48px; stroke: #a47b45; opacity: 0.6;"></i>
                                    </div>
                                    <h4 class="mb-2"
                                        style="font-family: 'Cormorant Upright', serif; font-size: 24px; font-weight: 700;">
                                        Your wishlist is empty</h4>
                                    <p class="text-secondary mb-4">Save fragrances you love and shop them later.</p>
                                    <a href="{{ route('product.listing') }}" class="ctaBtn">
                                        <span class="btnTxt">Browse Fragrances</span>
                                        <span class="arw"></span>
                                    </a>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </section>

    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // ===== REMOVE FROM WISHLIST (AJAX - DIRECT REMOVAL NO RELOAD) =====
            function setupWishlistRemoveButtons() {
                document.querySelectorAll('.remove-wishlist-btn, .js-wishlist-remove').forEach(function(btn) {
                    if (btn.getAttribute('data-listener-attached') === '1') return;
                    btn.setAttribute('data-listener-attached', '1');

                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        var productId = this.getAttribute('data-product-id');
                        var itemElement = document.getElementById('wishlist-col-' + productId) ||
                            this.closest('.wishlist-item');

                        if (!productId) return;

                        // Visual feedback
                        this.style.opacity = '0.5';
                        this.style.pointerEvents = 'none';

                        var removeUrl = '{{ route('wishlist.remove', ':id') }}'.replace(':id',
                            productId);

                        fetch(removeUrl, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(function(response) {
                                return response.json();
                            })
                            .then(function(data) {
                                if (data.status || data.success) {
                                    if (itemElement) {
                                        itemElement.style.transition = 'all 0.3s ease';
                                        itemElement.style.opacity = '0';
                                        itemElement.style.transform = 'scale(0.85)';

                                        setTimeout(function() {
                                            itemElement.remove();

                                            var remainingItems = document
                                                .querySelectorAll('.wishlist-item')
                                                .length;
                                            var newCount = data.wishlist_count !==
                                                undefined ? data.wishlist_count :
                                                remainingItems;

                                            // Update page count
                                            var countEl = document.getElementById(
                                                'wishlistCount');
                                            if (countEl) {
                                                countEl.textContent = newCount;
                                            }

                                            // Update header badges
                                            if (typeof updateWishlistBadge ===
                                                'function') {
                                                updateWishlistBadge(newCount);
                                            }
                                            document.querySelectorAll(
                                                    '.wishlist-count, .wishlistCount')
                                                .forEach(function(el) {
                                                    el.textContent = newCount;
                                                });

                                            // Show empty state if no items left
                                            var container = document.getElementById(
                                                'wishlistContainer');
                                            var emptyEl = document.getElementById(
                                                'emptyWishlist');
                                            if (remainingItems === 0) {
                                                if (container) {
                                                    container.classList.add('d-none');
                                                }
                                                if (emptyEl) {
                                                    emptyEl.classList.remove('d-none');
                                                }
                                            }

                                            showMessage(data.message ||
                                                'Item removed from wishlist.',
                                                'success');
                                            if (typeof window.showToast ===
                                                'function') {
                                                window.showToast(data.message ||
                                                    'Item removed from wishlist.',
                                                    'info');
                                            }
                                        }, 280);
                                    }
                                } else {
                                    btn.style.opacity = '1';
                                    btn.style.pointerEvents = 'auto';
                                    showMessage(data.message || 'Failed to remove item.',
                                        'danger');
                                }
                            })
                            .catch(function(error) {
                                btn.style.opacity = '1';
                                btn.style.pointerEvents = 'auto';
                                showMessage('Something went wrong. Please try again.',
                                    'danger');
                                console.error('Error:', error);
                            });
                    });
                });
            }

            setupWishlistRemoveButtons();

            // ===== ADD TO CART (AJAX - NO PAGE RELOAD) =====
            document.querySelectorAll('.add-to-cart-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var btnElement = this;
                    if (btnElement.getAttribute('data-busy') === '1') {
                        return false;
                    }

                    var productId = this.getAttribute('data-product-id');
                    var addUrl = this.getAttribute('data-add-url');
                    var originalHtml = btnElement.innerHTML;

                    if (!productId || !addUrl) return;

                    btnElement.setAttribute('data-busy', '1');
                    btnElement.disabled = true;
                    btnElement.style.pointerEvents = 'none';
                    btnElement.textContent = 'Adding...';

                    fetch(addUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(data) {
                            if (data.status || data.success) {
                                var msg = data.message || 'Product added to cart successfully';

                                // Update cart count badge in header
                                var newCount = data.cart_count !== undefined ? data.cart_count :
                                    1;
                                if (typeof window.updateCartBadge === 'function') {
                                    window.updateCartBadge(newCount);
                                } else {
                                    document.querySelectorAll('.cart-count, .cartCount')
                                        .forEach(function(el) {
                                            el.textContent = newCount;
                                            if (parseInt(newCount, 10) > 0) {
                                                el.style.display = 'flex';
                                            } else {
                                                el.style.display = 'none';
                                            }
                                        });
                                }

                                if (data.already_in_cart) {
                                    showMessage(msg, 'info');
                                    if (typeof window.showToast === 'function') {
                                        window.showToast(msg, 'warning');
                                    }
                                    btnElement.textContent = 'Already in Cart';
                                } else {
                                    showMessage(msg, 'success');
                                    if (typeof window.showToast === 'function') {
                                        window.showToast(msg, 'success');
                                    }
                                    btnElement.textContent = 'Added ✓';
                                    btnElement.style.background = '#28a745';
                                    btnElement.style.borderColor = '#28a745';
                                    btnElement.style.color = '#fff';
                                }

                                setTimeout(function() {
                                    btnElement.innerHTML = originalHtml;
                                    btnElement.style.background = '#34271e';
                                    btnElement.style.borderColor = '#34271e';
                                    btnElement.style.color = '#ffffff';
                                    btnElement.disabled = false;
                                    btnElement.style.pointerEvents = 'auto';
                                    btnElement.removeAttribute('data-busy');
                                    if (typeof lucide !== 'undefined') {
                                        lucide.createIcons();
                                    }
                                }, 2000);
                            } else {
                                btnElement.innerHTML = originalHtml;
                                btnElement.disabled = false;
                                btnElement.style.pointerEvents = 'auto';
                                btnElement.removeAttribute('data-busy');
                                showMessage(data.message || 'Failed to add to cart.', 'danger');
                                if (typeof window.showToast === 'function') {
                                    window.showToast(data.message || 'Failed to add to cart.',
                                        'error');
                                }
                            }
                        })
                        .catch(function(error) {
                            btnElement.disabled = false;
                            btnElement.style.pointerEvents = 'auto';
                            btnElement.removeAttribute('data-busy');
                            btnElement.innerHTML = originalHtml;
                            showMessage('Something went wrong. Please try again.', 'danger');
                            console.error('Error:', error);
                        });
                });
            });

            // ===== SHOW MESSAGE FUNCTION =====
            function showMessage(message, type) {
                var messageContainer = document.getElementById('wishlistMessage');
                if (!messageContainer) return;

                var alertClass = type === 'success' ? 'alert-success' : (type === 'info' ? 'alert-info' :
                    'alert-danger');
                var icon = type === 'success' ? '✓' : (type === 'info' ? 'ℹ' : '✕');

                messageContainer.innerHTML = `
                    <div class="alert ${alertClass} alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                        <span>${icon}</span>
                        <span>${message}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;

                setTimeout(function() {
                    var alert = messageContainer.querySelector('.alert');
                    if (alert) {
                        alert.classList.remove('show');
                        setTimeout(function() {
                            messageContainer.innerHTML = '';
                        }, 300);
                    }
                }, 5000);
            }
        });
    </script>
@endpush
