@extends('layouts.front')

@section('title',
    $shippingPolicy->meta_title ??
    ($shippingPolicy->title ??
    'Shipping & Delivery Policy | Al Rihan
    Perfumes'))
@section('meta_description',
    $shippingPolicy->meta_description ??
    'Shipping and delivery policy, estimated timelines,
    and order tracking at Al Rihan Perfumes.')
@section('meta_keywords', $shippingPolicy->meta_keywords ?? 'shipping policy, delivery, al rihan perfume delivery')

@section('content')
    <main class="pageWrapper">
        <div class="cloudWrap position-relative d-none d-lg-block">
            <div class="container clouds position-relative"></div>
        </div>
        <section class="py-5 overflow-hidden">
            <div class="container mb-5">
                <div class="text-center">
                    <nav class="breadcrumb justify-content-center mb-0 fw-semibold">
                        <a class="breadcrumb-item" href="{{ route('home') }}">Home</a>
                        <span class="breadcrumb-item active" aria-current="page">Shipping & Delivery</span>
                    </nav>
                    <h1 class="sectTitle fw-bold txtred">{{ $shippingPolicy->title ?? 'Shipping & Delivery Policy' }}</h1>
                </div>
                <div class="mx-auto generalContent" style="max-width:1000px;">
                    @if ($shippingPolicy && filled($shippingPolicy->content))
                        {!! $shippingPolicy->content !!}
                    @else
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i> Shipping &amp; Delivery information will be updated
                            soon.
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>
@endsection
