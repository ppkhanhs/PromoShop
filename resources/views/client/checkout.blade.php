@extends('layouts.client.master')

@section('title', 'Thanh toán đơn hàng - PromoShop')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Thanh toán</h1>
        <a href="{{ route('client.cart') }}" class="btn btn-outline-secondary">Quay về giỏ hàng</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Thông tin giao hàng</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('client.checkout.submit') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label for="customer_name" class="form-label">Họ và tên</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control"
                                   value="{{ old('customer_name', $user?->getAttribute('name')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="customer_phone" class="form-label">Số điện thoại</label>
                            <input type="tel" id="customer_phone" name="customer_phone" class="form-control"
                                   value="{{ old('customer_phone') }}" required>
                        </div>
                        <div class="col-12">
                            <label for="shipping_address" class="form-label">Địa chỉ giao hàng</label>
                            <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" required>{{ old('shipping_address') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label for="note" class="form-label">Ghi chú (tuỳ chọn)</label>
                            <textarea id="note" name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">Xác nhận đặt hàng</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Tóm tắt đơn hàng</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush mb-3">
                        @foreach ($cartItems as $item)
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <h3 class="h6 mb-1">{{ $item['name'] }}</h3>
                                    <span class="text-muted small">x{{ $item['quantity'] }}</span>
                                </div>
                                <span class="fw-semibold">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }} đ</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tạm tính</span>
                        <strong>{{ number_format($summary['subtotal'], 0, ',', '.') }} đ</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Giảm giá</span>
                        <strong class="text-success">-{{ number_format($summary['discount'], 0, ',', '.') }} đ</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Phí giao hàng</span>
                        <strong>{{ number_format($summary['final_shipping_fee'], 0, ',', '.') }} đ</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5">
                        <span>Thành tiền</span>
                        <strong class="text-primary">{{ number_format($summary['final_total'], 0, ',', '.') }} đ</strong>
                    </div>
                    @if (!empty($summary['applied_promotions']))
                        <div class="mt-3">
                            <span class="fw-semibold">Khuyến mãi áp dụng:</span>
                            <ul class="mb-0 text-muted small">
                                @foreach ($summary['applied_promotions'] as $applied)
                                    <li>
                                        {{ $applied['promotion']['title'] ?? $applied['promotion']['promo_id'] }} –
                                        {{ $applied['tier']['label'] ?? ('Tầng ' . $applied['tier']['tier_level']) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if (!empty($summary['gifts']))
                        <div class="mt-2 text-success small">
                            <span class="fw-semibold">Quà tặng:</span>
                            <ul class="mb-0">
                                @foreach ($summary['gifts'] as $gift)
                                    <li>{{ $gift['description'] ?? 'Ưu đãi tặng kèm' }} (x{{ $gift['quantity'] ?? 1 }})</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

