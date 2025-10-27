@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Chi tiết đơn hàng')

@php
    use Carbon\Carbon;
    $items = $order->get('items', []);
    $createdAt = $order->get('created_at');
    try {
        $createdAtFormatted = $createdAt ? Carbon::parse($createdAt)->format('d/m/Y H:i') : '---';
    } catch (\Throwable $th) {
        $createdAtFormatted = '---';
    }
    $subtotal = collect($items)->sum(fn ($item) => (int) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? $item['qty'] ?? 1));
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Đơn hàng {{ $order->get('order_id') }}</h1>
            <p class="text-muted mb-0">Thông tin chi tiết và lịch sử ưu đãi của đơn.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Quay lại danh sách
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Thông tin khách hàng</h2>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Khách hàng</dt>
                        <dd class="col-7">{{ $order->get('customer_name') ?? 'Không cung cấp' }}</dd>

                        <dt class="col-5">Số điện thoại</dt>
                        <dd class="col-7">{{ $order->get('customer_phone') ?? '---' }}</dd>

                        <dt class="col-5">Tài khoản</dt>
                        <dd class="col-7">{{ $order->get('user_id') ?? 'Khách lẻ' }}</dd>

                        <dt class="col-5">Ngày tạo</dt>
                        <dd class="col-7">{{ $createdAtFormatted }}</dd>

                        <dt class="col-5">Trạng thái</dt>
                        <dd class="col-7">
                            <span class="badge text-bg-{{ $order->get('status') === 'completed' ? 'success' : 'warning' }}">
                                {{ strtoupper($order->get('status', 'pending')) }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Tổng quan thanh toán</h2>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between mb-2">
                            <span>Tạm tính</span>
                            <strong>{{ number_format($subtotal, 0, ',', '.') }} ₫</strong>
                        </li>
                        <li class="d-flex justify-content-between mb-2 text-success">
                            <span>Giảm giá</span>
                            <strong>-{{ number_format($order->get('discount') ?? 0, 0, ',', '.') }} ₫</strong>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span>Phí giao hàng</span>
                            <strong>{{ number_format($order->get('shipping_fee') ?? 0, 0, ',', '.') }} ₫</strong>
                        </li>
                        <li class="d-flex justify-content-between border-top pt-2">
                            <span>Thành tiền</span>
                            <strong class="text-primary">{{ number_format($order->get('final_amount') ?? 0, 0, ',', '.') }} ₫</strong>
                        </li>
                    </ul>
                    <hr>
                    <dl class="row mb-0">
                        <dt class="col-5">Mã khuyến mãi</dt>
                        <dd class="col-7">{{ $order->get('promo_id') ?? 'Không áp dụng' }}</dd>

                        <dt class="col-5">Tầng áp dụng</dt>
                        <dd class="col-7">{{ $order->get('applied_tier') ? 'Tầng ' . $order->get('applied_tier') : '---' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Danh sách sản phẩm</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php
                                    $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                                    $price = (int) ($item['price'] ?? $item['unit_price'] ?? 0);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item['name'] ?? $item['product_id'] }}</div>
                                        <div class="text-muted small">Mã: {{ $item['product_id'] ?? '---' }}</div>
                                    </td>
                                    <td class="text-end">{{ number_format($price, 0, ',', '.') }} ₫</td>
                                    <td class="text-center">{{ $qty }}</td>
                                    <td class="text-end">{{ number_format($qty * $price, 0, ',', '.') }} ₫</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Đơn hàng không có sản phẩm.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Ghi chú &amp; giao hàng</h2>
                </div>
                <div class="card-body">
                    <dl class="row mb-3">
                        <dt class="col-3">Địa chỉ giao</dt>
                        <dd class="col-9">{{ $order->get('shipping_address') ?? '---' }}</dd>

                        <dt class="col-3">Ghi chú</dt>
                        <dd class="col-9">{{ $order->get('note') ?? 'Không có ghi chú.' }}</dd>
                    </dl>
                    <div class="text-muted small">
                        Nếu cần cập nhật trạng thái hoặc thông tin vận chuyển, vui lòng thao tác trực tiếp trên hệ thống xử lý đơn hàng.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
