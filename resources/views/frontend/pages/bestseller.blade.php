@php
    if (!isset($categories)) {
        $categories = \App\Models\Category::where('status', 1)->withStartingFromPrice()->withCount('products')->orderBy('sort_order')->orderBy('id')->get();
    }
    if (!isset($brands)) {
        $brands = \App\Models\Brand::where('status', 1)->whereHas('products', fn($q) => $q->where('status', 1))->orderBy('name')->get();
    }
    $listingTitle = $listingTitle ?? 'Best Sellers';
    $listingSubtitle = $listingSubtitle ?? 'Discover our best-selling fragrances, loved by customers for their style, quality, and popularity.';
    $breadcrumbLabel = $breadcrumbLabel ?? 'Best Sellers';
    $filterAction = $filterAction ?? route('best.sellers');
    $clearFilterUrl = $clearFilterUrl ?? route('best.sellers');
    $filter = $filter ?? 'best_selling';
@endphp
@include('frontend.pages.product_listing')
