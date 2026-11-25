@php
    if (!isset($categories)) {
        $categories = \App\Models\Category::where('status', 1)->withStartingFromPrice()->withCount('products')->orderBy('sort_order')->orderBy('id')->get();
    }
    if (!isset($brands)) {
        $brands = \App\Models\Brand::where('status', 1)->whereHas('products', fn($q) => $q->where('status', 1))->orderBy('name')->get();
    }
    $listingTitle = $listingTitle ?? 'Combo Offers';
    $listingSubtitle = $listingSubtitle ?? 'Explore our exclusive fragrance sets and curated bundle offers.';
    $breadcrumbLabel = $breadcrumbLabel ?? 'Combo Offers';
    $filterAction = $filterAction ?? route('combo.offers');
    $clearFilterUrl = $clearFilterUrl ?? route('combo.offers');
    $filter = $filter ?? 'combo_offers';
@endphp
@include('frontend.pages.product_listing')
