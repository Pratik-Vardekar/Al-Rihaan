@if (method_exists($products, 'hasPages') && $products->hasPages())
    <nav class="shop-pagination" aria-label="Product pages">
        @if ($products->onFirstPage())
            <button class="page-arrow" type="button" aria-label="Previous page" disabled>
                <i data-lucide="chevron-left"></i>
            </button>
        @else
            <button class="page-arrow" type="button" aria-label="Previous page"
                data-page-url="{{ $products->previousPageUrl() }}">
                <i data-lucide="chevron-left"></i>
            </button>
        @endif

        @foreach ($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
            <button class="page-number {{ $page == $products->currentPage() ? 'active' : '' }}"
                type="button" aria-current="{{ $page == $products->currentPage() ? 'page' : 'false' }}"
                data-page-url="{{ $url }}">
                {{ $page }}
            </button>
        @endforeach

        @if ($products->hasMorePages())
            <button class="page-arrow" type="button" aria-label="Next page"
                data-page-url="{{ $products->nextPageUrl() }}">
                <i data-lucide="chevron-right"></i>
            </button>
        @else
            <button class="page-arrow" type="button" aria-label="Next page" disabled>
                <i data-lucide="chevron-right"></i>
            </button>
        @endif
    </nav>
@endif
