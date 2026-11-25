@extends('layouts.front')

@section('title', 'Shop By Category | Al Rihan Luxury Perfumes')

@section('content')
    <style>
        .categories-page {
            padding: 40px var(--page-padding, 5%) 80px;
            background: var(--color-bg, #f3eadc);
            min-height: 75vh;
        }

        .category-showcase-card {
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border: 1px solid var(--color-border, #e1d4c1);
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            height: 100%;
            box-shadow: 0 4px 14px rgba(46, 33, 25, 0.06);
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.4s ease, border-color 0.4s ease;
        }

        .category-showcase-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 32px rgba(46, 33, 25, 0.12);
            border-color: var(--color-bronze, #a88a60);
            color: inherit;
        }

        .category-showcase-img {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 11;
            overflow: hidden;
            background: #ebe0d0;
        }

        .category-showcase-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.8s cubic-bezier(0.2, 0.7, 0.2, 1);
        }

        .category-showcase-card:hover .category-showcase-img img {
            transform: scale(1.07);
        }

        .category-showcase-img::after {
            content: "";
            position: absolute;
            inset: 40% 0 0;
            background: linear-gradient(to bottom, transparent, rgba(46, 33, 25, 0.4));
            z-index: 1;
            pointer-events: none;
        }

        .category-showcase-badge {
            position: absolute;
            top: 14px;
            right: 14px;
            background: rgba(46, 33, 25, 0.82);
            color: #ffffff;
            backdrop-filter: blur(6px);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 2;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .category-showcase-content {
            padding: 24px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .category-showcase-title {
            font-family: var(--font-heading, "Cormorant Upright", serif);
            font-size: 30px;
            font-weight: 600;
            line-height: 1.15;
            color: var(--color-heading, #2e2119);
            margin: 0 0 10px;
            transition: color 0.3s ease;
        }

        .category-showcase-card:hover .category-showcase-title {
            color: var(--color-bronze, #a88a60);
        }

        .category-subcat-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 18px;
        }

        .subcat-pill {
            font-size: 11px;
            background: #f7f1e7;
            color: #6d5d51;
            padding: 3px 10px;
            border-radius: 14px;
            border: 1px solid #ebdccb;
            font-weight: 500;
        }

        .subcat-pill.more {
            background: #ebdccb;
            color: #2e2119;
            font-weight: 600;
        }

        .category-showcase-action {
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--color-bronze, #a88a60);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: gap 0.3s ease, color 0.3s ease;
        }

        .category-showcase-card:hover .category-showcase-action {
            gap: 12px;
            color: var(--color-charcoal, #3A2619);
        }

        .category-showcase-action i {
            width: 16px;
            height: 16px;
            transition: transform 0.3s ease;
        }

        .category-showcase-card:hover .category-showcase-action i {
            transform: translateX(4px);
        }

        .categories-cta-box {
            margin-top: 60px;
            background: #ffffff;
            border: 1px solid var(--color-border, #e1d4c1);
            border-radius: 14px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 4px 18px rgba(46, 33, 25, 0.05);
        }

        .categories-cta-box h3 {
            font-family: var(--font-heading, "Cormorant Upright", serif);
            font-size: 34px;
            color: var(--color-heading, #2e2119);
            margin-bottom: 8px;
        }

        .categories-cta-box p {
            color: var(--color-body, #76685c);
            max-width: 600px;
            margin: 0 auto 20px;
            font-size: 14px;
        }

        .categories-cta-box .btn-explore-all {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--color-charcoal, #3A2619);
            color: #ffffff;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .categories-cta-box .btn-explore-all:hover {
            background: var(--color-bronze, #a88a60);
            transform: translateY(-2px);
            color: #ffffff;
        }

        @media (max-width: 767px) {
            .categories-page {
                padding: 25px var(--page-padding, 4%) 50px;
            }

            .category-showcase-title {
                font-size: 24px;
            }

            .category-showcase-content {
                padding: 18px;
            }

            .categories-cta-box {
                margin-top: 40px;
                padding: 30px 18px;
            }

            .categories-cta-box h3 {
                font-size: 26px;
            }
        }
    </style>

    <section class="shop-page categories-page">
        <!-- HEADER -->
        <div class="shop-header">
            <div class="shop-header-content">
                <span class="shop-subtitle">EXPLORE OUR FRAGRANCE WORLD</span>
                <h1>Shop By Category</h1>
                <p>Discover our exquisite collection of luxury perfumes, attars, and body mists, artfully categorized for
                    every style and mood.</p>
            </div>

            <nav class="shop-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <span>/</span>
                <strong>Categories</strong>
            </nav>
        </div>

        <!-- CATEGORIES GRID -->
        <div class="categories-grid-container">
            <div class="row g-4">
                @forelse($categories as $category)
                    <div class="col-xl-4 col-lg-4 col-md-6 col-12">
                        <a href="{{ route('category.show', $category->slug) }}" class="category-showcase-card">
                            <div class="category-showcase-img">
                                <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy">
                                <div class="category-showcase-badge">
                                    {{ $category->products_count }}
                                    {{ \Illuminate\Support\Str::plural('Fragrance', $category->products_count) }}
                                </div>
                            </div>
                            <div class="category-showcase-content">
                                <h3 class="category-showcase-title">{{ $category->name }}</h3>

                                @if ($category->subcategories && $category->subcategories->count() > 0)
                                    <div class="category-subcat-pills">
                                        @foreach ($category->subcategories->take(3) as $subcat)
                                            <span class="subcat-pill">{{ $subcat->name }}</span>
                                        @endforeach
                                        @if ($category->subcategories->count() > 3)
                                            <span
                                                class="subcat-pill more">+{{ $category->subcategories->count() - 3 }}</span>
                                        @endif
                                    </div>
                                @endif

                                <div class="category-showcase-action">
                                    <span>Explore Collection</span>
                                    <i data-lucide="arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <p class="text-muted fs-5">No categories available at the moment.</p>
                        <a href="{{ route('product.listing') }}" class="btn btn-outline-dark mt-2">Explore All
                            Fragrances</a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- BOTTOM CALL TO ACTION -->
        <div class="categories-cta-box">
            <h3>Looking for Something Specific?</h3>
            <p>Explore our complete fragrance catalog featuring bestsellers, new arrivals, and exclusive perfume oils.</p>
            <a href="{{ route('product.listing') }}" class="btn-explore-all">
                <span>View All Fragrances</span>
                <i data-lucide="arrow-right"></i>
            </a>
        </div>
    </section>
@endsection
