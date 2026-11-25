{{-- Product card slide wrapper (home slider + listing grid) --}}
<div class="{{ $wrapperClass ?? 'swiper-slide' }}">
    @include('frontend.partials.product-card', ['product' => $product])
</div>
