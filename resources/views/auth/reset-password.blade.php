@extends('layouts.front')

@section('title', 'Set New Password')

@section('content')
    <main class="pageWrapper d-flex">

        <section class="w-100 d-flex position-relative">

            <div
                class="w-100 h-25 position-absolute top-0 bottom-0 m-auto bg-secondary-subtle rounded-0 progress progress-bar-striped progress-bar-animated">
            </div>

            <div class="container d-flex py-sm-5 py-4 justify-content-center align-items-center position-relative z-3">

                <div class="loginWrap bg-white shadow-lg d-flex flex-wrap justify-content-between rounded-3 overflow-hidden">

                    <div class="col-12 px-md-5 px-4 py-4 align-self-center">
                        <h1 class="resetTitle mb-3 d-inline-flex align-items-center gap-3">Set New Password</h1>
                        <small class="mb-4 d-block lh-sm custom-desc">
                            Enter your email and choose a new password for your account.
                        </small>

                        @if (session('status'))
                            <div class="alert alert-success mb-3" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger mb-3" role="alert">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.store') }}" class="row g-3 formFormat"
                            id="setNewPasswordForm">
                            @csrf

                            <input type="hidden" name="token" value="{{ $request->route('token') }}">

                            <div class="col-12">
                                <input type="email"
                                    class="form-control @error('email') is-invalid @enderror emailInputBox" name="email"
                                    id="email" placeholder="Email id" value="{{ old('email', $request->email) }}"
                                    required autofocus autocomplete="username" style="background:#e8f0fe;" />
                                @error('email')
                                    <div class="text-danger mt-1" style="font-size:.875rem;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="position-relative">
                                    <input type="password"
                                        class="form-control @error('password') is-invalid @enderror emailInputBox"
                                        name="password" id="password" placeholder="New Password" required
                                        autocomplete="new-password" minlength="8" style="background:#e8f0fe;" />
                                    <button type="button" class="passwordToggle" aria-label="Show/Hide password"></button>
                                </div>
                                <div class="password-requirements mt-2" style="font-size: 12px; color: #6c757d;">
                                    <div class="req-item" id="req-length">
                                        <span class="req-icon">✕</span> Minimum 8 characters
                                    </div>
                                    <div class="req-item" id="req-uppercase">
                                        <span class="req-icon">✕</span> At least 1 uppercase (A-Z)
                                    </div>
                                    <div class="req-item" id="req-lowercase">
                                        <span class="req-icon">✕</span> At least 1 lowercase (a-z)
                                    </div>
                                    <div class="req-item" id="req-number">
                                        <span class="req-icon">✕</span> At least 1 number (0-9)
                                    </div>
                                    <div class="req-item" id="req-special">
                                        <span class="req-icon">✕</span> At least 1 special character (@ # $ % &amp; * !)
                                    </div>
                                </div>
                                @error('password')
                                    <div class="text-danger mt-1" style="font-size:.875rem;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="position-relative">
                                    <input type="password"
                                        class="form-control @error('password_confirmation') is-invalid @enderror emailInputBox"
                                        name="password_confirmation" id="password_confirmation"
                                        placeholder="Confirm New Password" required autocomplete="new-password"
                                        minlength="8" style="background:#e8f0fe;" />
                                    <button type="button" class="passwordToggle" aria-label="Show/Hide password"></button>
                                </div>
                                @error('password_confirmation')
                                    <div class="text-danger mt-1" style="font-size:.875rem;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 text-center">
                                <button type="submit" class="ctaBtn">Reset Password</button>
                            </div>

                            <div class="col-12 mt-3 text-center">
                                <a href="{{ route('login') }}" class="fw-medium">Back to Sign In</a>
                            </div>
                        </form>
                    </div>

                </div>

            </div>

        </section>

    </main>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const toggleButtons = document.querySelectorAll('.passwordToggle');

            toggleButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const input = this.closest('.position-relative').querySelector(
                        'input[type="password"], input[type="text"]');
                    if (input) {
                        if (input.type === 'password') {
                            input.type = 'text';
                            this.classList.add('show');
                        } else {
                            input.type = 'password';
                            this.classList.remove('show');
                        }
                    }
                });
            });

            // Password validation function
            function validatePassword(password) {
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[@#$%&*!]/.test(password)
                };
                return requirements;
            }

            // Update password requirements UI
            function updatePasswordRequirements(password) {
                const reqs = validatePassword(password);

                const reqMap = {
                    'req-length': reqs.length,
                    'req-uppercase': reqs.uppercase,
                    'req-lowercase': reqs.lowercase,
                    'req-number': reqs.number,
                    'req-special': reqs.special
                };

                Object.keys(reqMap).forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        const icon = element.querySelector('.req-icon');
                        if (reqMap[id]) {
                            element.classList.remove('invalid');
                            element.classList.add('valid');
                            icon.textContent = '✓';
                        } else {
                            element.classList.remove('valid');
                            element.classList.add('invalid');
                            icon.textContent = '✕';
                        }
                    }
                });

                // Check if all requirements are met
                const allValid = Object.values(reqMap).every(v => v === true);
                const passwordInput = document.getElementById('password');
                if (passwordInput) {
                    if (allValid && passwordInput.value.length > 0) {
                        passwordInput.classList.remove('is-invalid');
                    } else if (passwordInput.value.length > 0) {
                        passwordInput.classList.add('is-invalid');
                    }
                }
                return allValid;
            }

            // Real-time password validation
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    updatePasswordRequirements(this.value);
                });
            }

            // Email validation on blur
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (this.value.length > 0 && !emailRegex.test(this.value)) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });

                emailInput.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            }

            // Form validation
            const form = document.getElementById('setNewPasswordForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const email = document.getElementById('email');
                    const password = document.getElementById('password');
                    const confirmPassword = document.getElementById('password_confirmation');
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    // Validate email
                    if (email && !emailRegex.test(email.value)) {
                        e.preventDefault();
                        email.classList.add('is-invalid');
                        alert('Please enter a valid email address.');
                        email.focus();
                        return false;
                    }

                    // Validate password with all requirements
                    const reqs = validatePassword(password.value);
                    const allValid = Object.values(reqs).every(v => v === true);

                    if (password && !allValid) {
                        e.preventDefault();
                        password.classList.add('is-invalid');
                        alert(
                            'Password must meet all requirements:\n- Minimum 8 characters\n- At least 1 uppercase (A-Z)\n- At least 1 lowercase (a-z)\n- At least 1 number (0-9)\n- At least 1 special character (@ # $ % & * !)'
                        );
                        password.focus();
                        return false;
                    }

                    // Validate password match
                    if (password && confirmPassword && password.value !== confirmPassword.value) {
                        e.preventDefault();
                        confirmPassword.classList.add('is-invalid');
                        alert('Passwords do not match. Please try again.');
                        confirmPassword.focus();
                        return false;
                    }

                    return true;
                });
            }

            // Clear validation on input
            document.querySelectorAll('.emailInputBox').forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            });

            // Initialize password requirements (show as invalid initially)
            const initialPassword = document.getElementById('password');
            if (initialPassword) {
                updatePasswordRequirements('');
            }
        });
    </script>
@endpush
