@php
    if (!isset($categories)) {
        $categories = \App\Models\Category::where('status', 1)->withStartingFromPrice()->withCount('products')->orderBy('sort_order')->orderBy('id')->get();
    }
    if (!isset($brands)) {
        $brands = \App\Models\Brand::where('status', 1)->whereHas('products', fn($q) => $q->where('status', 1))->orderBy('name')->get();
    }
    $listingTitle = $listingTitle ?? 'Gifting Collection';
    $listingSubtitle = $listingSubtitle ?? 'Thoughtfully curated fragrance combo sets crafted to delight your loved ones on every special occasion.';
    $breadcrumbLabel = $breadcrumbLabel ?? 'Gifting';
    $filterAction = $filterAction ?? route('gifting');
    $clearFilterUrl = $clearFilterUrl ?? route('gifting');
    $filter = $filter ?? 'gifting';
@endphp
@include('frontend.pages.product_listing')
