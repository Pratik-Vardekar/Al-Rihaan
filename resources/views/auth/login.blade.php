@extends('layouts.front')

@section('title', 'Login')

@section('content')

    <main class="pageWrapper d-flex">

        <section class="w-100 d-flex position-relative">

            <div class="container d-flex py-sm-5 py-4 justify-content-center align-items-center position-relative z-3">

                <div class="loginWrap bg-white shadow-lg d-flex flex-wrap justify-content-between rounded-3 overflow-hidden">

                    <div class="col-12 px-md-5 px-4 py-4 align-self-center">

                        <h1 class="sectTitle mb-4 d-inline-flex align-items-center gap-3 ">
                            Login
                        </h1>

                        <form method="POST" action="{{ route('login') }}" class="row g-4 formFormat">
                            @csrf

                            {{-- Email --}}
                            <div class="col-12">

                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    name="email" id="email" placeholder="Email Id" value="{{ old('email') }}"
                                    required autofocus autocomplete="email" />

                                @error('email')
                                    <div class="text-danger mt-1" style="font-size:.875rem;">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            {{-- Password --}}
                            <div class="col-12">

                                <div class="position-relative">

                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        name="password" id="password" placeholder="Password" required
                                        autocomplete="current-password" />

                                    <button type="button" class="passwordBtn" tabindex="-1" aria-label="Show password"
                                        aria-pressed="false">
                                    </button>

                                </div>

                                @error('password')
                                    <div class="text-danger mt-1" style="font-size:.875rem;">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small>
                                    <a href="{{ route('password.request') }}" class="mt-1 float-end">
                                        Forgot Password?
                                    </a>
                                </small>

                            </div>

                            {{-- Login Button --}}
                            <div class="col-12 text-center">

                                <button type="submit" class="ctaBtn">
                                    <span class="btnTxt">Login</span>
                                    <span class="arw"></span>
                                </button>

                            </div>

                            {{-- Register Link --}}
                            <div class="col-12 mt-5 text-center">

                                Didn't have an account?

                                <a href="{{ route('register') }}" class="fw-semibold">
                                    Signup
                                </a>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </section>

    </main>

    {{-- Password Show / Hide Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const eyeSvg =
                'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23666\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpath d=\'M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z\'/%3E%3Ccircle cx=\'12\' cy=\'12\' r=\'3.5\'/%3E%3C/svg%3E") center/contain no-repeat';

            const eyeSlashSvg =
                'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'24\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%23666\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpath d=\'M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94\'/%3E%3Cpath d=\'M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M14.11 14.12a3 3 0 1 1-4.24-4.24\'/%3E%3Cline x1=\'1\' y1=\'1\' x2=\'23\' y2=\'23\'/%3E%3C/svg%3E") center/contain no-repeat';

            const passwordButtons = document.querySelectorAll('.passwordBtn');

            passwordButtons.forEach(function(button) {

                const wrapper = button.closest('.position-relative');
                const input = wrapper.querySelector('input');

                // Initial eye icon
                button.style.background = eyeSlashSvg;
                button.style.width = '24px';
                button.style.height = '24px';
                button.style.border = 'none';
                button.style.position = 'absolute';
                button.style.top = '50%';
                button.style.right = '10px';
                button.style.transform = 'translateY(-50%)';
                button.style.zIndex = '3';
                button.style.cursor = 'pointer';
                button.style.backgroundColor = 'transparent';
                button.style.padding = '0';

                button.addEventListener('click', function() {

                    if (input.type === 'password') {

                        input.type = 'text';

                        button.style.background = eyeSvg;
                        button.setAttribute('aria-label', 'Hide password');
                        button.setAttribute('aria-pressed', 'true');

                    } else {

                        input.type = 'password';

                        button.style.background = eyeSlashSvg;
                        button.setAttribute('aria-label', 'Show password');
                        button.setAttribute('aria-pressed', 'false');

                    }

                });

            });

        });
    </script>

@endsection
