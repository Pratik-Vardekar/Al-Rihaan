@extends('layouts.front')

@section('title', $compliance->meta_title ?? ($compliance->title ?? 'Compliance | Al Rihan Perfumes'))
@section('meta_description',
    $compliance->meta_description ??
    'Al Rihan Perfumes compliance, quality standards, and
    ethical sourcing.')
@section('meta_keywords', $compliance->meta_keywords ?? 'compliance, perfume safety, al rihan')

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
                        <span class="breadcrumb-item active" aria-current="page">Compliance</span>
                    </nav>
                    <h1 class="sectTitle fw-bold txtred">{{ $compliance->title ?? 'Compliance & Quality Standards' }}</h1>
                </div>
                <div class="mx-auto generalContent" style="max-width:1000px;">
                    @if ($compliance && filled($compliance->content))
                        {!! $compliance->content !!}
                    @else
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i> Compliance information will be updated soon.
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>
@endsection
