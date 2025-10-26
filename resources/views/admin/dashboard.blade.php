@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Tổng quan')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Tổng quan hệ thống</h1>
            <p class="text-muted mb-0">Theo dõi tình hình đơn hàng, doanh thu và hiệu quả khuyến mãi.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-2">Khuyến mãi đang chạy</h2>
                    <p class="display-6 fw-semibold mb-0">
                        {{ number_format($stats['promotions_active'] ?? $stats['promos'] ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-2">Tổng sản phẩm</h2>
                    <p class="display-6 fw-semibold mb-0">
                        {{ number_format($stats['products'] ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-2">Đơn hàng đã tạo</h2>
                    <p class="display-6 fw-semibold mb-0">
                        {{ number_format($stats['orders'] ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-2">Tổng tiền giảm</h2>
                    <p class="display-6 fw-semibold text-success mb-0">
                        {{ number_format($stats['discount_amount'] ?? 0, 0, ',', '.') }} ₫
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Doanh thu &amp; số đơn theo tháng</h2>
                </div>
                <div class="card-body">
                    @if (empty($chartData['revenue']['categories']))
                        <div class="text-center text-muted py-5">
                            Chưa có dữ liệu đơn hàng để hiển thị biểu đồ.
                        </div>
                    @else
                        <div id="chart-revenue" style="min-height: 320px;"></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Tỉ lệ trạng thái đơn hàng</h2>
                </div>
                <div class="card-body">
                    @if (empty($chartData['status']['labels']))
                        <div class="text-center text-muted py-5">
                            Chưa có đơn hàng nào.
                        </div>
                    @else
                        <div id="chart-status" style="min-height: 320px;"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h2 class="h5 mb-0">Top chương trình khuyến mãi</h2>
            <span class="text-muted small">Dựa trên số lần áp dụng và tổng tiền giảm.</span>
        </div>
        <div class="card-body">
            @php
                $topPromotions = $stats['top_promotions'] ?? [];
            @endphp
            @if (empty($topPromotions))
                <div class="text-center text-muted py-4">
                    Chưa có lượt áp dụng khuyến mãi nào.
                </div>
            @else
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div id="chart-promotions" style="min-height: 280px;"></div>
                    </div>
                    <div class="col-lg-6">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã khuyến mãi</th>
                                        <th class="text-end">Số lượt áp dụng</th>
                                        <th class="text-end">Tổng tiền giảm</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topPromotions as $promo)
                                        <tr>
                                            <td class="fw-semibold">{{ $promo['promo_id'] ?? $promo['title'] }}</td>
                                            <td class="text-end">{{ number_format($promo['usage'] ?? 0) }}</td>
                                            <td class="text-end text-success">
                                                {{ number_format($promo['discount_amount'] ?? 0, 0, ',', '.') }} ₫
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const currencyFormatter = new Intl.NumberFormat('vi-VN');

            const revenueConfig = @json($chartData['revenue']);
            if (revenueConfig.categories.length) {
                const revenueChart = new ApexCharts(document.querySelector('#chart-revenue'), {
                    chart: {
                        type: 'line',
                        height: 330,
                        toolbar: { show: false },
                    },
                    stroke: {
                        curve: 'smooth',
                        width: [0, 0, 3],
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '45%',
                            borderRadius: 6,
                        },
                    },
                    fill: {
                        opacity: [0.9, 0.9, 1],
                    },
                    colors: ['#2563eb', '#f97316', '#16a34a'],
                    series: [
                        { name: 'Doanh thu', type: 'column', data: revenueConfig.series.revenue },
                        { name: 'Tiền giảm', type: 'column', data: revenueConfig.series.discount },
                        { name: 'Số đơn', type: 'line', data: revenueConfig.series.orders },
                    ],
                    xaxis: {
                        categories: revenueConfig.categories,
                        labels: { rotate: -45 },
                    },
                    yaxis: [
                        {
                            labels: {
                                formatter: (val) => `${currencyFormatter.format(Math.round(val))} ₫`,
                            },
                            title: { text: 'Giá trị (₫)' },
                        },
                        {
                            opposite: true,
                            labels: { formatter: (val) => currencyFormatter.format(Math.round(val)) },
                            title: { text: 'Số đơn' },
                        },
                    ],
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: [
                            {
                                formatter: (val) => `${currencyFormatter.format(Math.round(val))} ₫`,
                            },
                            {
                                formatter: (val) => `${currencyFormatter.format(Math.round(val))} ₫`,
                            },
                            {
                                formatter: (val) => `${currencyFormatter.format(Math.round(val))} đơn`,
                            },
                        ],
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                    },
                });
                revenueChart.render();
            }

            const statusConfig = @json($chartData['status']);
            if (statusConfig.labels.length) {
                const statusChart = new ApexCharts(document.querySelector('#chart-status'), {
                    chart: {
                        type: 'donut',
                        height: 320,
                    },
                    labels: statusConfig.labels,
                    series: statusConfig.series,
                    colors: ['#2563eb', '#f97316', '#16a34a', '#9333ea', '#f43f5e', '#0ea5e9'],
                    dataLabels: {
                        formatter: (val) => `${val.toFixed(1)}%`,
                    },
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        y: {
                            formatter: (val) => `${currencyFormatter.format(Math.round(val))} đơn`,
                        },
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Tổng đơn',
                                        formatter: () => currencyFormatter.format(statusConfig.series.reduce((a, b) => a + b, 0)),
                                    },
                                },
                            },
                        },
                    },
                });
                statusChart.render();
            }

            const promotionConfig = @json($chartData['promotions']);
            if (promotionConfig.labels.length) {
                const promotionChart = new ApexCharts(document.querySelector('#chart-promotions'), {
                    chart: {
                        type: 'bar',
                        height: 280,
                    },
                    series: [
                        {
                            name: 'Số lượt áp dụng',
                            data: promotionConfig.usage,
                        },
                        {
                            name: 'Tổng tiền giảm (₫)',
                            data: promotionConfig.discount,
                        },
                    ],
                    colors: ['#2563eb', '#f97316'],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 6,
                            barHeight: '60%',
                        },
                    },
                    xaxis: {
                        categories: promotionConfig.labels,
                        labels: {
                            style: { fontSize: '13px' },
                        },
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: [
                            {
                                formatter: (val) => `${currencyFormatter.format(Math.round(val))} lượt`,
                            },
                            {
                                formatter: (val) => `${currencyFormatter.format(Math.round(val))} ₫`,
                            },
                        ],
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                    },
                });
                promotionChart.render();
            }
        });
    </script>
@endpush
