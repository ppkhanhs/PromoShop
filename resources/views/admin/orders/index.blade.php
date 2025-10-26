@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Đơn hàng')

@php
    use Carbon\Carbon;
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Đơn hàng</h1>
            <p class="text-muted mb-0">Theo dõi các đơn khách đã đặt và trạng thái xử lý.</p>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-2">Tổng đơn hàng</h2>
                    <p class="display-6 fw-semibold mb-0">{{ number_format($summary['total']) }}</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-2">Doanh thu sau ưu đãi</h2>
                    <p class="display-6 fw-semibold text-primary mb-0">
                        {{ number_format($summary['revenue'], 0, ',', '.') }} ₫
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-2">Tổng tiền giảm</h2>
                    <p class="display-6 fw-semibold text-success mb-0">
                        {{ number_format($summary['discount'], 0, ',', '.') }} ₫
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-2">Phí giao hàng thu</h2>
                    <p class="display-6 fw-semibold mb-0">
                        {{ number_format($summary['shipping'], 0, ',', '.') }} ₫
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Danh sách đơn hàng</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th class="text-end">Tổng</th>
                        <th class="text-end">Giảm</th>
                        <th class="text-center">Trạng thái</th>
                        <th>Mã khuyến mãi</th>
                        <th>Ngày tạo</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            /** @var \App\Models\Cassandra\Order $order */
                            $createdAt = $order->get('created_at');
                            try {
                                $createdAtFormatted = $createdAt ? Carbon::parse($createdAt)->format('d/m/Y H:i') : '---';
                            } catch (\Throwable $th) {
                                $createdAtFormatted = '---';
                            }
                            $status = $order->get('status', 'pending');
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $order->get('order_id') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $order->get('customer_name') ?? ($order->get('user_id') ?: 'Khách lẻ') }}</div>
                                <div class="text-muted small">{{ $order->get('customer_phone') ?? '---' }}</div>
                            </td>
                            <td class="text-end">{{ number_format($order->get('final_amount') ?? 0, 0, ',', '.') }} ₫</td>
                            <td class="text-end text-success">-{{ number_format($order->get('discount') ?? 0, 0, ',', '.') }} ₫</td>
                            <td class="text-center">
                                <span class="badge text-bg-{{ $status === 'completed' ? 'success' : ($status === 'pending' ? 'warning' : 'secondary') }}">
                                    {{ strtoupper($status) }}
                                </span>
                            </td>
                            <td>{{ $order->get('promo_id') ?? '---' }}</td>
                            <td>{{ $createdAtFormatted }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.orders.show', $order->get('order_id')) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-eye me-1"></i>Chi tiết
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Chưa có đơn hàng nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Khuyến mãi được áp dụng nhiều</h2>
        </div>
        <div class="card-body">
            @if ($promotionStats->isEmpty())
                <div class="text-center text-muted">Chưa ghi nhận khuyến mãi áp dụng.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Khuyến mãi</th>
                                <th class="text-end">Số đơn</th>
                                <th class="text-end">Tổng tiền giảm</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($promotionStats as $code => $stat)
                                <tr>
                                    <td class="fw-semibold">{{ $code }}</td>
                                    <td class="text-end">{{ number_format($stat['count'] ?? 0) }}</td>
                                    <td class="text-end text-success">
                                        {{ number_format($stat['discount'] ?? 0, 0, ',', '.') }} ₫
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
