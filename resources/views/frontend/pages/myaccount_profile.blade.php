@extends('layouts.front')
@section('title', 'My Profile | Al Rihan Luxury Perfumes')

@section('content')
    <main class="pageWrapper profile-page">

        <section class="py-5">
            <div class="container">

                <div class="row justify-content-between mb-5">
                    @include('frontend.partials.myaccount_nav', ['active' => 'profile', 'user' => $user])

                    <div class="col-lg-9 mb-lg-4">

                        <h2 class="sectTitle lh-1 mb-4">My Profile</h2>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="profileFormCard p-4 p-md-5 bg-white rounded-4 border shadow-sm"
                            style="max-width:540px;">
                            <form action="{{ route('profile.profile.update') }}" method="POST" class="row g-4 formFormat"
                                id="profileForm">
                                @csrf
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-dark">Full Name <span
                                            class="txtRed">*</span></label>
                                    <input type="text" class="form-control" name="name" placeholder="Full Name*"
                                        value="{{ old('name', $user->name) }}" required />
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-dark">Email Id <span
                                            class="txtRed">*</span></label>
                                    <input type="email" class="form-control" name="email" placeholder="Email Id*"
                                        value="{{ old('email', $user->email) }}" required />
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-dark">Mobile No.</label>
                                    <input type="tel" class="form-control" name="phone" placeholder="Mobile No."
                                        value="{{ old('phone', $user->phone) }}" />
                                </div>
                                <div class="col-12 mt-4 d-flex align-items-center gap-3 flex-wrap">
                                    <button type="submit" class="ctaBtn">Save Changes</button>
                                    <button type="button" id="logoutBtn" class="ctaBtn dark border-0">Logout</button>
                                </div>
                            </form>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>

                    </div>
                </div>

            </div>
        </section>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var logoutBtn = document.getElementById('logoutBtn');
            var logoutForm = document.getElementById('logout-form');
            if (logoutBtn && logoutForm) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    logoutForm.submit();
                });
            }
        });
    </script>
@endsection
