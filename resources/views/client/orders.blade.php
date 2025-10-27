@extends('layouts.client.master')

@section('title', 'Đơn hàng của tôi - PromoShop')

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Đơn hàng của tôi</h1>
            <p class="text-muted mb-0">Theo dõi lịch sử mua sắm và các khuyến mãi đã áp dụng.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('client.home') }}" class="btn btn-outline-secondary">
                Tiếp tục mua sắm
            </a>
            <a href="{{ route('client.cart') }}" class="btn btn-primary">
                Mở giỏ hàng
            </a>
        </div>
    </div>

    <form action="{{ route('client.orders') }}" method="GET" class="card shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-xl-4">
                <label for="keyword" class="form-label">Tìm kiếm đơn hàng</label>
                <input type="text" id="keyword" name="keyword" class="form-control"
                       placeholder="Nhập mã đơn hoặc sản phẩm"
                       value="{{ request('keyword') }}">
            </div>
            <div class="col-md-4 col-xl-3">
                <label for="status" class="form-label">Trạng thái</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="pending" @selected(request('status') === 'pending')>Chờ xử lý</option>
                    <option value="processing" @selected(request('status') === 'processing')>Đang xử lý</option>
                    <option value="shipped" @selected(request('status') === 'shipped')>Đã giao</option>
                    <option value="completed" @selected(request('status') === 'completed')>Hoàn thành</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-4 col-xl-3">
                <label for="date_from" class="form-label">Từ ngày</label>
                <input type="date" id="date_from" name="date_from" class="form-control"
                       value="{{ request('date_from') }}">
            </div>
            <div class="col-md-4 col-xl-2">
                <label for="date_to" class="form-label">Đến ngày</label>
                <input type="date" id="date_to" name="date_to" class="form-control"
                       value="{{ request('date_to') }}">
            </div>
            <div class="col-md-4 col-xl-12 d-flex justify-content-end gap-2">
                <a href="{{ route('client.orders') }}" class="btn btn-light">Xóa bộ lọc</a>
                <button type="submit" class="btn btn-primary">Lọc đơn hàng</button>
            </div>
        </div>
    </form>

    @forelse ($orders as $order)
        @php
            $orderId = $order->get('order_id') ?? $order->get('order_code');
            $promoSnapshot = collect($order->get('promotion_snapshot') ?? []);
            $giftSnapshot = collect($order->get('gifts') ?? []);
            $collapseId = 'order-detail-' . $orderId;
            $promoCollapseId = 'order-promo-' . $orderId;
        @endphp
        <div class="card shadow-sm mb-4 position-relative">
            <div class="card-header bg-white d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h5 mb-1">Mã đơn: {{ $orderId }}</h2>
                    <div class="text-muted small d-flex flex-column flex-md-row gap-2">
                        <span>Ngày tạo: {{ $order->get('created_at') ?? $order->get('order_date') }}</span>
                        <span>Phương thức: {{ $order->get('payment_method', 'Thanh toán khi nhận hàng') }}</span>
                    </div>
                </div>
                <span class="badge text-bg-primary">{{ $order->get('status', 'pending') }}</span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a class="btn btn-outline-primary btn-sm"
                       data-bs-toggle="collapse"
                       href="#{{ $collapseId }}"
                       role="button"
                       aria-expanded="false"
                       aria-controls="{{ $collapseId }}">
                        Chi tiết sản phẩm
                    </a>
                    <a class="btn btn-outline-secondary btn-sm"
                       data-bs-toggle="collapse"
                       href="#{{ $promoCollapseId }}"
                       role="button"
                       aria-expanded="false"
                       aria-controls="{{ $promoCollapseId }}">
                        Khuyến mãi áp dụng
                    </a>
                    <form action="{{ route('client.orders.reorder') }}" method="POST" class="ms-auto">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $orderId }}">
                        <button type="submit" class="btn btn-primary btn-sm">
                            Mua lại đơn này
                        </button>
                    </form>
                </div>

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
                <div class="collapse" id="{{ $collapseId }}">
                    @if ($items = $order->get('items'))
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th class="text-center">SKU</th>
                                        <th class="text-end">Số lượng</th>
                                        <th class="text-end">Đơn giá</th>
                                        <th class="text-end">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>{{ $item['name'] ?? $item['product_id'] }}</td>
                                            <td class="text-center">{{ $item['product_id'] ?? $item['sku'] ?? '-' }}</td>
                                            <td class="text-end">{{ $item['quantity'] ?? $item['qty'] ?? 1 }}</td>
                                            <td class="text-end">{{ number_format($item['price'] ?? 0, 0, ',', '.') }} đ</td>
                                            <td class="text-end">{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? $item['qty'] ?? 1), 0, ',', '.') }} đ</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted small mb-0">Chưa có dữ liệu sản phẩm trong đơn.</p>
                    @endif
                </div>

                <div class="collapse mt-3" id="{{ $promoCollapseId }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <strong class="d-block mb-2">Khuyến mãi</strong>
                                @if ($promoSnapshot->isNotEmpty())
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($promoSnapshot as $promo)
                                            <li class="mb-2">
                                                <div class="fw-semibold">{{ $promo['title'] ?? $promo['promo_id'] }}</div>
                                                <div class="text-muted small">
                                                    {{ $promo['tier_label'] ?? ('Bậc ' . ($promo['tier_level'] ?? '')) }}
                                                </div>
                                                <div class="small text-success">
                                                    Giảm {{ number_format($promo['discount_amount'] ?? 0, 0, ',', '.') }} đ
                                                    @if (!empty($promo['shipping_discount']))
                                                        · Freeship {{ number_format($promo['shipping_discount'], 0, ',', '.') }} đ
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small mb-0">Đơn hàng này chưa áp dụng khuyến mãi.</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <strong class="d-block mb-2">Quà tặng / Combo</strong>
                                @if ($giftSnapshot->isNotEmpty())
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($giftSnapshot as $gift)
                                            <li>
                                                {{ $gift['description'] ?? 'Ưu đãi tặng kèm' }}
                                                <span class="text-muted small">(x{{ $gift['quantity'] ?? 1 }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small mb-0">Không có quà tặng kèm theo đơn này.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if ($order->get('notes'))
                    <div class="mt-3">
                        <strong>Ghi chú của shop:</strong>
                        <p class="text-muted mb-0">{{ $order->get('notes') }}</p>
                    </div>
                @endif
            </div>
            <div class="card-footer bg-white d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
                <div class="text-muted small">
                    Cập nhật lần cuối: {{ $order->get('updated_at') ?? $order->get('created_at') }}
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('client.orders.invoice', ['order' => $orderId]) }}" class="btn btn-light btn-sm">
                        Tải hóa đơn
                    </a>
                    <a href="{{ route('client.support') }}" class="btn btn-outline-secondary btn-sm">
                        Cần hỗ trợ?
                    </a>
                    <a href="{{ route('client.orders.track', ['order' => $orderId]) }}" class="btn btn-outline-primary btn-sm">
                        Theo dõi vận chuyển
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-secondary">
            Bạn chưa có đơn hàng nào. Hãy mua sắm và trải nghiệm khuyến mãi đa tầng nhé!
        </div>
    @endforelse
@endsection
