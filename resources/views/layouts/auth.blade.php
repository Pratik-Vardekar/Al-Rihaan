<!doctype html>
<html lang="en">

<head>
    <title>Admin Login | AI Rihan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- App favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('frontend/assets/images/logoairihan.svg') }}">
    <link rel="shortcut icon" type="image/svg+xml" href="{{ asset('frontend/assets/images/logoairihan.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('frontend/assets/images/logoairihan.svg') }}">

    <!-- Theme Config Js -->
    <script src="{{ asset('backend/assets/js/config.js') }}"></script>

    <!-- Vendor css -->
    <link href="{{ asset('backend/assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link id="app-style" href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Al Rihan Admin Styles -->
    <link href="{{ asset('backend/assets/css/al-rihan-admin.css') }}?v={{ @filemtime(base_path('backend/assets/css/al-rihan-admin.css')) ?: time() }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/css/watch-admin.css') }}?v={{ @filemtime(base_path('backend/assets/css/watch-admin.css')) ?: time() }}" rel="stylesheet" type="text/css" />
</head>

<body style="background-color: #FAF7F2;">
    {{-- <div class="position-absolute top-0 end-0">
            <img src="assets/images/auth-card-bg.svg')}}" class="auth-card-bg-img" alt="auth-card-bg" />
        </div>
        <div class="position-absolute bottom-0 start-0" style="transform: rotate(180deg)">
            <img src="{{asset('admin/assets/images/auth-card-bg.svg')}}" class="auth-card-bg-img" alt="auth-card-bg" />
        </div> --}}
    @yield('content')
    <!-- end auth-fluid-->

    <!-- Vendor js -->
    <script src="{{ asset('backend/assets/js/vendors.min.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('backend/assets/js/app.js') }}"></script>


    <!-- Password Suggestion Js -->
    <script src="{{ asset('backend/assets/js/pages/auth-password.js') }}"></script>
</body>

</html>
