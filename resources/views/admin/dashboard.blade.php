@extends('layouts.admin')

@section('title', 'Dashboard - Admin Panel')

@section('content')
    <div class="container-fluid">

        {{-- ── Page Title ─────────────────────────────────────────── --}}
        <div class="page-title-head d-flex align-items-center justify-content-between mb-3 pt-2">
            <div class="luxury-page-heading">
                <span class="luxury-title-bar"></span>
                <h4 class="luxury-main-title">Al Rihan CMS</h4>
            </div>

            <div class="text-end">
                <ol class="breadcrumb m-0 py-0 align-items-center">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}"
                            style="color: #7A695E; font-size: 13px; text-decoration: none;">
                            <i class="ti ti-smart-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active" style="color: #20140D; font-weight: 600; font-size: 13px;">Dashboard
                    </li>
                </ol>
            </div>
        </div>

        {{-- ── Top Stat Cards (4 Cards Matching Al Rihan Mockup) ─────── --}}
        <div class="row g-3 mb-4">
            <!-- Card 1: Total Products -->
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('product.index') }}" class="text-decoration-none">
                    <div class="luxury-stat-card">
                        <div class="luxury-stat-content">
                            <div class="luxury-stat-top">
                                <div class="luxury-stat-icon">
                                    <i class="ti ti-package"></i>
                                </div>
                                <div class="luxury-stat-info">
                                    <div class="luxury-stat-title">Total Products</div>
                                    <div class="luxury-stat-value">{{ number_format($totalProducts) }}</div>
                                </div>
                            </div>
                            <div class="luxury-stat-trend">
                                <span class="trend-up"><i class="ti ti-arrow-up-right"></i> +12%</span>
                                <span class="trend-sub">vs last month</span>
                            </div>
                        </div>
                        <div class="luxury-stat-art">
                            <img src="{{ asset('backend/assets/images/dashboard/card-perfume.svg') }}" alt="" />
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card 2: Total Orders -->
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('order.index') }}" class="text-decoration-none">
                    <div class="luxury-stat-card">
                        <div class="luxury-stat-content">
                            <div class="luxury-stat-top">
                                <div class="luxury-stat-icon">
                                    <i class="ti ti-shopping-cart"></i>
                                </div>
                                <div class="luxury-stat-info">
                                    <div class="luxury-stat-title">Total Orders</div>
                                    <div class="luxury-stat-value">{{ number_format($totalOrders) }}</div>
                                </div>
                            </div>
                            <div class="luxury-stat-trend">
                                <span class="trend-up"><i class="ti ti-arrow-up-right"></i> +8%</span>
                                <span class="trend-sub">vs last month</span>
                            </div>
                        </div>
                        <div class="luxury-stat-art">
                            <img src="{{ asset('backend/assets/images/dashboard/card-orders.svg') }}" alt="" />
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card 3: Customer Reviews -->
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('admin.review.index') }}" class="text-decoration-none">
                    <div class="luxury-stat-card">
                        <div class="luxury-stat-content">
                            <div class="luxury-stat-top">
                                <div class="luxury-stat-icon">
                                    <i class="ti ti-star"></i>
                                </div>
                                <div class="luxury-stat-info">
                                    <div class="luxury-stat-title">Customer Reviews</div>
                                    <div class="luxury-stat-value">{{ number_format($totalReviews) }}</div>
                                </div>
                            </div>
                            <div class="luxury-stat-trend">
                                <span class="trend-gold">Ratings <i class="fas fa-star text-warning"
                                        style="font-size: 11px;"></i></span>
                                <span class="trend-sub">distribution</span>
                            </div>
                        </div>
                        <div class="luxury-stat-art">
                            <img src="{{ asset('backend/assets/images/dashboard/card-reviews.svg') }}" alt="" />
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card 4: Customers -->
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('customer.index') }}" class="text-decoration-none">
                    <div class="luxury-stat-card">
                        <div class="luxury-stat-content">
                            <div class="luxury-stat-top">
                                <div class="luxury-stat-icon">
                                    <i class="ti ti-users"></i>
                                </div>
                                <div class="luxury-stat-info">
                                    <div class="luxury-stat-title">Customers</div>
                                    <div class="luxury-stat-value">{{ number_format($totalUsers) }}</div>
                                </div>
                            </div>
                            <div class="luxury-stat-trend">
                                <span class="trend-pink">0% <i class="ti ti-arrow-up-right"></i></span>
                                <span class="trend-sub">growth this month</span>
                            </div>
                        </div>
                        <div class="luxury-stat-art">
                            <img src="{{ asset('backend/assets/images/dashboard/card-customers.svg') }}" alt="" />
                        </div>
                    </div>
                </a>
            </div>
        </div>{{-- end stat cards --}}

        {{-- ── Overview + Order Status Donut ──────────────────────── --}}
        <div class="row g-3 mb-4">

            <!-- Overview (Current Year) -->
            <div class="col-xl-8">
                <div class="luxury-main-card h-100">
                    <div class="luxury-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-chart-bar fs-20" style="color: #20140D;"></i>
                            <h5 class="fw-bold mb-0" style="color: #20140D; font-size: 17px;">
                                Overview <span class="text-muted fw-normal fs-14 ms-1">(Current Year)</span>
                            </h5>
                        </div>
                        <div class="overview-toggle-pills">
                            <button type="button" class="btn-toggle-pill overview-filter-btn active"
                                data-mode="revenue">Revenue</button>
                            <button type="button" class="btn-toggle-pill overview-filter-btn"
                                data-mode="orders">Orders</button>
                        </div>
                    </div>

                    <div class="luxury-card-body pt-1">
                        <div class="row align-items-center">
                            <!-- Left 6 KPIs in 2-column grid -->
                            <div class="col-lg-4 col-md-5">
                                <div class="metrics-grid">
                                    <!-- Revenue -->
                                    <div class="metric-item">
                                        <span class="metric-label">Revenue</span>
                                        <div class="metric-value">
                                            <i class="ti ti-wallet fs-16 text-muted"></i>
                                            ₹{{ number_format($totalRevenue) }}
                                        </div>
                                        <span class="metric-badge badge-sand"><i class="ti ti-chevron-up"></i>
                                            Total</span>
                                    </div>

                                    <!-- Orders -->
                                    <div class="metric-item">
                                        <span class="metric-label">Orders</span>
                                        <div class="metric-value">
                                            <i class="ti ti-shopping-cart fs-16 text-muted"></i>
                                            {{ number_format($totalOrders) }}
                                        </div>
                                        <span class="metric-badge badge-sand"><i class="ti ti-chevron-up"></i> All
                                            Time</span>
                                    </div>

                                    <!-- Total Users -->
                                    <div class="metric-item">
                                        <span class="metric-label">Total Users</span>
                                        <div class="metric-value">
                                            <i class="ti ti-users fs-16 text-muted"></i> {{ number_format($totalUsers) }}
                                        </div>
                                        <span class="metric-badge badge-green"><i class="ti ti-chevron-up"></i>
                                            Registered</span>
                                    </div>

                                    <!-- Coupons -->
                                    <div class="metric-item">
                                        <span class="metric-label">Coupons</span>
                                        <div class="metric-value">
                                            <i class="ti ti-ticket fs-16 text-muted"></i>
                                            {{ number_format($totalCoupons) }}
                                        </div>
                                        <span class="metric-badge badge-neutral">Active</span>
                                    </div>

                                    <!-- Sub-Categories -->
                                    <div class="metric-item">
                                        <span class="metric-label">Sub-Categories</span>
                                        <div class="metric-value">
                                            <i class="ti ti-layout-grid fs-16 text-muted"></i>
                                            {{ number_format($totalSubCategories) }}
                                        </div>
                                        <span class="metric-badge badge-sand">Total</span>
                                    </div>

                                    <!-- Sliders -->
                                    <div class="metric-item">
                                        <span class="metric-label">Sliders</span>
                                        <div class="metric-value">
                                            <i class="ti ti-photo fs-16 text-muted"></i>
                                            {{ number_format($totalSliders ?? 0) }}
                                        </div>
                                        <span class="metric-badge badge-sand">Active</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Chart -->
                            <div class="col-lg-8 col-md-7">
                                <div class="custom-chart-legend mb-1">
                                    <span class="legend-item"><span class="legend-dot"
                                            style="background-color: #351F14;"></span> Revenue (₹)</span>
                                    <span class="legend-item"><span class="legend-dot"
                                            style="background-color: #E8BA82;"></span> Orders</span>
                                </div>
                                <div id="dash-revenue-chart" class="apex-charts" style="min-height: 270px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Status -->
            <div class="col-xl-4">
                <div class="luxury-main-card h-100 position-relative overflow-hidden">
                    <div class="luxury-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-chart-pie fs-20" style="color: #20140D;"></i>
                            <h5 class="fw-bold mb-0" style="color: #20140D; font-size: 17px;">Order Status</h5>
                        </div>
                        <div class="overview-toggle-pills">
                            <button type="button" class="btn-toggle-pill donut-toggle active"
                                data-type="order">Order</button>
                            <button type="button" class="btn-toggle-pill donut-toggle"
                                data-type="payment">Payment</button>
                        </div>
                    </div>

                    <div class="luxury-card-body position-relative pt-1" style="z-index: 2;">
                        <div id="most-leads-chart" class="apex-charts" style="min-height: 220px;"></div>
                        <div class="order-status-legend mt-2" id="donut-legend-row"></div>
                    </div>
                </div>
            </div>

        </div>{{-- end overview row --}}

        {{-- ── Recent Orders Table ─────────────────────────────────── --}}
        <div class="row">
            <div class="col-xl-12">
                <div data-table data-table-rows-per-page="5" class="card">
                    <div class="card-header border-light justify-content-between">
                        <h4 class="card-title">Recent Orders</h4>
                        <div class="d-flex align-items-center gap-2">
                            <span class="me-2 fw-semibold">Filter By:</span>
                            <div class="app-search">
                                <select data-table-filter="order-status" class="form-select form-control my-1 my-md-0">
                                    <option value="All">Order Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <i class="ti ti-briefcase app-search-icon text-muted"></i>
                            </div>
                            <div class="app-search">
                                <input data-table-search type="search" class="form-control"
                                    placeholder="Search orders..." />
                                <i class="ti ti-search app-search-icon text-muted"></i>
                            </div>
                            <div>
                                <select data-table-set-rows-per-page class="form-select form-control my-1 my-md-0">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-nowrap table-centered table-select table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th data-table-sort>Order ID</th>
                                    <th data-table-sort>Customer</th>
                                    <th data-table-sort>Date</th>
                                    <th data-table-sort>Amount</th>
                                    <th data-table-sort>Payment</th>
                                    <th data-table-sort data-column="order-status">Order Status</th>
                                    <th>•••</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    @php
                                        $orderStatusMap = [
                                            'pending' => 'bg-warning-subtle text-warning',
                                            'processing' => 'bg-info-subtle text-info',
                                            'shipped' => 'bg-primary-subtle text-primary',
                                            'delivered' => 'bg-success-subtle text-success',
                                            'cancelled' => 'bg-danger-subtle text-danger',
                                        ];
                                        $paymentStatusMap = [
                                            'pending' => 'bg-warning-subtle text-warning',
                                            'paid' => 'bg-success-subtle text-success',
                                            'failed' => 'bg-danger-subtle text-danger',
                                            'refunded' => 'bg-secondary-subtle text-secondary',
                                        ];
                                        $oClass = $orderStatusMap[$order->order_status] ?? 'text-bg-light';
                                        $pClass = $paymentStatusMap[$order->payment_status] ?? 'text-bg-light';
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('order.show', $order->id) }}"
                                                class="fw-medium text-primary" title="View Order Details">
                                                #{{ $order->order_id }}
                                            </a>
                                        </td>
                                        <td>
                                            @if ($order->user)
                                                <div class="avatar-xs d-inline-block me-1">
                                                    <span
                                                        class="avatar-title bg-primary-subtle text-primary fw-bold rounded-circle">
                                                        {{ strtoupper(substr($order->user->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <a href="#!" class="text-reset">{{ $order->user->name }}</a>
                                            @else
                                                <span class="text-muted">Guest</span>
                                            @endif
                                        </td>
                                        <td>{{ $order->created_at->format('d M, Y') }}</td>
                                        <td>
                                            <div class="text-nowrap">₹{{ number_format($order->grand_total, 2) }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $pClass }} p-1 text-capitalize">
                                                {{ $order->payment_status }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $oClass }} p-1 text-capitalize">
                                                {{ $order->order_status }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('order.show', $order->id) }}" class="text-muted fs-20"
                                                title="View details">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No orders found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div data-table-pagination-info="orders"></div>
                            <div data-table-pagination></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>{{-- end orders --}}

        {{-- ── Latest Products + Recent Users + Activity ──────────── --}}
        <div class="row">

            {{-- Latest Products --}}
            <div class="col-xl-4 col-lg-12">
                <div data-table data-table-rows-per-page="5" class="card">
                    <div class="card-header border-light justify-content-between">
                        <h4 class="card-title">Latest Products</h4>
                        <div class="app-search">
                            <input data-table-search type="search" class="form-control" placeholder="Search..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-nowrap table-centered table-select table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>Product</th>
                                    <th data-table-sort>Price</th>
                                    <th data-table-sort>Stock</th>
                                    <th>•••</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestProducts as $product)
                                    <tr>
                                        <td>
                                            <h5 class="fs-14 mb-0 fw-normal">{{ Str::limit($product->name, 22) }}</h5>
                                            <span class="text-muted fs-12">{{ $product->category->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>₹{{ number_format($product->selling_price, 2) }}</td>
                                        <td>{{ $product->quantity ?? 0 }}</td>
                                        <td>
                                            <a href="{{ route('product.edit', $product->id) }}" class="text-muted fs-20"
                                                title="Edit product">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No products yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div data-table-pagination-info="products"></div>
                            <div data-table-pagination></div>
                        </div>
                    </div>
                </div>
            </div>



            {{-- Recent Users --}}
            <div class="col-xl-4 col-lg-6">
                <div class="card">
                    <div class="card-header justify-content-between">
                        <h4 class="card-title">Recent Users</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive mb-n1">
                            <table class="table table-nowrap table-borderless table-centered mb-0">
                                <thead class="bg-light bg-opacity-50 thead-sm">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentUsers as $user)
                                        <tr>
                                            <td>
                                                <div class="avatar-xs d-inline-block me-1">
                                                    <span
                                                        class="avatar-title bg-primary-subtle text-primary fw-bold rounded-circle">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <span class="align-middle">{{ $user->name }}</span>
                                            </td>
                                            <td class="text-muted fs-12">{{ Str::limit($user->email, 20) }}</td>
                                            <td class="text-muted fs-12">{{ $user->created_at->format('d M Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">No users found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="col-xl-4 col-lg-6">
                <div class="card card-h-100 border shadow-sm rounded-4 transition-card">
                    <div class="card-header justify-content-between">
                        <h4 class="card-title">Recent Activity</h4>

                    </div>
                    <div class="card-body" data-simplebar style="max-height: 426px">
                        <div class="timeline timeline-users">
                            <div class="timeline-item d-flex align-items-stretch">
                                <div class="timeline-dot text-bg-primary"><i class="ti ti-package fs-md"></i></div>
                                <div class="timeline-content ps-3 pb-4">
                                    <h5 class="mb-1">{{ $totalProducts }} Products Listed</h5>
                                    <p class="mb-1 text-muted">Total active products available in the store.</p>
                                    <span class="text-primary fw-semibold">Store Inventory</span>
                                </div>
                            </div>
                            <div class="timeline-item d-flex align-items-stretch">
                                <div class="timeline-dot text-bg-success"><i class="ti ti-truck fs-md"></i></div>
                                <div class="timeline-content ps-3 pb-4">
                                    <h5 class="mb-1">{{ $totalOrders }} Orders Placed</h5>
                                    <p class="mb-1 text-muted">Total orders received across all time.</p>
                                    <span class="text-primary fw-semibold">Order Management</span>
                                </div>
                            </div>
                            <div class="timeline-item d-flex align-items-stretch">
                                <div class="timeline-dot text-bg-warning"><i class="ti ti-users fs-md"></i></div>
                                <div class="timeline-content ps-3 pb-4">
                                    <h5 class="mb-1">{{ $totalUsers }} Registered Users</h5>
                                    <p class="mb-1 text-muted">Users who have signed up on the platform.</p>
                                    <span class="text-primary fw-semibold">User Management</span>
                                </div>
                            </div>
                            <div class="timeline-item d-flex align-items-stretch">
                                <div class="timeline-dot text-bg-info"><i class="ti ti-star fs-md"></i></div>
                                <div class="timeline-content ps-3 pb-4">
                                    <h5 class="mb-1">{{ $totalReviews }} Product Reviews</h5>
                                    <p class="mb-1 text-muted">Customer reviews submitted for products.</p>
                                    <span class="text-primary fw-semibold">Reviews</span>
                                </div>
                            </div>
                            <div class="timeline-item d-flex align-items-stretch">
                                <div class="timeline-dot text-bg-danger"><i class="ti ti-ticket fs-md"></i></div>
                                <div class="timeline-content ps-3 pb-4">
                                    <h5 class="mb-1">{{ $totalCoupons }} Coupons Available</h5>
                                    <p class="mb-1 text-muted">Active discount coupons in the system.</p>
                                    <span class="text-primary fw-semibold">Promotions</span>
                                </div>
                            </div>
                            <div class="timeline-item d-flex align-items-stretch">
                                <div class="timeline-dot text-bg-secondary"><i class="ti ti-article fs-md"></i></div>
                                <div class="timeline-content ps-3">
                                    <h5 class="mb-1">{{ $totalBlogs }} Blog Posts Published</h5>
                                    <p class="mb-1 text-muted">Published articles live on the website.</p>
                                    <span class="text-primary fw-semibold">Content</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end bottom row --}}

    </div>{{-- end container-fluid --}}

@endsection

@section('page-scripts')
    <script>
        // ── PHP → JS data bridge ───────────────────────────────────────────────
        const dashData = {
            months: @json($revenueMonths),
            revenue: @json($revenueChartData),
            orders: @json($ordersChartData),

            // order_status donut
            orderDonutLabels: @json($donutLabels),
            orderDonutSeries: @json($donutSeries),

            // payment_status donut
            paymentDonutLabels: @json($paymentLabels),
            paymentDonutSeries: @json($paymentSeries),

            // sparklines
            catSparkline: @json($categorySparkline),
            prodSparkline: @json($productSparkline),
            blogSparkline: @json($blogSparkline),
            orderSparkline: @json($orderSparkline),

            // new users bar
            newUsersData: @json($newUsersPerMonth->pluck('total')->toArray()),

            // 4 distinct visuals data
            categoryProgress: @json($categoryProgress),
            barLabels: @json($barLabels),
            barValues: @json($barValues),
            donutRatingLabels: @json($donutRatingLabels),
            donutRatingValues: @json($donutRatingValues),
        };

        // ── Color maps keyed by status value ──────────────────────────────────
        const orderColors = {
            pending: '#C5A880',
            processing: '#6B4D36',
            shipped: '#B3804A',
            delivered: '#198754',
            cancelled: '#8C5A32',
        };
        const paymentColors = {
            pending: '#C5A880',
            paid: '#B3804A',
            failed: '#dc3545',
            refunded: '#adb5bd',
        };

        // ── Main Revenue + Orders Chart ────────────────────────────────────────
        if (document.querySelector('#dash-revenue-chart')) {
            const revenueChart = new ApexCharts(document.querySelector('#dash-revenue-chart'), {
                series: [{
                        name: 'Revenue (₹)',
                        type: 'line',
                        data: dashData.revenue
                    },
                    {
                        name: 'Orders',
                        type: 'column',
                        data: dashData.orders
                    }
                ],
                chart: {
                    height: 275,
                    type: 'line',
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit'
                },
                stroke: {
                    width: [2.5, 0],
                    curve: 'smooth'
                },
                plotOptions: {
                    bar: {
                        columnWidth: '42%',
                        borderRadius: 4
                    }
                },
                fill: {
                    opacity: [1, 0.85],
                    type: ['solid', 'solid']
                },
                markers: {
                    size: [4, 0],
                    strokeColors: '#351F14',
                    strokeWidth: 2,
                    colors: ['#FFFFFF'],
                    hover: {
                        size: 6
                    }
                },
                colors: ['#351F14', '#E8BA82'],
                xaxis: {
                    categories: dashData.months,
                    labels: {
                        style: {
                            fontSize: '11px',
                            colors: '#7A695E'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: [{
                        title: {
                            text: 'Revenue (₹)',
                            style: {
                                fontSize: '11px',
                                color: '#7A695E'
                            }
                        },
                        labels: {
                            style: {
                                colors: '#7A695E',
                                fontSize: '11px'
                            },
                            formatter: v => '₹' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : (v || 0))
                        }
                    },
                    {
                        opposite: true,
                        title: {
                            text: 'Orders',
                            style: {
                                fontSize: '11px',
                                color: '#7A695E'
                            }
                        },
                        labels: {
                            style: {
                                colors: '#7A695E',
                                fontSize: '11px'
                            },
                            formatter: v => Math.round(v)
                        }
                    }
                ],
                legend: {
                    show: false
                },
                dataLabels: {
                    enabled: false
                },
                grid: {
                    borderColor: '#EFE8DE',
                    strokeDashArray: 0,
                    xaxis: {
                        lines: {
                            show: false
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                tooltip: {
                    shared: true,
                    y: [{
                            formatter: v => '₹' + Number(v).toLocaleString('en-IN')
                        },
                        {
                            formatter: v => v + ' orders'
                        }
                    ]
                }
            });
            revenueChart.render();

            // ── Overview filter toggle pills ───────────────────────────────────
            document.querySelectorAll('.overview-filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.overview-filter-btn').forEach(b => b.classList.remove(
                        'active'));
                    this.classList.add('active');
                    const mode = this.dataset.mode;
                    if (mode === 'orders') {
                        revenueChart.updateSeries([{
                                name: 'Revenue (₹)',
                                type: 'line',
                                data: []
                            },
                            {
                                name: 'Orders',
                                type: 'column',
                                data: dashData.orders
                            }
                        ]);
                    } else {
                        revenueChart.updateSeries([{
                                name: 'Revenue (₹)',
                                type: 'line',
                                data: dashData.revenue
                            },
                            {
                                name: 'Orders',
                                type: 'column',
                                data: dashData.orders
                            }
                        ]);
                    }
                });
            });
        }

        // ── Donut chart (Order status / payment status toggle) ─────────────────
        if (document.querySelector('#most-leads-chart')) {
            let donutChart = null;
            const luxuryOrderColors = {
                pending: '#5C3119',
                processing: '#D48D3B',
                shipped: '#E0B989',
                delivered: '#C4A381',
                cancelled: '#8E7E74'
            };
            const luxuryPaymentColors = {
                paid: '#2E7D32',
                pending: '#5C3119',
                failed: '#D32F2F',
                refunded: '#8E7E74'
            };

            function buildLegend(labels, series, colorMap) {
                const total = series.reduce((a, b) => a + b, 0);
                return '<div class="d-flex flex-wrap align-items-center justify-content-center gap-3 pt-2">' +
                    labels.map((lbl, i) => {
                        const color = colorMap[lbl.toLowerCase()] || '#8C5A32';
                        const pct = total > 0 ? ((series[i] / total) * 100).toFixed(1) : 0;
                        return `
                        <div class="d-flex align-items-center gap-1 fs-12">
                            <span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:${color};"></span>
                            <span class="text-capitalize fw-semibold" style="color: #20140D;">${lbl}</span>
                            <span class="text-muted">${series[i]} (${pct}%)</span>
                        </div>`;
                    }).join('') +
                    '</div>';
            }

            function renderDonut(labels, series, colorMap, centerLabel) {
                const colors = labels.map(l => colorMap[l.toLowerCase()] || '#8C5A32');
                const safeSeries = series.length ? series : [1];
                const safeLabels = labels.length ? labels : ['No Data'];

                const opts = {
                    series: safeSeries,
                    labels: safeLabels,
                    chart: {
                        type: 'donut',
                        height: 220,
                        fontFamily: 'inherit'
                    },
                    colors,
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ['#FFFFFF']
                    },
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '74%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true,
                                        fontSize: '12px',
                                        color: '#8E7E74'
                                    },
                                    value: {
                                        show: true,
                                        fontSize: '24px',
                                        fontWeight: 700,
                                        color: '#20140D'
                                    },
                                    total: {
                                        show: true,
                                        label: centerLabel,
                                        fontSize: '12px',
                                        color: '#8E7E74',
                                        formatter: () => safeSeries.reduce((a, b) => a + b, 0)
                                    }
                                }
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: v => v + ' orders'
                        }
                    }
                };

                if (donutChart) {
                    donutChart.updateOptions(opts, true, true);
                } else {
                    donutChart = new ApexCharts(document.querySelector('#most-leads-chart'), opts);
                    donutChart.render();
                }

                const legendEl = document.getElementById('donut-legend-row');
                if (legendEl) {
                    legendEl.innerHTML = buildLegend(labels, series, colorMap);
                }
            }

            // Initial render — order_status
            renderDonut(dashData.orderDonutLabels, dashData.orderDonutSeries, luxuryOrderColors, 'Total Orders');

            // Toggle buttons
            document.querySelectorAll('.donut-toggle').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.donut-toggle').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    if (this.dataset.type === 'payment') {
                        renderDonut(dashData.paymentDonutLabels, dashData.paymentDonutSeries,
                            luxuryPaymentColors, 'By Payment');
                    } else {
                        renderDonut(dashData.orderDonutLabels, dashData.orderDonutSeries, luxuryOrderColors,
                            'Total Orders');
                    }
                });
            });
        }

        // ── New users mini bar chart (if present in DOM) ──────────────────────
        if (document.querySelector('#new-users-chart')) {
            new ApexCharts(document.querySelector('#new-users-chart'), {
                series: [{
                    name: 'New Users',
                    data: dashData.newUsersData
                }],
                chart: {
                    type: 'bar',
                    height: 80,
                    sparkline: {
                        enabled: true
                    }
                },
                colors: ['#B3804A'],
                plotOptions: {
                    bar: {
                        columnWidth: '60%',
                        borderRadius: 3
                    }
                },
                tooltip: {
                    fixed: {
                        enabled: false
                    },
                    x: {
                        show: false
                    },
                    y: {
                        formatter: v => v + ' users'
                    }
                }
            }).render();
        }
    </script>
@endsection
