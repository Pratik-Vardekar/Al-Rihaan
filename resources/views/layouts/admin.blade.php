<!DOCTYPE html>
<html lang="en" data-menu-color="light" data-topbar-color="light" data-bs-theme="light">

<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Admin Dashboard') | AI Rihan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="AI Rihan - Admin Panel" />
    <meta name="keywords" content="AI Rihan, perfumes, fragrances, admin, CMS, dashboard" />
    <meta name="author" content="AI Rihan" />

    <!-- App favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('frontend/assets/images/logoairihan.svg') }}">
    <link rel="shortcut icon" type="image/svg+xml" href="{{ asset('frontend/assets/images/logoairihan.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('frontend/assets/images/logoairihan.svg') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <!-- Theme Config Js -->
    <script src="{{ asset('backend/assets/js/config.js') }}"></script>

    <!-- Vendor css -->
    <link href="{{ asset('backend/assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link id="app-style" href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Al Rihan Admin Styles -->
    <link href="{{ asset('backend/assets/css/al-rihan-admin.css') }}?v={{ @filemtime(base_path('backend/assets/css/al-rihan-admin.css')) ?: time() }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/css/watch-admin.css') }}?v={{ @filemtime(base_path('backend/assets/css/watch-admin.css')) ?: time() }}" rel="stylesheet" type="text/css" />

    @stack('styles')
</head>

<body class="watch-admin alrihan-admin">
    <!-- Begin page -->
    <div class="wrapper">
        @include('admin.partials.header')

        @include('admin.partials.sidebar')

        <!-- ============================================================== -->
        <!-- Start Main Content -->
        <!-- ============================================================== -->
        <div class="content-page bg-light">
            <div class="container-fluid">
                @yield('content')
            </div>
            <!-- container -->

            @include('admin.partials.footer')
        </div>
        <!-- ============================================================== -->
        <!-- End of Main Content -->
        <!-- ============================================================== -->
    </div>
    <!-- END wrapper -->

    {{-- @include('admin.partials.theme-settings') --}}

    <!-- Vendor js -->
    <script src="{{ asset('backend/assets/js/vendors.min.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('backend/assets/js/app.js') }}"></script>

    <!-- Apex Chart js -->
    <script src="{{ asset('backend/assets/plugins/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Custom table -->
    <script src="{{ asset('backend/assets/js/pages/custom-table.js') }}"></script>
    {{-- <script src="{{ asset('backend/assets/js/pages/dashboard-crm.js') }}"></script> --}}


    @stack('scripts')

    <!-- Page specific dashboard js -->
    @yield('page-scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                iconColor: '#198754',
                title: 'Success',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false,
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                timer: 2500,
                showConfirmButton: false
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: `{!! implode('<br>', $errors->all()) !!}`,
            });
        </script>
    @endif
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js" defer></script>

</body>

</html>
