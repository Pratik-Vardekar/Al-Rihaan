<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <title>
        @hasSection('title')
            @yield('title') | Al-Rihan
        @else
            Al-Rihan
        @endif
    </title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('frontend/assets/images/logo.svg') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('frontend/assets/images/logo.svg') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('frontend/assets/images/logo.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('fronten/images/logo.svvicon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Upright:wght@300;400;500;600;700&family=Manrope:wght@200..800&display=swap"rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />

    <link rel="stylesheet"
        href="{{ asset('frontend/assets/css/al-rihan.css') }}?v={{ @filemtime(public_path('frontend/assets/css/al-rihan.css')) ?: 28 }}">
    <link rel="stylesheet"
        href="{{ asset('frontend/assets/css/update-al.css') }}?v={{ @filemtime(public_path('frontend/assets/css/update-al.css')) ?: time() }}">
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/search-suggest.css') }}?v=5">

    <!-- Facebook Pixel Code -->
    <script>
        ! function(f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function() {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '1796916864696577');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1"
            src="https://www.facebook.com/tr?id=1796916864696577&ev=PageView
&noscript=1" />
    </noscript>
    <!-- End Facebook Pixel Code -->

    <style>
        /* Globally remove unwanted list bullet dots across all frontend pages */
        ul.addressList,
        .addressList,
        ul.addressList > li,
        .addressList > li,
        ul.ordersList,
        .ordersList,
        ul.ordersList > li,
        .ordersList > li,
        ul.bagListWrap,
        .bagListWrap,
        ul.bagListWrap > li,
        .bagListWrap > li,
        li.bagItem,
        .bagItem,
        ul.myAccLinks,
        .myAccLinks,
        ul.myAccLinks > li,
        .myAccLinks > li,
        .my-account-sidebar ul,
        .my-account-sidebar li,
        .profile-page ul.addressList,
        .profile-page ul.ordersList,
        .profile-page ul.myAccLinks {
            list-style: none !important;
            list-style-type: none !important;
            padding-left: 0 !important;
            margin-left: 0 !important;
        }

        ul.addressList > li::marker,
        .addressList > li::marker,
        ul.ordersList > li::marker,
        .ordersList > li::marker,
        ul.bagListWrap > li::marker,
        .bagListWrap > li::marker,
        li.bagItem::marker,
        .bagItem::marker,
        ul.myAccLinks > li::marker,
        .myAccLinks > li::marker,
        .my-account-sidebar li::marker,
        .addressList::marker {
            display: none !important;
            content: "" !important;
        }

        #toastContainer {
            z-index: 999999 !important;
        }
        .tws-toast,
        .tws-toast.show {
            background-color: #198754 !important;
            background: #198754 !important;
            color: #ffffff !important;
            border-radius: 50px !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25) !important;
            padding: 10px 18px !important;
            min-width: 280px;
            border: none !important;
        }
        .tws-toast .toast-inner {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 12px !important;
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            border-radius: 0 !important;
            width: 100% !important;
        }
        .tws-toast .tws-toast-body {
            color: #ffffff !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            line-height: 1.4 !important;
            padding: 0 !important;
            background: transparent !important;
            background-color: transparent !important;
        }
        .tws-toast .tws-toast-body a {
            color: #ffd180 !important;
            text-decoration: underline !important;
            font-weight: 600 !important;
        }
        .tws-toast .tws-toast-close {
            background: rgba(255, 255, 255, 0.25) !important;
            border: none !important;
            color: #ffffff !important;
            border-radius: 50% !important;
            width: 24px !important;
            height: 24px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 16px !important;
            cursor: pointer !important;
            line-height: 1 !important;
            padding: 0 !important;
            opacity: 0.9 !important;
            transition: opacity 0.2s, background 0.2s !important;
            flex-shrink: 0 !important;
        }
        .tws-toast .tws-toast-close:hover {
            opacity: 1 !important;
            background: rgba(255, 255, 255, 0.4) !important;
        }
        .tws-toast.tws-toast-warning {
            background-color: #198754 !important;
            background: #198754 !important;
        }
        .tws-toast.tws-toast-success {
            background-color: #198754 !important;
            background: #198754 !important;
        }
        .tws-toast.tws-toast-error {
            background-color: #dc3545 !important;
            background: #dc3545 !important;
        }

        @media (max-width: 991.98px) {
            .site-header .header-actions {
                display: flex !important;
                align-items: center !important;
                gap: 16px !important;
            }

            .site-header .header-actions .header-account-btn,
            .site-header .header-actions .header-wishlist-btn {
                display: none !important;
            }

            .site-header .header-actions .search-toggle,
            .site-header .header-actions .header-cart-btn {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 24px !important;
                height: 24px !important;
                position: relative !important;
                color: inherit !important;
                text-decoration: none !important;
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                line-height: 1 !important;
            }

            .site-header .header-actions .search-toggle i,
            .site-header .header-actions .search-toggle svg,
            .site-header .header-actions .header-cart-btn i,
            .site-header .header-actions .header-cart-btn svg {
                width: 20px !important;
                height: 20px !important;
                stroke-width: 1.75 !important;
                stroke: currentColor !important;
                color: inherit !important;
                display: block !important;
            }

            .site-header .header-actions .header-cart-btn .cart-count {
                position: absolute !important;
                top: -6px !important;
                right: -8px !important;
                min-width: 16px !important;
                height: 16px !important;
                border-radius: 50% !important;
                background: #a47b45 !important;
                color: #ffffff !important;
                font-size: 10px !important;
                font-weight: 700 !important;
                line-height: 1 !important;
                padding: 0 3px !important;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    @include('frontend.partials.header')
    @yield('content')
    @include('frontend.partials.footer')
    @include('frontend.partials.mobile-bottom-menu')

    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script
        src="{{ asset('frontend/assets/js/custom.js') }}?v={{ @filemtime(public_path('frontend/assets/js/custom.js')) ?: 9 }}">
    </script>
    <script src="{{ asset('frontend/assets/js/search-suggest.js') }}?v=5"></script>
    <script src="{{ asset('frontend/assets/js/range-slider.js') }}"></script>

    <script>
        window.showToast = function(message, type) {
            type = type || 'success';
            const container = document.getElementById('toastContainer');
            if (!container || !message) {
                return;
            }

            const toastType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'success';
            const toastId = 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 1000);

            // Clear any existing toast so multiple toasts don't stack/conflict
            container.innerHTML = '';

            container.insertAdjacentHTML('beforeend',
                '<div id="' + toastId + '" class="toast tws-toast tws-toast-' + toastType +
                ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
                '<div class="toast-inner">' +
                '<div class="tws-toast-body">' + message + '</div>' +
                '<button type="button" class="tws-toast-close" data-bs-dismiss="toast" aria-label="Close">&times;</button>' +
                '</div>' +
                '</div>'
            );
            const toastEl = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastEl, {
                animation: true,
                autohide: true,
                delay: 2500
            });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', function() {
                toastEl.remove();
            });
        };

        window.updateCartBadge = function(count) {
            if (count === undefined || count === null) return;
            var num = parseInt(count, 10);
            if (isNaN(num)) num = 0;
            document.querySelectorAll('.cart-count, .cartCount').forEach(function(el) {
                el.textContent = num;
                if (num > 0) {
                    el.style.setProperty('display', 'flex', 'important');
                } else {
                    el.style.setProperty('display', 'none', 'important');
                }
            });
        };

        window.updateWishlistBadge = function(count) {
            if (count === undefined || count === null) return;
            var num = parseInt(count, 10);
            if (isNaN(num)) num = 0;
            document.querySelectorAll('.wishlist-count, .wishlistCount').forEach(function(el) {
                el.textContent = num;
                if (num > 0) {
                    el.style.setProperty('display', 'flex', 'important');
                } else {
                    el.style.setProperty('display', 'none', 'important');
                }
            });
        };

        @if (session('success'))
            window.showToast(@json(session('success')), 'success');
        @endif

        @if (session('error'))
            window.showToast(@json(session('error')), 'error');
        @endif

        @if (session('status'))
            window.showToast(@json(session('status')), 'success');
        @endif

        @if (session('login_required'))
            window.showToast({!! json_encode(
                'Please Login First. <a href="' . route('login') . '">Login</a> or <a href="' . route('register') . '">Sign Up</a>',
            ) !!},
                'warning'
            );
        @endif

        @if (isset($errors) && $errors->any() && !session('error'))
            window.showToast(@json($errors->first()), 'error');
        @endif

        document.addEventListener('click', function(e) {
            const oosBtn = e.target.closest('.js-out-of-stock-btn, .js-out-of-stock');
            if (oosBtn) {
                e.preventDefault();
                e.stopPropagation();
                var msg = oosBtn.getAttribute('data-oos-message') || 'Product is out of stock';
                if (typeof window.showToast === 'function') {
                    window.showToast(msg, 'error');
                } else {
                    alert(msg);
                }
                return;
            }

            const toggleBtn = e.target.closest('.js-wishlist-toggle, .wishlist-btn, .product-gallery-wishlist');
            if (toggleBtn) {
                e.preventDefault();
                e.stopPropagation();
                const toggleUrl = toggleBtn.getAttribute('data-toggle-url');
                if (!toggleUrl || toggleBtn.getAttribute('data-busy') === '1') return;
                toggleBtn.setAttribute('data-busy', '1');
                toggleBtn.style.pointerEvents = 'none';

                const productId = toggleBtn.getAttribute('data-product-id');
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                fetch(toggleUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        if (data.redirect && !data.in_wishlist && !data.success) {
                            window.location.href = data.redirect;
                            return;
                        }
                        const isAdded = data.in_wishlist !== undefined ? Boolean(data.in_wishlist) : (data
                            .status === 'added' || data.is_wishlisted || data.status === true);

                        // Sync all buttons on the page for this product
                        const selector = productId ?
                            ('[data-product-id="' + productId + '"].js-wishlist-toggle, [data-product-id="' +
                                productId + '"].wishlist-btn, [data-product-id="' + productId +
                                '"].product-gallery-wishlist') :
                            null;
                        const buttons = selector ? document.querySelectorAll(selector) : [toggleBtn];

                        buttons.forEach(function(btn) {
                            const icon = btn.querySelector('svg, i');
                            if (isAdded) {
                                btn.classList.add('is-active', 'active', 'saved');
                                btn.setAttribute('aria-label', 'Remove from wishlist');
                                if (icon) {
                                    icon.style.fill = 'currentColor';
                                    icon.style.stroke = 'currentColor';
                                }
                            } else {
                                btn.classList.remove('is-active', 'active', 'saved');
                                btn.setAttribute('aria-label', 'Add to wishlist');
                                if (icon) {
                                    icon.style.fill = 'none';
                                    icon.style.stroke = '';
                                }
                            }
                        });

                        // Toast notification
                        if (typeof window.showToast === 'function') {
                            if (isAdded) {
                                window.showToast(data.message || 'Added to Wishlist', 'success');
                            } else {
                                window.showToast(data.message || 'Removed from Wishlist', 'info');
                            }
                        }

                        // Update header wishlist badge
                        const newCount = data.wishlist_count !== undefined ? data.wishlist_count : (data
                            .count !== undefined ? data.count : undefined);
                        if (typeof window.updateWishlistBadge === 'function' && newCount !== undefined) {
                            window.updateWishlistBadge(newCount);
                        }
                    })
                    .catch(function(err) {
                        console.error('Wishlist toggle error:', err);
                        if (typeof window.showToast === 'function') {
                            window.showToast('Something went wrong. Please try again.', 'error');
                        }
                    })
                    .finally(function() {
                        toggleBtn.removeAttribute('data-busy');
                        toggleBtn.style.pointerEvents = '';
                    });
            }
        });

        var homeBannerEl = document.querySelector('.homeBanner');
        if (homeBannerEl) {
            var homeSlideCount = homeBannerEl.querySelectorAll('.swiper-slide').length;
            new Swiper(homeBannerEl, {
                slidesPerView: 1,
                spaceBetween: 0,
                speed: 650,
                loop: homeSlideCount > 1,
                grabCursor: true,
                autoplay: homeSlideCount > 1 ? {
                    delay: 5000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                } : false,
                pagination: {
                    el: ".hbPagination",
                    clickable: true,
                    dynamicBullets: homeSlideCount > 6,
                },
                parallax: true,
                touchEventsTarget: 'wrapper',
                passiveListeners: true,
                touchReleaseOnEdges: true,
            });
        }

        if (document.querySelector('.marqueStrip')) {
            document.querySelectorAll('.marqueStrip').forEach(function(el) {
                var marqueSlides = el.querySelectorAll('.swiper-slide').length;
                var canLoopMarque = marqueSlides > 10;
                new Swiper(el, {
                    slidesPerView: 3,
                    spaceBetween: 1,
                    speed: 6000,
                    loop: canLoopMarque,
                    watchOverflow: true,
                    grabCursor: false,
                    allowTouchMove: false,
                    autoplay: canLoopMarque ? {
                        delay: 0,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true
                    } : false,
                    breakpoints: {
                        576: {
                            slidesPerView: 4
                        },
                        768: {
                            slidesPerView: 5
                        },
                        992: {
                            slidesPerView: 6
                        },
                        1200: {
                            slidesPerView: 8
                        },
                        1400: {
                            slidesPerView: 10
                        },
                    },
                });
            });
        }

        if (document.querySelector('.productSlider')) {
            document.querySelectorAll('.productSlider').forEach(function(sliderEl) {
                new Swiper(sliderEl, {
                    slidesPerView: 1.5,
                    spaceBetween: 0,
                    speed: 1000,
                    centeredSlides: true,
                    pagination: {
                        el: sliderEl.querySelector('.swiper-pagination') || ".swiper-pagination",
                        clickable: true,
                    },
                    breakpoints: {
                        576: {
                            slidesPerView: 2,
                            slidesPerGroup: 2,
                            centeredSlides: false,
                        },
                        992: {
                            slidesPerView: 3,
                            slidesPerGroup: 3,
                            centeredSlides: false,
                        },
                        1400: {
                            slidesPerView: 4,
                            slidesPerGroup: 4,
                            centeredSlides: false,
                        },
                    },
                });
            });
        }

        if (document.querySelector('.testimSwiper')) {
            document.querySelectorAll('.testimSwiper').forEach(function(testimEl) {
                var testimSlideCount = testimEl.querySelectorAll('.swiper-slide').length;

                new Swiper(testimEl, {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    speed: 1000,
                    loop: testimSlideCount > 3,
                    centeredSlides: false,
                    grabCursor: true,
                    autoplay: testimSlideCount > 1 ? {
                        delay: 3500,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true
                    } : false,
                    pagination: {
                        el: testimEl.querySelector('.swiper-pagination') || ".swiper-pagination",
                        clickable: true
                    },
                    breakpoints: {
                        576: {
                            slidesPerView: 1,
                            spaceBetween: 20
                        },
                        768: {
                            slidesPerView: 2,
                            spaceBetween: 20
                        },
                        992: {
                            slidesPerView: 3,
                            spaceBetween: 24
                        },
                        1200: {
                            slidesPerView: 3,
                            spaceBetween: 30
                        },
                    },
                });
            });
        }
        /* Path Animation */
        let ticking = false;
        let last_known_scroll_position = 0;
        let updatePath = false;
        const element = document.querySelector('.svgRoute');
        const path = element ? element.querySelector('.travelPath') : null;
        let totalLength = 0;

        if (element && path) {
            totalLength = path.getTotalLength();
            path.style.strokeDasharray = `${totalLength}`;
            path.style.strokeDashoffset = totalLength;

            let observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    updatePath = entry.isIntersecting;
                });
            }, {
                rootMargin: "0px 0px 50% 50%"
            });
            observer.observe(element);

            function doSomething() {
                if (!updatePath) {
                    return;
                }
                window.requestAnimationFrame(() => {
                    const center = window.innerHeight / 1.1;
                    const boundaries = path.getBoundingClientRect();
                    const top = boundaries.top;
                    const height = boundaries.height;
                    const percentage = (center - top) / height;
                    const drawLength = percentage > 0 ? totalLength * percentage : 0;
                    path.style.strokeDashoffset = drawLength < totalLength ? totalLength - drawLength : 0;
                })
            }

            window.addEventListener('scroll', function() {
                last_known_scroll_position = window.scrollY;
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        doSomething(last_known_scroll_position);
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        }

        const bgBox = document.querySelector('.offerSection');
        const bg = document.querySelector('.parallax');
        if (bgBox && bg) {
            const ww = window.innerWidth / 2;
            const wh = window.innerHeight / 2;
            bgBox.addEventListener('mousemove', e => {
                bg.style.transform = `translate3d(-${e.clientX / ww}%, ${e.clientY / wh}%, 0)`;
            });
        }

        // Global Add To Cart for cards (New Arrivals, Bestsellers, Related)
        document.addEventListener('click', function(e) {
            const btnEl = e.target.closest('.js-rel-add-cart, .add-cart-btn');
            if (!btnEl) return;
            if (btnEl.classList.contains('js-detail-add-cart') || btnEl.classList.contains('product-add-cart'))
                return;

            const addUrl = btnEl.getAttribute('data-url');
            if (!addUrl) return;

            e.preventDefault();
            e.stopPropagation();

            if (btnEl.getAttribute('data-busy') === '1') return;

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            btnEl.setAttribute('data-busy', '1');
            btnEl.disabled = true;
            const origHtml = btnEl.innerHTML;
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
                    btnEl.innerHTML = origHtml;
                    if (typeof lucide !== 'undefined') lucide.createIcons();

                    if (data.status || data.success) {
                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message || 'Added to bag successfully!', 'success');
                        }
                        if (typeof window.updateCartBadge === 'function' && typeof data.cart_count !==
                            'undefined') {
                            window.updateCartBadge(data.cart_count);
                        }
                    } else {
                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message || 'Could not add to bag.', 'error');
                        }
                    }
                })
                .catch(err => {
                    btnEl.removeAttribute('data-busy');
                    btnEl.disabled = false;
                    btnEl.innerHTML = origHtml;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                    if (typeof window.showToast === 'function') {
                        window.showToast('Something went wrong. Please try again.', 'error');
                    }
                });
        });
    </script>

    @stack('scripts')
    @stack('custom-scripts')
</body>

</html>
