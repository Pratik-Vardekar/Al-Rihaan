@extends('layouts.front')
@section('content')
    <main class="pageWrapper home-page">

        {{-- Slider --}}
        <section class="hero-banner" id="home">

            <div class="swiper heroSwiper">

                <div class="swiper-wrapper">

                    @forelse($sliders as $key => $slider)
                        @php
                            // Desktop Image: support image_url, image or desktop_image
                            $desktopName = $slider->desktop_image ?? $slider->image;
                            $desktopSrc =
                                $slider->image_url ?:
                                ($desktopName
                                    ? asset('backend/assets/images/slider/' . $desktopName)
                                    : asset('frontend/assets/images/heroBanner_1.jpg'));

                            // Mobile Image: support mobile_image_url or mobile_image
                            $mobileName = $slider->mobile_image;
                            $mobileSrc =
                                $slider->mobile_image_url ?:
                                ($mobileName
                                    ? asset('backend/assets/images/slider/' . $mobileName)
                                    : null);

                            $altText = $slider->alt_text ?? ($slider->title ?? 'Al Rihan Luxury Perfume');
                        @endphp

                        <div class="swiper-slide hero-slide">

                            @if (!empty($slider->link))
                                <a href="{{ $slider->link }}" class="hero-slide-link"
                                    style="display: block; width: 100%; height: 100%;">
                            @endif

                            <picture>

                                @if (!empty($mobileSrc))
                                    <source media="(max-width: 767px)" srcset="{{ $mobileSrc }}">
                                @endif

                                <img src="{{ $desktopSrc }}" alt="{{ $altText }}"
                                    loading="{{ $key == 0 ? 'eager' : 'lazy' }}"
                                    @if ($key == 0) fetchpriority="high" @endif>

                            </picture>

                            @if (!empty($slider->link))
                                </a>
                            @endif

                        </div>

                    @empty

                        {{-- Fallback default banners if no sliders in database --}}
                        <div class="swiper-slide hero-slide">
                            <picture>
                                <source media="(max-width: 767px)"
                                    srcset="{{ asset('frontend/assets/images/heroBanner_mob1.jpg') }}">
                                <img src="{{ asset('frontend/assets/images/heroBanner_1.jpg') }}"
                                    alt="Al Rihan Luxury Perfume" loading="eager" fetchpriority="high">
                            </picture>
                        </div>

                        <div class="swiper-slide hero-slide">
                            <picture>
                                <source media="(max-width: 767px)"
                                    srcset="{{ asset('frontend/assets/images/heroBanner_mob2.jpg') }}">
                                <img src="{{ asset('frontend/assets/images/heroBanner_2.jpg') }}"
                                    alt="Al Rihan Luxury Perfume" loading="lazy">
                            </picture>
                        </div>
                    @endforelse

                </div>

                <div class="hero-pagination"></div>

            </div>

            <div class="hero-loader">
                <span></span>
            </div>

        </section>


        {{-- CATEGORIES SECTION --}}


        <section class="category-section section-container" id="categories">

            <div class="category-heading reveal">
                <span class="section-subtitle">EXPLORE OUR COLLECTION</span>
                <h2 class="section-heading">Shop By Category.</h2>
            </div>

            <div class="category-slider-wrapper">
                <div class="swiper categorySwiper">
                    <div class="swiper-wrapper">

                        @forelse($categories as $category)
                            <div class="swiper-slide">
                                <a href="{{ route('category.show', $category->slug) }}" class="category-card">

                                    <div class="category-image">
                                        <img src="{{ !empty($category->image)
                                            ? asset('backend/assets/images/category/' . $category->image)
                                            : asset('frontend/assets/images/category/perfume.jpg') }}"
                                            alt="{{ $category->name }}" loading="lazy">

                                        <div class="category-overlay">
                                            <h3>{{ $category->name }}</h3>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="swiper-slide">
                                <p>No categories available.</p>
                            </div>
                        @endforelse

                    </div>
                </div>
            </div>

            <div class="category-controls">
                <button class="category-prev" aria-label="Previous categories">
                    <i data-lucide="chevron-left"></i>
                </button>

                <div class="category-pagination"></div>

                <button class="category-next" aria-label="Next categories">
                    <i data-lucide="chevron-right"></i>
                </button>
            </div>

        </section>


        {{-- THE SIGNATURE EDIT --}}

        {{-- <section class="bestseller-section section-container" id="bestsellers">

            <!-- Section Heading -->
            <div class="bestseller-heading reveal">

                <span class="section-subtitle">
                    CURATED FOR YOU
                </span>

                <h2 class="section-heading">
                    The Signature Edit.
                </h2>

            </div>


            <!-- Product Slider -->

            <div class="bestseller-slider-wrapper">

                <div class="swiper bestsellerSwiper">
                    <div class="swiper-wrapper">

                        <!-- Product 1 -->
                        <div class="swiper-slide">
                            <a href="product-details.html" class="product-card">

                                <div class="product-image">

                                    <div class="product-image-wrapper">
                                        <img src="{{ asset('frontend\assets\images\products\1.jpg') }}"
                                            alt="Rose Éternelle" class="main-image" loading="lazy">

                                        <img src="{{ asset('frontend\assets\images\products\2.jpg') }}"
                                            alt="Rose Éternelle alternate view" class="hover-image" loading="lazy">
                                    </div>

                                    <span class="product-badge rating">★ 4.2</span>

                                    <button class="wishlist-btn" aria-label="Add to wishlist">
                                        <i data-lucide="heart"></i>
                                    </button>

                                </div>

                                <div class="product-info">
                                    <h3>Rose Éternelle</h3>

                                    <p class="product-meta">
                                        100ml&nbsp; | &nbsp;Unisex&nbsp; | &nbsp;Rose · Peony · Musk
                                    </p>

                                    <div class="product-price">
                                        <strong>₹799</strong>
                                        <del>₹999.00</del>
                                        <span>10%</span>
                                    </div>

                                    <button class="add-cart-btn">
                                        <i data-lucide="shopping-cart"></i>
                                        Add to Cart
                                    </button>
                                </div>

                            </a>
                        </div>


                        <!-- Product 2 -->
                        <div class="swiper-slide">
                            <a href="product-details.html" class="product-card">

                                <div class="product-image">

                                    <div class="product-image-wrapper">
                                        <img src="{{ asset('frontend\assets\images\products\2.jpg') }}" alt="Noir Mystique"
                                            class="main-image" loading="lazy">

                                        <img src="{{ asset('frontend\assets\images\products\1.jpg') }}"
                                            alt="Noir Mystique alternate view" class="hover-image" loading="lazy">
                                    </div>

                                    <span class="product-badge new">New</span>

                                    <button class="wishlist-btn" aria-label="Add to wishlist">
                                        <i data-lucide="heart"></i>
                                    </button>

                                </div>

                                <div class="product-info">
                                    <h3>Noir Mystique</h3>

                                    <p class="product-meta">
                                        100ml&nbsp; | &nbsp;Unisex&nbsp; | &nbsp;Amber · Smoky · Benzoin
                                    </p>

                                    <div class="product-price">
                                        <strong>₹699</strong>
                                        <del>₹999.00</del>
                                        <span>10%</span>
                                    </div>

                                    <button class="add-cart-btn">
                                        <i data-lucide="shopping-cart"></i>
                                        Add to Cart
                                    </button>
                                </div>

                            </a>
                        </div>


                        <!-- Product 3 -->
                        <div class="swiper-slide">
                            <a href="product-details.html" class="product-card">

                                <div class="product-image">

                                    <div class="product-image-wrapper">
                                        <img src="{{ asset('frontend\assets\images\products\3.jpg') }}" alt="Bois Doré"
                                            class="main-image" loading="lazy">

                                        <img src="{{ asset('frontend\assets\images\products\1.jpg') }}"
                                            alt="Bois Doré alternate view" class="hover-image" loading="lazy">
                                    </div>

                                    <span class="product-badge rating">★ 4.2</span>

                                    <button class="wishlist-btn" aria-label="Add to wishlist">
                                        <i data-lucide="heart"></i>
                                    </button>

                                </div>

                                <div class="product-info">
                                    <h3>Bois Doré</h3>

                                    <p class="product-meta">
                                        100ml&nbsp; | &nbsp;Unisex&nbsp; | &nbsp;Amber · Smoky · Benzoin
                                    </p>

                                    <div class="product-price">
                                        <strong>₹499</strong>
                                        <del>₹799.00</del>
                                        <span>10%</span>
                                    </div>

                                    <button class="add-cart-btn">
                                        <i data-lucide="shopping-cart"></i>
                                        Add to Cart
                                    </button>
                                </div>

                            </a>
                        </div>


                        <!-- Product 4 -->
                        <div class="swiper-slide">
                            <a href="product-details.html" class="product-card">

                                <div class="product-image">

                                    <div class="product-image-wrapper">
                                        <img src="{{ asset('frontend\assets\images\products\5.jpg') }}" alt="Oud Royale"
                                            class="main-image" loading="lazy">

                                        <img src="{{ asset('frontend\assets\images\products\6.jpg') }}"
                                            alt="Oud Royale alternate view" class="hover-image" loading="lazy">
                                    </div>

                                    <span class="product-badge new">New</span>

                                    <button class="wishlist-btn" aria-label="Add to wishlist">
                                        <i data-lucide="heart"></i>
                                    </button>

                                </div>

                                <div class="product-info">
                                    <h3>Oud Royale</h3>

                                    <p class="product-meta">
                                        100ml&nbsp; | &nbsp;Unisex&nbsp; | &nbsp;Amber · Smoky · Benzoin
                                    </p>

                                    <div class="product-price">
                                        <strong>₹799</strong>
                                        <del>₹899.00</del>
                                        <span>10%</span>
                                    </div>

                                    <button class="add-cart-btn">
                                        <i data-lucide="shopping-cart"></i>
                                        Add to Cart
                                    </button>
                                </div>

                            </a>
                        </div>


                        <!-- Product 5 -->
                        <div class="swiper-slide">
                            <a href="product-details.html" class="product-card">

                                <div class="product-image">

                                    <div class="product-image-wrapper">
                                        <img src="{{ asset('frontend\assets\images\products\7.jpg') }}"
                                            alt="Velvet Essence" class="main-image" loading="lazy">

                                        <img src="{{ asset('frontend\assets\images\products\6.jpg') }}"
                                            alt="Velvet Essence alternate view" class="hover-image" loading="lazy">
                                    </div>

                                    <span class="product-badge rating">★ 4.5</span>

                                    <button class="wishlist-btn" aria-label="Add to wishlist">
                                        <i data-lucide="heart"></i>
                                    </button>

                                </div>

                                <div class="product-info">
                                    <h3>Velvet Essence</h3>

                                    <p class="product-meta">
                                        100ml&nbsp; | &nbsp;Unisex&nbsp; | &nbsp;Woody · Floral · Musk
                                    </p>

                                    <div class="product-price">
                                        <strong>₹699</strong>
                                        <del>₹899.00</del>
                                        <span>10%</span>
                                    </div>

                                    <button class="add-cart-btn">
                                        <i data-lucide="shopping-cart"></i>
                                        Add to Cart
                                    </button>
                                </div>

                            </a>
                        </div>

                    </div>
                </div>

            </div>



            <!-- Slider Controls -->
            <div class="bestseller-controls">

                <button class="bestseller-prev" aria-label="Previous products">
                    <i data-lucide="chevron-left"></i>
                </button>

                <div class="bestseller-pagination"></div>

                <button class="bestseller-next" aria-label="Next products">
                    <i data-lucide="chevron-right"></i>
                </button>

            </div>


            <!-- Explore All -->
            <div class="explore-wrapper">

                <a href={{ route('product.listing') }} class="explore-btn">
                    Explore All
                </a>

            </div>

        </section> --}}


        <section class="bestseller-section section-container" id="bestsellers">

            <!-- Section Heading -->
            <div class="bestseller-heading reveal">
                <span class="section-subtitle">CURATED FOR YOU</span>
                <h2 class="section-heading">The Signature Edit.</h2>
            </div>

            @if (isset($bestSellingProducts) && $bestSellingProducts->isNotEmpty())
                <!-- Product Slider -->
                <div class="bestseller-slider-wrapper">
                    <div class="swiper bestsellerSwiper">
                        <div class="swiper-wrapper">

                            @foreach ($bestSellingProducts as $item)
                                <div class="swiper-slide">
                                    @include('frontend.partials.product-card', ['product' => $item])
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>

                <!-- Slider Controls -->
                <div class="bestseller-controls">

                    <button class="bestseller-prev" type="button" aria-label="Previous products">
                        <i data-lucide="chevron-left"></i>
                    </button>

                    <div class="bestseller-pagination"></div>

                    <button class="bestseller-next" type="button" aria-label="Next products">
                        <i data-lucide="chevron-right"></i>
                    </button>

                </div>
            @else
                <div class="text-center py-5">
                    <p>No signature products available currently.</p>
                </div>
            @endif

            <!-- Explore All -->
            <div class="explore-wrapper">
                <a href="{{ route('product.listing') }}" class="explore-btn">
                    Explore All
                </a>
            </div>

        </section>


        {{-- OCCASION SECTION --}}

        <section class="occasion-section" id="occasions">
            <div class="section-container">

                <!-- Section Heading -->
                <div class="occasion-heading">
                    <span class="section-subtitle">SCENTS FOR EVERY CHAPTER</span>
                    <h2 class="section-heading">
                        Make Every Occasion<br>
                        Unforgettable.
                    </h2>
                </div>

                <!-- Occasion Grid -->
                <div class="occasion-grid">

                    <!-- Evening Rituals -->
                    <a href="{{ asset('frontend/assets/images/evening-rituals.jpg') }}"
                        class="occasion-card occasion-card-tall">

                        <img src="{{ asset('frontend/assets/images/evening-rituals.jpg') }}" alt="Evening Rituals">

                        <div class="occasion-overlay">
                            <h3>Evening Rituals</h3>
                            <p>Bring warmth and atmosphere to your evenings.</p>
                        </div>
                    </a>

                    <!-- Everyday Elegance -->
                    <a href="{{ asset('frontend/assets/images/everyday-elegance.jpg') }}" class="occasion-card">
                        <img src="{{ asset('frontend/assets/images/everyday-elegance.jpg') }}" alt="Everyday Elegance">

                        <div class="occasion-overlay">
                            <h3>Everyday Elegance</h3>
                            <p>Make your everyday presence distinctive.</p>
                        </div>
                    </a>

                    <!-- Thoughtful Gifting -->
                    <a href="{{ asset('frontend/assets/images/thoughtful-gifting.jpg') }}" class="occasion-card">
                        <img src="{{ asset('frontend/assets/images/thoughtful-gifting.jpg') }}" alt="Thoughtful Gifting">

                        <div class="occasion-overlay">
                            <h3>Thoughtful Gifting</h3>
                            <p>Give a fragrance that speaks without words.</p>
                        </div>
                    </a>

                    <!-- Wedding & Celebrations -->
                    <a href="{{ asset('frontend/assets/images/wedding-celebrations.jpg') }}"
                        class="occasion-card occasion-card-wide">
                        <img src="{{ asset('frontend/assets/images/wedding-celebrations.jpg') }}"
                            alt="Wedding and Celebrations">

                        <div class="occasion-overlay">
                            <h3>Wedding &amp; Celebrations</h3>
                            <p>Fragrances for moments worth remembering.</p>
                        </div>
                    </a>

                </div>

            </div>
        </section>

        <!-- NEW ARRIVALS SLIDER -->
        <section class="new-arrivals-section" id="new-arrivals">
            <div class="section-container">

                <!-- Section Heading -->
                <div class="new-arrivals-heading">
                    <span class="section-subtitle">NEW ARRIVALS</span>
                    <h2 class="section-heading">Worn Most this Season</h2>
                </div>

                @php
                    $newArrivalsList = $newArrivals ?? ($newArrivalsProducts ?? collect());
                @endphp

                @if ($newArrivalsList->isNotEmpty())
                    <!-- NEW ARRIVALS SLIDER -->
                    <div class="new-arrivals-slider-wrapper">

                        <div class="swiper newArrivalsSwiper">
                            <div class="swiper-wrapper">

                                @foreach ($newArrivalsList as $item)
                                    <div class="swiper-slide">
                                        @include('frontend.partials.product-card', ['product' => $item])
                                    </div>
                                @endforeach

                            </div>
                        </div>

                        <!-- Slider Controls -->
                        <div class="bestseller-controls">

                            <button class="new-arrivals-prev" type="button" aria-label="Previous products">
                                <i data-lucide="chevron-left"></i>
                            </button>

                            <div class="new-arrivals-pagination"></div>

                            <button class="new-arrivals-next" type="button" aria-label="Next products">
                                <i data-lucide="chevron-right"></i>
                            </button>

                        </div>

                        <!-- Explore All -->
                        <div class="explore-wrapper">
                            <a href="{{ route('product.listing') }}" class="explore-btn">
                                Explore All
                            </a>
                        </div>

                    </div>
                @else
                    <div class="text-center py-5">
                        <p>No new arrivals available currently.</p>
                    </div>
                @endif

            </div>
        </section>


        {{-- fragrance-journey --}}
        <section class="fragrance-journey">
            <div class="fragrance-journey-overlay"></div>

            <div class="fragrance-journey-container">

                <!-- Left Content -->
                <div class="fragrance-journey-content">

                    <span class="fragrance-journey-subtitle">
                        CRAFTED WITH CHARACTER
                    </span>

                    <h2>
                        Every Note Has a Story.
                    </h2>

                    <p>
                        A memorable fragrance unfolds in layers, revealing
                        a distinctive personality with every moment.
                    </p>

                </div>

                <!-- Right Content -->
                <div class="fragrance-journey-steps">

                    <div class="fragrance-journey-item">
                        <span class="fragrance-step-number">01</span>

                        <h3>The Opening Impression</h3>

                        <p>
                            A captivating introduction that awakens the senses.
                        </p>
                    </div>

                    <div class="fragrance-journey-item">
                        <span class="fragrance-step-number">02</span>

                        <h3>The Heart of the Fragrance</h3>

                        <p>
                            Expressive notes that reveal depth and personality.
                        </p>
                    </div>

                    <div class="fragrance-journey-item">
                        <span class="fragrance-step-number">03</span>

                        <h3>The Lasting Signature</h3>

                        <p>
                            A memorable finish that stays with you.
                        </p>
                    </div>

                </div>

            </div>
        </section>

        {{-- gift-feature-section --}}
        <section class="gift-feature-section">
            <div class="gift-feature-image">
                <img src="{{ asset('frontend\assets\images\gift.jpg') }}" alt="Luxury Perfume Gift Set">
            </div>

            <div class="gift-feature-content">
                <span class="gift-eyebrow">THE ART OF GIFTING</span>

                <h2>A Scent That Becomes Your Signature.</h2>

                <p>
                    Curated fragrance combinations bring together
                    captivating scents for yourself and the people you love.
                </p>

                <a href="{{ route('gifting') }}" class="gift-feature-btn">
                    Explore Gift Set
                </a>

                <div class="gift-watermark"><img src="images/al.svg" alt=""></div>
            </div>
        </section>


    </main>
@endsection
