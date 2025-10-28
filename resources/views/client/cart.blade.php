@extends('layouts.client.master')

@section('title', 'Giỏ hàng của bạn - PromoShop')

@section('content')
    @php
        $cartCollection = collect($cartItems ?? []);
        $totalQuantity = $cartCollection->sum('quantity');
        $promotionList = collect($promotions ?? [])->take(3);
        $appliedPromotions = collect($summary['applied_promotions'] ?? []);
        $manualPendingPromotions = collect($pendingPromotions ?? []);
        $disabledPromotions = collect($disabledPromotions ?? []);
        $gifts = collect($summary['gifts'] ?? []);
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2">Smart checkout</span>
            <h1 class="h3 mt-2 mb-1">Giỏ hàng thông minh</h1>
            <p class="text-muted mb-0">Tối ưu khuyến mãi theo thời gian thực từ Cassandra</p>
        </div>
        <div class="text-md-end">
            <a href="{{ route('client.home') }}" class="btn btn-outline-secondary">
                ← Tiếp tục mua sắm
            </a>
        </div>
    </div>

    <div class="row g-4 cart-wrapper">
        <div class="col-lg-8">
            <div class="card cart-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div>
                            <h2 class="h5 mb-1">Danh sách sản phẩm</h2>
                            <span class="text-muted small">Theo dõi các sản phẩm bạn đã chọn</span>
                        </div>
                        <span class="badge bg-light text-dark fw-semibold px-3 py-2">{{ $totalQuantity }} món</span>
                    </div>

                    @if ($cartCollection->isEmpty())
                        <div class="cart-empty text-center">
                            <div class="mb-3">
                                <span class="cart-empty__icon">🛒</span>
                            </div>
                            <h3 class="cart-empty__title mb-2">Giỏ hàng đang trống</h3>
                            <p class="text-muted mb-0">
                                Thêm sản phẩm từ trang chủ để hệ thống gợi ý ưu đãi và quà tặng hấp dẫn nhất.
                            </p>
                            <a href="{{ route('client.home') }}" class="btn btn-primary cart-empty__cta">
                                Bắt đầu khám phá
                            </a>
                        </div>
                    @else
                        <div class="cart-item-list d-flex flex-column gap-3">
                            @foreach ($cartCollection as $index => $item)
                                @php
                                    $lineTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
                                @endphp
                                <article class="card cart-item-card border-0">
                                    <div class="card-body">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-sm-auto">
                                                <div class="cart-item-thumb">
                                                    <img src="{{ $item['image_url'] ?? 'https://placehold.co/160x160?text=PromoShop' }}"
                                                        alt="{{ $item['name'] ?? $item['product_id'] }}">
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="d-flex flex-wrap justify-content-between gap-3">
                                                    <div>
                                                        <h3 class="h6 mb-1 cart-item-title">{{ $item['name'] ?? $item['product_id'] }}</h3>
                                                        <p class="text-muted small mb-0">Mã hàng: {{ $item['product_id'] }}</p>
                                                        <p class="text-muted small mb-0">Đơn giá: {{ number_format($item['price'] ?? 0, 0, ',', '.') }} VND</p>
                                                    </div>
                                                    <div class="cart-item-total text-end">
                                                        <span class="text-muted small d-block">Thành tiền</span>
                                                        <strong>{{ number_format($lineTotal, 0, ',', '.') }} VND</strong>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                                                    <form action="{{ route('client.cart.update') }}" method="POST" data-auto-submit="quantity" class="cart-item-quantity d-flex align-items-center gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                                        <label class="text-muted small mb-0" for="qty-{{ $index }}">Số lượng</label>
                                                        <input type="number" id="qty-{{ $index }}" name="quantity" class="form-control form-control-sm js-cart-qty"
                                                            value="{{ $item['quantity'] }}" min="1">
                                                    </form>
                                                    <form action="{{ route('client.cart.remove') }}" method="POST" class="ms-auto">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                                        <button type="submit" class="btn btn-link text-danger p-0">Xóa</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="cart-sidebar-stack">

                <div class="card cart-card cart-summary-card-secondary">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Áp dụng khuyến mãi</h2>
                        <form action="{{ route('client.cart.promo.apply') }}" method="POST" class="promo-code-form">
                            @csrf
                            <label for="promotion_code" class="visually-hidden">Mã khuyến mãi</label>
                            <div class="input-group promo-code-group">
                                <input type="text" id="promotion_code" name="promotion_code" class="form-control"
                                    placeholder="Nhập mã khuyến mãi" value="{{ old('promotion_code') }}" autocomplete="off">
                                <button type="submit" class="btn btn-secondary">
                                    Áp dụng
                                </button>
                            </div>
                        </form>

                        @if ($errors->has('promotion_code'))
                            <p class="text-danger small mt-2 mb-0">
                                {{ $errors->first('promotion_code') }}
                            </p>
                        @endif

                        <div class="mt-3">
                            <strong class="small text-uppercase text-muted">Gợi ý mã nhanh</strong>
                            <div class="promo-chip-group mt-2">
                                @forelse ($promotionList as $promotion)
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick="document.getElementById('promotion_code').value='{{ $promotion->promo_code ?? $promotion->promo_id }}'">
                                        {{ $promotion->title ?? $promotion->get('title') ?? $promotion->promo_id }}
                                    </button>
                                @empty
                                    <span class="text-muted small">Chưa có chương trình nổi bật.</span>
                                @endforelse
                            </div>
                        </div>

                        @if ($appliedPromotions->isNotEmpty())
                            <div class="mt-4">
                                <strong class="small text-uppercase text-muted">Đang áp dụng</strong>
                                <ul class="list-unstyled mb-0 mt-2 space-y-3">
                                    @foreach ($appliedPromotions as $applied)
                                        <li class="text-center border rounded-3 py-3 px-3 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-success mx-auto mb-2"><path d="M20 6 9 17l-5-5"/></svg>
                                            <div class="fw-semibold text-success mb-2">Đã áp dụng</div>
                                            <div class="my-2">
                                                <div class="fw-semibold">{{ $applied['promotion']['title'] ?? $applied['promotion']['promo_id'] }}</div>
                                                <div class="text-muted small">{{ $applied['tier']['label'] ?? ('Bậc ' . ($applied['tier']['tier_level'] ?? '')) }}</div>
                                            </div>
                                            <div class="mt-3">
                                                <span class="promo-saving d-block text-success fw-medium mb-2">Giảm {{ number_format($applied['discount'] ?? 0, 0, ',', '.') }} VND</span>
                                                <form action="{{ route('client.cart.promo.remove') }}" method="POST" class="d-inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="promotion_id"
                                                        value="{{ $applied['promotion']['promo_id'] ?? $applied['promotion']['promo_code'] ?? $applied['promotion']['id'] ?? ($applied['promotion']['code'] ?? '') }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hủy</button>
                                                </form>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($manualPendingPromotions->isNotEmpty())
                            <div class="mt-4">
                                <strong class="small text-uppercase text-muted">Đang chờ điều kiện</strong>
                                <ul class="list-unstyled mb-0 mt-2">
                                    @foreach ($manualPendingPromotions as $pending)
                                        <li class="promo-line-item">
                                            <div class="promo-line-item__content">
                                                <div class="promo-line-item__title">{{ $pending['title'] ?? $pending['promo_id'] ?? ($pending['promo_code'] ?? 'Khuyến mãi') }}</div>
                                                <div class="promo-line-item__subtitle">Cần thêm giá trị đơn hàng hoặc số lượng để kích hoạt ưu đãi này.</div>
                                            </div>
                                            <div class="promo-line-item__actions">
                                                <form action="{{ route('client.cart.promo.remove') }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="promotion_id"
                                                        value="{{ $pending['promo_id'] ?? $pending['promo_code'] ?? ($pending['id'] ?? ($pending['code'] ?? '')) }}">
                                                    <button type="submit" class="link-danger small">Hủy</button>
                                                </form>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($disabledPromotions->isNotEmpty())
                            <div class="mt-4">
                                <strong class="small text-uppercase text-muted">Đã tắt tự động</strong>
                                <ul class="list-unstyled mb-0 mt-2">
                                    @foreach ($disabledPromotions as $disabled)
                                        <li class="promo-line-item">
                                            <div class="promo-line-item__content">
                                                <div class="promo-line-item__title">{{ $disabled['title'] ?? $disabled['promo_id'] ?? ($disabled['promo_code'] ?? 'Khuyến mãi') }}</div>
                                                <div class="promo-line-item__subtitle">Khuyến mãi này sẽ không tự áp dụng cho tới khi bạn bật lại.</div>
                                            </div>
                                            <div class="promo-line-item__actions">
                                                <form action="{{ route('client.cart.promo.enable') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="promotion_id"
                                                        value="{{ $disabled['promo_id'] ?? $disabled['promo_code'] ?? ($disabled['id'] ?? ($disabled['code'] ?? '')) }}">
                                                    <button type="submit" class="link-primary small">Bật lại</button>
                                                </form>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($appliedPromotions->isEmpty() && $manualPendingPromotions->isEmpty() && $disabledPromotions->isEmpty())
                            <p class="text-muted small mb-0 mt-3">
                                Áp dụng khuyến mãi để tiết kiệm thêm và mở khóa quà tặng hấp dẫn.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="card cart-card">
                    <div class="card-body">
                        <div class="cart-summary__header">
                            <span class="cart-summary__icon">💳</span>
                            <div>
                                <h3 class="h6 mb-1">Tổng quan thanh toán</h3>
                                <span class="text-muted small">Theo dõi chi tiết chi phí đơn hàng tại đây</span>
                            </div>
                        </div>

                        <div class="cart-summary__total">
                            <span>Tổng thanh toán</span>
                            <strong>{{ number_format($summary['final_total'] ?? 0, 0, ',', '.') }} VND</strong>
                        </div>

                        @if (($summary['discount'] ?? 0) > 0 || ($summary['shipping_discount'] ?? 0) > 0)
                            <div class="mb-3">
                                <span class="cart-summary__badge">
                                    Tiết kiệm {{ number_format(($summary['discount'] ?? 0) + ($summary['shipping_discount'] ?? 0), 0, ',', '.') }} VND
                                </span>
                            </div>
                        @endif

                        <ul class="list-unstyled cart-summary-list mb-3">
                            <li class="cart-summary-row">
                                <span>Tạm tính</span>
                                <strong>{{ number_format($summary['subtotal'] ?? 0, 0, ',', '.') }} VND</strong>
                            </li>
                            <li class="cart-summary-row text-success">
                                <span>Giảm giá</span>
                                <strong>-{{ number_format($summary['discount'] ?? 0, 0, ',', '.') }} VND</strong>
                            </li>
                            <li class="cart-summary-row">
                                <span>Phí giao hàng</span>
                                <strong>{{ number_format($summary['shipping_fee'] ?? 0, 0, ',', '.') }} VND</strong>
                            </li>
                            @if (($summary['shipping_discount'] ?? 0) > 0)
                                <li class="cart-summary-row text-success">
                                    <span>Giảm phí giao hàng</span>
                                    <strong>-{{ number_format($summary['shipping_discount'], 0, ',', '.') }} VND</strong>
                                </li>
                            @endif
                            <li class="cart-summary-divider"></li>
                            <li class="cart-summary-row text-muted">
                                <span>Phương thức mặc định</span>
                                <strong>Thanh toán khi nhận</strong>
                            </li>
                        </ul>
                        <form action="{{ route('client.checkout') }}" method="GET">
                            <button type="submit" class="btn btn-primary w-100 fw-semibold" @if ($cartCollection->isEmpty()) disabled @endif>
                                Tiến hành thanh toán
                            </button>
                        </form>
                    </div>
                </div>

                @if ($gifts->isNotEmpty() || $promotionList->isNotEmpty())
                    <div class="card cart-card">
                        <div class="card-body">
                            @if ($gifts->isNotEmpty())
                                <strong>Quà tặng đi kèm</strong>
                                <ul class="gift-list mb-0">
                                    @foreach ($gifts as $gift)
                                        <li>{{ $gift['description'] ?? 'Quà tặng' }} (x{{ $gift['quantity'] ?? 1 }})</li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($gifts->isNotEmpty() && $promotionList->isNotEmpty())
                                <hr class="my-3">
                            @endif

                            @if ($promotionList->isNotEmpty())
                                <strong>Gợi ý thêm ưu đãi</strong>
                                @foreach ($promotionList as $promotion)
                                    <div class="promo-recommend__item">
                                        <span>{{ $promotion->title ?? $promotion->get('title') ?? $promotion->promo_id }}</span>
                                        <span class="text-muted">{{ $promotion->statusLabel() }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const forms = document.querySelectorAll('form[data-auto-submit="quantity"]');
            const DEBOUNCE_DELAY = 500; 

            forms.forEach(function (form) {
                const input = form.querySelector('.js-cart-qty');
                if (!input) {
                    console.warn('Auto-submit form missing .js-cart-qty input:', form);
                    return; 
                }

                let timeoutId = null; 

                const submitForm = function () {
                    if (form.dataset.submitting === 'true') {
                        return;
                    }
                    form.dataset.submitting = 'true'; 
                    console.log('Auto-submitting quantity update for:', input.id);
                    form.requestSubmit(); 

                };

                input.addEventListener('input', function () {
                    clearTimeout(timeoutId); 
                    timeoutId = setTimeout(submitForm, DEBOUNCE_DELAY);
                });

                input.addEventListener('change', function () {
                    clearTimeout(timeoutId); 
                    submitForm(); 
                });

                form.addEventListener('submit', function() {
                     clearTimeout(timeoutId);
                     form.dataset.submitting = 'true'; 
                });
            });
        });
    </script>

@endsection
