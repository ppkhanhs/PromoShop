@extends('layouts.client.master')

@section('title', 'PromoShop - Ưu đãi đa tầng mỗi ngày')

@section('content')
    <div class="row align-items-center mb-5">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold text-primary">Săn ưu đãi đa tầng, tiết kiệm gấp bội</h1>
            <p class="lead text-muted">
                PromoShop giúp bạn áp dụng khuyến mãi thông minh giống GrabFood hoặc Be:
                giảm giá theo từng ngưỡng, miễn phí giao hàng và tặng combo sản phẩm khi đạt đủ điều kiện.
            </p>
            <div class="d-flex gap-3">
                <a href="#products" class="btn btn-primary btn-lg">Bắt đầu mua sắm</a>
                <a href="#promotions" class="btn btn-outline-primary btn-lg">Xem khuyến mãi</a>
            </div>
        </div>
        <div class="col-lg-5 text-center">
            <img src="https://images.promoshop.vn/banners/promo-tiered.png"
                 class="img-fluid rounded-4 shadow-sm"
                 alt="Khuyến mãi đa tầng">
        </div>
    </div>

    <section id="promotions" class="mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h4 mb-0">Khuyến mãi nổi bật</h2>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($promotions as $promotion)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h3 class="h5 mb-1">{{ $promotion->title ?? $promotion->get('title') }}</h3>
                                <p class="text-muted mb-2">
                                    Thời gian: {{ $promotion->get('start_date') ?? 'Không giới hạn' }}
                                    – {{ $promotion->get('end_date') ?? 'Không giới hạn' }}
                                </p>
                                @if ($promotion->tiers())
                                    <ul class="mb-0 text-muted">
                                        @foreach ($promotion->tiers() as $tier)
                                            <li>
                                                <strong>{{ $tier->label() }}:</strong>
                                                Đơn từ {{ $tier->formattedMinValue() }} đ
                                                @if ($tier->get('discount_percent'))
                                                    • Giảm {{ $tier->get('discount_percent') }}%
                                                @endif
                                                @if ($tier->get('discount_amount'))
                                                    • Giảm thêm {{ number_format($tier->get('discount_amount'), 0, ',', '.') }} đ
                                                @endif
                                                @if ($tier->get('freeship'))
                                                    • Freeship
                                                @endif
                                                @if ($tier->get('gift_product_id'))
                                                    • Tặng {{ $tier->get('combo_description') ?? 'sản phẩm kèm' }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted mb-0">Khuyến mãi áp dụng tự động.</p>
                                @endif
                            </div>
                            <span class="badge bg-success-subtle text-success">{{ $promotion->statusLabel() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Chưa có khuyến mãi nào.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section id="products">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Sản phẩm nổi bật</h2>
            <span class="text-muted">Tổng cộng {{ count($products) }} món</span>
        </div>

        <div class="row g-4">
            @forelse ($products as $product)
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <img src="{{ $product->image_url ?? 'https://images.promoshop.vn/placeholder/product.png' }}"
                             class="card-img-top"
                             alt="{{ $product->name }}">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $product->name }}</h5>
                            <p class="text-muted mb-2">{{ $product->category ?? 'Khác' }}</p>
                            <p class="fs-5 fw-semibold text-primary">{{ number_format($product->price, 0, ',', '.') }} đ</p>
                            <form action="{{ route('client.cart.add') }}" method="POST" class="mt-auto">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                                <div class="input-group">
                                    <input type="number" name="quantity" class="form-control" value="1" min="1">
                                    <button type="submit" class="btn btn-primary">
                                        Thêm vào giỏ
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-secondary">Chưa có sản phẩm nào.</div>
                </div>
            @endforelse
        </div>
    </section>
@endsection
