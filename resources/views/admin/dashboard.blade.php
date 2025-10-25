@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Tổng quan')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Tổng quan hệ thống</h1>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted">Khuyến mãi đang chạy</h2>
                    <p class="display-6 fw-semibold mb-0">{{ number_format($stats['promotions_active'] ?? $stats['promos'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted">Tổng sản phẩm</h2>
                    <p class="display-6 fw-semibold mb-0">{{ number_format($stats['products'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted">Đơn hàng đã tạo</h2>
                    <p class="display-6 fw-semibold mb-0">{{ number_format($stats['orders'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted">Doanh thu giảm</h2>
                    <p class="display-6 fw-semibold mb-0 text-success">
                        {{ number_format($stats['discount_amount'] ?? 0, 0, ',', '.') }} đ
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if (!empty($stats['top_promotions']))
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Top khuyến mãi được áp dụng</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Mã khuyến mãi</th>
                            <th class="text-end">Số lượt áp dụng</th>
                            <th class="text-end">Giảm giá</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['top_promotions'] as $promo)
                            <tr>
                                <td>{{ $promo['promo_id'] ?? $promo['title'] }}</td>
                                <td class="text-end">{{ number_format($promo['usage'] ?? 0) }}</td>
                                <td class="text-end text-success">{{ number_format($promo['discount_amount'] ?? 0, 0, ',', '.') }} đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

