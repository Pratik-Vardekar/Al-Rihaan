@forelse($products as $product)
    @include('frontend.partials.product-card', ['product' => $product, 'wishlistIds' => $wishlistIds ?? []])
@empty
    <div class="col-12 py-5 text-center text-muted" style="grid-column: 1 / -1;">
        <div class="p-5 bg-white rounded shadow-sm">
            <i data-lucide="search-x"
                style="width: 48px; height: 48px; stroke-width: 1.5; color: #a47b45; margin-bottom: 1rem;"></i>
            <h3 class="mb-2" style="font-size: 1.25rem; font-weight: 600;">No fragrances found</h3>
            <p class="mb-4 text-muted">Try adjusting your filters or browse all our signature
                fragrances.</p>
            <a href="{{ route('product.listing') }}" class="apply-filters"
                style="display: inline-block; text-decoration: none; max-width: 200px; margin: 0 auto;">
                View All Fragrances
            </a>
        </div>
    </div>
@endforelse
