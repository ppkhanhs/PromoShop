@extends('layouts.client.master')

@section('title', 'Đơn hàng của tôi - PromoShop')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Đơn hàng của tôi</h1>
        <a href="{{ route('client.home') }}" class="btn btn-outline-secondary">Tiếp tục mua sắm</a>
    </div>

    @forelse ($orders as $order)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h5 mb-1">Mã đơn: {{ $order->get('order_id') ?? $order->get('order_code') }}</h2>
                    <span class="text-muted small">Ngày tạo: {{ $order->get('created_at') ?? $order->get('order_date') }}</span>
                </div>
                <span class="badge text-bg-primary">{{ $order->get('status', 'pending') }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Người nhận:</strong> {{ $order->get('customer_name') }}</p>
                        <p class="mb-1"><strong>Điện thoại:</strong> {{ $order->get('customer_phone') }}</p>
                        <p class="mb-0"><strong>Địa chỉ:</strong> {{ $order->get('shipping_address') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Tạm tính:</strong> {{ number_format($order->get('total', 0), 0, ',', '.') }} đ</p>
                        <p class="mb-1 text-success"><strong>Giảm giá:</strong> -{{ number_format($order->get('discount', 0), 0, ',', '.') }} đ</p>
                        <p class="mb-0 fw-semibold text-primary"><strong>Thành tiền:</strong> {{ number_format($order->get('final_amount', $order->get('total', 0) - $order->get('discount', 0)), 0, ',', '.') }} đ</p>
                    </div>
                </div>
                @if ($items = $order->get('items'))
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Số lượng</th>
                                    <th class="text-end">Đơn giá</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>{{ $item['name'] ?? $item['product_id'] }}</td>
                                        <td class="text-end">{{ $item['quantity'] ?? $item['qty'] ?? 1 }}</td>
                                        <td class="text-end">{{ number_format($item['price'] ?? 0, 0, ',', '.') }} đ</td>
                                        <td class="text-end">{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? $item['qty'] ?? 1), 0, ',', '.') }} đ</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-secondary">
            Bạn chưa có đơn hàng nào. Hãy mua sắm và trải nghiệm khuyến mãi đa tầng nhé!
        </div>
    @endforelse
@endsection

