@extends('layouts.client.master')

@section('title', 'Gio hang cua ban - PromoShop')

@section('content')
    @php
        $cartCollection = collect($cartItems ?? []);
        $hasItems = $cartCollection->isNotEmpty();
        $totalQuantity = $cartCollection->sum('quantity');
        $promotionList = collect($promotions ?? [])->take(3);
        $appliedPromotions = collect($summary['applied_promotions'] ?? []);
        $gifts = collect($summary['gifts'] ?? []);
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2">Checkout squad</span>
            <h1 class="h3 mt-2 mb-1">Gio hang thong minh</h1>
            <p class="text-muted mb-0">Toi uu khuyen mai theo thoi gian thuc tu Cassandra</p>
        </div>
        <div class="text-md-end">
            <a href="{{ route('client.home') }}" class="btn btn-outline-secondary">Tiep tuc mua sam</a>
        </div>
    </div>

    <div class="cart-shell">
        <section class="cart-panel">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Danh sach san pham</h2>
                <span class="text-muted small">{{ $totalQuantity }} mon</span>
            </div>

            @if (!$hasItems)
                <div class="promo-recommend text-center">
                    <h3 class="h5">Gio hang dang trong</h3>
                    <p class="text-muted mb-0">Them san pham tu trang chu de he thong goi y uu dai tot nhat.</p>
                    <a href="{{ route('client.home') }}" class="btn btn-primary mt-2">Bat dau kham pha</a>
                </div>
            @else
                <div class="d-grid gap-3">
                    @foreach ($cartCollection as $item)
                        @php
                            $lineTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
                        @endphp
                        <article class="cart-item-modern">
                            <img src="{{ $item['image_url'] ?? 'https://images.promoshop.vn/placeholder/product.png' }}" alt="{{ $item['name'] ?? $item['product_id'] }}">
                            <div class="cart-item-meta">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <h3 class="h6 mb-1">{{ $item['name'] ?? $item['product_id'] }}</h3>
                                        <p class="text-muted small mb-0">Ma hang: {{ $item['product_id'] }}</p>
                                        <p class="text-muted small mb-0">Don gia: {{ number_format($item['price'] ?? 0, 0, ',', '.') }} VND</p>
                                    </div>
                                    <form action="{{ route('client.cart.remove') }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                        <button type="submit" class="btn btn-link text-danger p-0">Xoa</button>
                                    </form>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <form action="{{ route('client.cart.update') }}" method="POST" class="d-flex align-items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                        <label class="text-muted small mb-0">So luong</label>
                                        <input type="number" name="quantity" class="form-control" value="{{ $item['quantity'] }}" min="1" style="width: 90px;">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Cap nhat</button>
                                    </form>
                                    <div class="ms-md-auto">
                                        <span class="fw-semibold">{{ number_format($lineTotal, 0, ',', '.') }} VND</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="d-grid gap-3">
            <div class="cart-summary">
                <div class="summary-rows">
                    <div class="summary-row">
                        <span>Tam tinh</span>
                        <strong>{{ number_format($summary['subtotal'] ?? 0, 0, ',', '.') }} VND</strong>
                    </div>
                    <div class="summary-row text-success">
                        <span>Giam gia</span>
                        <strong>-{{ number_format($summary['discount'] ?? 0, 0, ',', '.') }} VND</strong>
                    </div>
                    <div class="summary-row">
                        <span>Phi giao hang</span>
                        <strong>{{ number_format($summary['shipping_fee'] ?? 0, 0, ',', '.') }} VND</strong>
                    </div>
                    @if (($summary['shipping_discount'] ?? 0) > 0)
                        <div class="summary-row text-success">
                            <span>Giam phi giao hang</span>
                            <strong>-{{ number_format($summary['shipping_discount'], 0, ',', '.') }} VND</strong>
                        </div>
                    @endif
                    <div class="summary-divider"></div>
                    <div class="summary-row fs-5">
                        <span>Thanh toan</span>
                        <strong>{{ number_format($summary['final_total'] ?? 0, 0, ',', '.') }} VND</strong>
                    </div>
                </div>
                <form action="{{ route('client.checkout') }}" method="GET" class="mt-3">
                    <button type="submit" class="btn btn-light w-100" @if (!$hasItems) disabled @endif>
                        Tien hanh thanh toan
                    </button>
                </form>
            </div>

            @if ($appliedPromotions->isNotEmpty())
                <div class="promo-recommend">
                    <strong>Khuyen mai dang ap dung</strong>
                    @foreach ($appliedPromotions as $applied)
                        <div class="promo-recommend__item">
                            <span>{{ $applied['promotion']['title'] ?? $applied['promotion']['promo_id'] }}</span>
                            <span class="text-primary">{{ $applied['tier']['label'] ?? ('Bac ' . ($applied['tier']['tier_level'] ?? '')) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($gifts->isNotEmpty())
                <div class="promo-recommend">
                    <strong>Qua tang di kem</strong>
                    <ul class="gift-list mb-0">
                        @foreach ($gifts as $gift)
                            <li>{{ $gift['description'] ?? 'Qua tang' }} (x{{ $gift['quantity'] ?? 1 }})</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($promotionList->isNotEmpty())
                <div class="promo-recommend">
                    <strong>Goi y them uu dai</strong>
                    @foreach ($promotionList as $promotion)
                        <div class="promo-recommend__item">
                            <span>{{ $promotion->title ?? $promotion->get('title') ?? $promotion->promo_id }}</span>
                            <span class="text-muted">{{ $promotion->statusLabel() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </aside>
    </div>
@endsection
