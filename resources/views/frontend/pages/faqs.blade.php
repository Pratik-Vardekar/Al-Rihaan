@extends('layouts.front')

@section('title', 'Frequently Asked Questions (FAQs) | Al Rihan Perfumes')
@section('meta_description', 'Find answers to frequently asked questions about Al Rihan luxury perfumes, orders, delivery timelines, returns, and ingredients.')
@section('meta_keywords', 'faqs, al rihan questions, perfume shipping, return policy, fragrance longevity, al rihan help')

@section('content')
<main class="pageWrapper">
    <div class="faq-page-wrapper">

    <div class="cloudWrap position-relative d-none d-lg-block">
        <div class="container clouds position-relative"></div>
    </div>
        <!-- BREADCRUMB -->
        <nav class="shop-breadcrumb mb-4" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span>/</span>
            <strong aria-current="page">Frequently Asked Questions</strong>
        </nav>

    <section class="py-5 overflow-hidden">
        <div class="container mb-5">
        <!-- HERO HEADER -->
        <div class="faq-hero text-center mb-4">
            <span class="faq-subtitle">HELP & SUPPORT</span>
            <h1 class="faq-title">Frequently Asked Questions</h1>
            <p class="faq-description">
                Explore answers to commonly asked questions about our artisanal luxury perfumes, orders, delivery, returns, and fragrance care.
            </p>

            <div class="text-center mb-4">
                <nav class="breadcrumb justify-content-center mb-0 fw-semibold">
                    <a class="breadcrumb-item" href="{{ route('home') }}">Home</a>
                    <span class="breadcrumb-item active" aria-current="page">Help Center</span>
                </nav>
                <h1 class="sectTitle fw-bold txtred">Help Center</h1>
            <!-- LIVE SEARCH BAR -->
            <div class="faq-search-wrapper">
                <i data-lucide="search" class="faq-search-icon"></i>
                <input type="text" id="faqSearchInput" class="faq-search-input" placeholder="Search questions (e.g. shipping, returns, longevity, tester...)" autocomplete="off">
                <button type="button" id="faqSearchClear" class="faq-search-clear" aria-label="Clear search">
                    <i data-lucide="x"></i>
                </button>
            </div>
        </div>

            <div class="mx-auto generalContent" style="max-width:1000px;">
        @if($totalFaqs > 0)
            <!-- CATEGORY FILTER PILLS (DYNAMIC DB) -->
            <div class="faq-category-nav" id="faqCategoryNav">
                <button type="button" class="faq-pill-btn is-active" data-filter="all">
                    All Questions <span class="faq-pill-count">({{ $totalFaqs }})</span>
                </button>
                @foreach($helpCategories as $category)
                    @if($category->activeFaqs->count() > 0)
                        <button type="button" class="faq-pill-btn" data-filter="cat-{{ $category->id }}">
                            {{ $category->title }} <span class="faq-pill-count">({{ $category->activeFaqs->count() }})</span>
                        </button>
                    @endif
                @endforeach
            </div>

                <div class="faqList" id="faqAccd">
                    @forelse($helpCategories as $category)
                        <div class="faqItem p-3 mb-4 rounded-3 {{ $category->bg_class }}">
                            <div class="faqItemHead" role="button" data-bs-toggle="collapse" data-bs-target="#faqItem_{{ $category->id }}" aria-expanded="false">
                                {{ $category->title }}
            <!-- FAQ LIST (DYNAMIC DB) -->
            <div class="faq-content-area" id="faqContentArea">
                @foreach($helpCategories as $category)
                    @if($category->activeFaqs->count() > 0)
                        <div class="faq-category-group" data-category-group="cat-{{ $category->id }}">
                            <div class="faq-category-heading">
                                <h2>{{ $category->title }}</h2>
                                <span class="badge">{{ $category->activeFaqs->count() }} FAQs</span>
                            </div>
                            <div id="faqItem_{{ $category->id }}" class="collapse {{ $category->bg_sub_class }} rounded-2" data-bs-parent="#faqAccd">
                                <div class="px-4">
                                    @forelse($category->activeFaqs as $faq)
                                        <div class="faqBox py-3">
                                            <div class="faqQ" role="button" data-bs-toggle="collapse" data-bs-target="#faq_{{ $category->id }}_{{ $faq->id }}" aria-expanded="false">
                                                {{ $faq->question }}
                                            </div>
                                            <div id="faq_{{ $category->id }}_{{ $faq->id }}" class="collapse" data-bs-parent="#faqItem_{{ $category->id }}">
                                                <div class="pt-3">
                                                    <p>{!! nl2br(e($faq->answer)) !!}</p>
                                                </div>
                                            </div>

                            <div class="faq-items-list">
                                @foreach($category->activeFaqs as $faq)
                                    <details class="faq-accordion-item" data-category="cat-{{ $category->id }}">
                                        <summary>
                                            <span class="faq-q-text">{{ $faq->question }}</span>
                                            <span class="faq-icon-bubble">
                                                <i data-lucide="plus"></i>
                                            </span>
                                        </summary>
                                        <div class="faq-answer-body">
                                            <p>{!! nl2br(e($faq->answer)) !!}</p>
                                        </div>
                                    @empty
                                        <div class="py-3 text-muted">No questions in this section yet.</div>
                                    @endforelse
                                </div>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted">Help content will appear here soon.</p>
                    @endforelse
                </div>
                    @endif
                @endforeach
            </div>
        @else
            <!-- CATEGORY FILTER PILLS (CURATED LUXURY FALLBACK) -->
            <div class="faq-category-nav" id="faqCategoryNav">
                <button type="button" class="faq-pill-btn is-active" data-filter="all">
                    All Questions <span class="faq-pill-count">(11)</span>
                </button>
                @foreach($fallbackFaqs as $groupTitle => $groupData)
                    <button type="button" class="faq-pill-btn" data-filter="grp-{{ Str::slug($groupTitle) }}">
                        {{ $groupTitle }} <span class="faq-pill-count">({{ count($groupData['faqs']) }})</span>
                    </button>
                @endforeach
            </div>

            <!-- FAQ LIST (CURATED LUXURY FALLBACK) -->
            <div class="faq-content-area" id="faqContentArea">
                @foreach($fallbackFaqs as $groupTitle => $groupData)
                    <div class="faq-category-group" data-category-group="grp-{{ Str::slug($groupTitle) }}">
                        <div class="faq-category-heading">
                            <h2>{{ $groupTitle }}</h2>
                            <span class="badge">{{ count($groupData['faqs']) }} FAQs</span>
                        </div>

                        <div class="faq-items-list">
                            @foreach($groupData['faqs'] as $faq)
                                <details class="faq-accordion-item" data-category="grp-{{ Str::slug($groupTitle) }}">
                                    <summary>
                                        <span class="faq-q-text">{{ $faq['question'] }}</span>
                                        <span class="faq-icon-bubble">
                                            <i data-lucide="plus"></i>
                                        </span>
                                    </summary>
                                    <div class="faq-answer-body">
                                        <p>{{ $faq['answer'] }}</p>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- NO SEARCH RESULTS FOUND MESSAGE -->
        <div id="faqNoResults" class="faq-no-results text-center py-5" style="display: none;">
            <div class="mb-3">
                <i data-lucide="search-x" style="width: 48px; height: 48px; color: #a47b45;"></i>
            </div>
            <h4 style="font-family: 'Cormorant Upright', serif; font-size: 26px; color: #34271e; margin-bottom: 8px;">No Matching Questions Found</h4>
            <p class="text-muted" style="max-width: 450px; margin: 0 auto 20px; font-size: 14px;">
                We couldn't find an answer matching your search. Please reach out to our fragrance consultants directly.
            </p>
            <button type="button" class="btn-faq-reset" id="faqResetSearchBtn">
                View All FAQs
            </button>
        </div>
    </section>

        <!-- STILL HAVE QUESTIONS CTA CARD -->
        <div class="faq-contact-card">
            <span class="faq-cta-badge">STILL HAVE QUESTIONS?</span>
            <h3 class="faq-cta-heading">We Are Always Here To Help</h3>
            <p class="faq-cta-text">
                Cannot find the answer you are looking for? Our dedicated fragrance specialists are pleased to assist you with personal consultations and order inquiries.
            </p>
            <div class="faq-cta-buttons">
                <a href="{{ route('contact.us') }}" class="faq-cta-btn-primary">
                    <i data-lucide="mail"></i>
                    <span>Contact Support</span>
                </a>
                <a href="tel:07021342265" class="faq-cta-btn-secondary">
                    <i data-lucide="phone"></i>
                    <span>Call 070213 42265</span>
                </a>
                <a href="https://wa.me/917021342265" target="_blank" class="faq-cta-btn-secondary">
                    <i data-lucide="message-circle"></i>
                    <span>WhatsApp Us</span>
                </a>
            </div>
        </div>

    </div>
