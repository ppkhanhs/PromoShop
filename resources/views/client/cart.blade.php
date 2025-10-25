@extends('layouts.client.master')

@section('title', 'Giỏ hàng của bạn - PromoShop')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Giỏ hàng của bạn</h1>
        <a href="{{ route('client.home') }}" class="btn btn-outline-secondary">Tiếp tục mua sắm</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Sản phẩm đã chọn</h2>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($cartItems as $item)
                        @php
                            $lineTotal = $item['price'] * $item['quantity'];
                        @endphp
                        <div class="list-group-item">
                            <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                                <img src="{{ $item['image_url'] ?? 'https://images.promoshop.vn/placeholder/product.png' }}"
                                     alt="{{ $item['name'] }}"
                                     class="rounded"
                                     style="width: 120px; height: 120px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h3 class="h5">{{ $item['name'] }}</h3>
                                            <p class="text-muted mb-2">Mã sản phẩm: {{ $item['product_id'] }}</p>
                                            <span class="badge text-bg-light">Đơn giá: {{ number_format($item['price'], 0, ',', '.') }} đ</span>
                                        </div>
                                        <form action="{{ route('client.cart.remove') }}" method="POST" class="ms-auto">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                            <button type="submit" class="btn btn-link text-danger">Xóa</button>
                                        </form>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                                        <form action="{{ route('client.cart.update') }}" method="POST" class="d-flex align-items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                            <label class="form-label mb-0 me-2">Số lượng</label>
                                            <input type="number" name="quantity" class="form-control" value="{{ $item['quantity'] }}" min="1" style="width: 90px;">
                                            <button type="submit" class="btn btn-outline-primary">Cập nhật</button>
                                        </form>
                                        <div class="ms-auto fw-semibold fs-5 text-primary">
                                            {{ number_format($lineTotal, 0, ',', '.') }} đ
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted text-center py-5">
                            Giỏ hàng đang trống.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Tổng kết đơn hàng</h2>
                </div>
                <div class="card-body">
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
                        <strong>{{ number_format($summary['shipping_fee'], 0, ',', '.') }} đ</strong>
                    </div>
                    @if ($summary['shipping_discount'] > 0)
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Giảm phí giao hàng</span>
                            <strong>-{{ number_format($summary['shipping_discount'], 0, ',', '.') }} đ</strong>
                        </div>
                    @endif
                    <hr>
                    <div class="d-flex justify-content-between mb-3 fs-5">
                        <span>Thành tiền</span>
                        <strong class="text-primary">{{ number_format($summary['final_total'], 0, ',', '.') }} đ</strong>
                    </div>
                    @if (!empty($summary['applied_promotions']))
                        <div class="mb-3">
                            <span class="fw-semibold">Khuyến mãi áp dụng:</span>
                            <ul class="mb-0 text-muted">
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
                        <div class="mb-3">
                            <span class="fw-semibold text-success">Quà tặng:</span>
                            <ul class="mb-0 text-muted">
                                @foreach ($summary['gifts'] as $gift)
                                    <li>{{ $gift['description'] ?? 'Quà tặng đặc biệt' }} (x{{ $gift['quantity'] ?? 1 }})</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <a href="{{ route('client.checkout') }}"
                       class="btn btn-primary w-100"
                       @if (empty($cartItems)) disabled @endif>
                        Tiến hành thanh toán
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
