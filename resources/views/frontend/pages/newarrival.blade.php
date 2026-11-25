@php
    if (!isset($categories)) {
        $categories = \App\Models\Category::where('status', 1)->withStartingFromPrice()->withCount('products')->orderBy('sort_order')->orderBy('id')->get();
    }
    if (!isset($brands)) {
        $brands = \App\Models\Brand::where('status', 1)->whereHas('products', fn($q) => $q->where('status', 1))->orderBy('name')->get();
    }
    $listingTitle = $listingTitle ?? 'New Arrivals';
    $listingSubtitle = $listingSubtitle ?? 'Explore our newest fragrance arrivals and latest luxury collections.';
    $breadcrumbLabel = $breadcrumbLabel ?? 'New Arrivals';
    $filterAction = $filterAction ?? route('new.arrivals');
    $clearFilterUrl = $clearFilterUrl ?? route('new.arrivals');
    $filter = $filter ?? 'new_arrival';
@endphp
@include('frontend.pages.product_listing')