</main>
@endsection

@push('scripts')
<script>
    $('.faqList .collapse').on('shown.bs.collapse', function (e) {
        var $opentarget = $(this).parent();
        e.stopPropagation();
        $('html,body').animate({scrollTop: $opentarget.offset().top - 120}, 300, 'swing');
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide) {
        window.lucide.createIcons();
    }

    const searchInput = document.getElementById('faqSearchInput');
    const searchClear = document.getElementById('faqSearchClear');
    const resetBtn = document.getElementById('faqResetSearchBtn');
    const categoryBtns = document.querySelectorAll('.faq-pill-btn');
    const faqItems = document.querySelectorAll('.faq-accordion-item');
    const categoryGroups = document.querySelectorAll('.faq-category-group');
    const noResults = document.getElementById('faqNoResults');

    let currentFilter = 'all';

    // Filter FAQs function
    function filterFaqs() {
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        let visibleCount = 0;

        if (searchClear) {
            searchClear.style.display = query.length > 0 ? 'block' : 'none';
        }

        categoryGroups.forEach(group => {
            const groupFilter = group.getAttribute('data-category-group');
            const matchesCategory = (currentFilter === 'all' || currentFilter === groupFilter);
            let groupVisibleItems = 0;

            const itemsInGroup = group.querySelectorAll('.faq-accordion-item');
            itemsInGroup.forEach(item => {
                const qText = item.querySelector('.faq-q-text')?.textContent.toLowerCase() || '';
                const aText = item.querySelector('.faq-answer-body')?.textContent.toLowerCase() || '';
                const matchesSearch = query === '' || qText.includes(query) || aText.includes(query);

                if (matchesCategory && matchesSearch) {
                    item.style.display = '';
                    groupVisibleItems++;
                    visibleCount++;

                    // Auto expand if user searched specifically
                    if (query.length >= 3) {
                        item.setAttribute('open', 'true');
                    }
                } else {
                    item.style.display = 'none';
                }
            });

            // Show or hide the whole category section
            if (groupVisibleItems > 0) {
                group.style.display = '';
            } else {
                group.style.display = 'none';
            }
        });

        if (noResults) {
            noResults.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    // Category button click
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            categoryBtns.forEach(b => b.classList.remove('is-active'));
            this.classList.add('is-active');
            currentFilter = this.getAttribute('data-filter');
            filterFaqs();
        });
    });

    // Search input typing
    if (searchInput) {
        searchInput.addEventListener('input', filterFaqs);
    }

    // Search clear button
    if (searchClear) {
        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            filterFaqs();
            searchInput.focus();
        });
    }

    // Reset button in no results
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            const allBtn = document.querySelector('.faq-pill-btn[data-filter="all"]');
            if (allBtn) {
                categoryBtns.forEach(b => b.classList.remove('is-active'));
                allBtn.classList.add('is-active');
                currentFilter = 'all';
            }
            filterFaqs();
        });
    }
});
</script>
@endpush
