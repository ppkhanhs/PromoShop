@extends('layouts.client.master')

@section('title', 'PromoShop - Trang ưu đãi thông minh')

@section('content')
    @php
        $promotionList = collect($promotions ?? []);
        $productList = collect($products ?? []);
        $promotionCount = $promotionList->count();
        $productCount = $productList->count();
        $categoryCount = $productList->pluck('category')->filter()->unique()->count();
        $bestSaving = 0;

        $promotionList->each(function ($promotion) use (&$bestSaving) {
            $max = (float) ($promotion->max_discount_amount ?? $promotion->get('max_discount_amount') ?? 0);
            $percent = (float) ($promotion->discount_percent ?? $promotion->get('discount_percent') ?? 0);
            $bestSaving = max($bestSaving, $max, $percent ? $percent * 1000 : 0);

            if (method_exists($promotion, 'tiers')) {
                foreach ($promotion->tiers() ?? [] as $tier) {
                    $tierAmount = (float) ($tier->get('discount_amount') ?? 0);
                    $tierPercent = (float) ($tier->get('discount_percent') ?? 0);
                    $bestSaving = max($bestSaving, $tierAmount, $tierPercent ? $tierPercent * 1000 : 0);
                }
            }
        });

        $bestSavingDisplay = $bestSaving > 0 ? number_format($bestSaving, 0, ',', '.') . ' VND' : '0 VND';
        $spotlights = $promotionList->take(3);
    @endphp

    <section class="promo-hero mb-5">
        <div>
            <span class="promo-hero__eyebrow">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M12 3v18M5 9h7m0 0 7 6" stroke="#1877f2" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Ưu đãi thông minh mỗi ngày
            </span>
            <h1 class="promo-hero__headline">
                Săn ưu đãi đa tầng, gom trọn mã giảm giá theo kiểu super app
            </h1>
            <p class="promo-hero__lead">
                PromoShop lấy dữ liệu từ Cassandra để đồng bộ mô hình khuyến mãi bậc thang
                giống Grab, Shopee: kết hợp giảm giá, freeship và combo tặng quà trong một nội dung duy nhất.
            </p>
            <div class="promo-hero__actions">
                <a href="#products" class="btn btn-primary btn-lg">Khám phá sản phẩm</a>
                <a href="#promotions" class="btn btn-outline-primary btn-lg">Xem tất cả ưu đãi</a>
            </div>
            <div class="promo-stats">
                <div class="promo-stat">
                    <strong>{{ $promotionCount }}</strong>
                    <span>Chương trình đang hoạt động</span>
                </div>
                <div class="promo-stat">
                    <strong>{{ $productCount }}</strong>
                    <span>Sản phẩm đang mở bán</span>
                </div>
                <div class="promo-stat">
                    <strong>{{ $categoryCount }}</strong>
                    <span>Nhóm hàng được tự động gợi ý</span>
                </div>
                <div class="promo-stat">
                    <strong>{{ $bestSavingDisplay }}</strong>
                    <span>Giá trị giảm tối đa</span>
                </div>
            </div>
        </div>
        <div class="text-center">
            <img src="https://images.promoshop.vn/banners/promo-dashboard.png"
                 alt="Promo dashboard"
                 class="img-fluid rounded-4 shadow-sm">
        </div>
    </section>

    <section id="promotions" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 mb-1">Điểm nhấn khuyến mãi</h2>
                <p class="text-muted mb-0">Tổng hợp chương trình nổi bật nhất hiện tại</p>
            </div>
            <a href="{{ route('client.cart') }}" class="btn btn-light btn-sm">
                Xem ưu đãi đã áp dụng
            </a>
        </div>

        @if ($spotlights->isEmpty())
            <div class="alert alert-secondary">Chưa có chương trình nào được kích hoạt.</div>
        @else
            <div class="promo-spotlight">
                @foreach ($spotlights as $promotion)
                    @php
                        $start = $promotion->get('start_date') ?? 'Không giới hạn';
                        $end = $promotion->get('end_date') ?? 'Không giới hạn';
                        $tiers = method_exists($promotion, 'tiers') ? collect($promotion->tiers() ?? []) : collect();
                    @endphp
                    <article class="promo-card">
                        <span class="promo-tag">{{ $promotion->statusLabel() }}</span>
                        <h3 class="promo-card__title">
                            {{ $promotion->title ?? $promotion->get('title') ?? $promotion->promo_id ?? 'Khuyến mãi' }}
                        </h3>
                        <p class="promo-card__period">
                            Thời gian: {{ $start }} - {{ $end }}
                        </p>
                        @if ($tiers->isNotEmpty())
                            <ul class="promo-tier-list">
                                @foreach ($tiers as $tier)
                                    <li>
                                        <strong>{{ $tier->label() }}</strong> ·
                                        &bull; Đơn từ {{ $tier->formattedMinValue() }}
                                        @if ($tier->get('discount_percent'))
                                            &bull; Giảm {{ $tier->get('discount_percent') }}%
                                        @endif
                                        @if ($tier->get('discount_amount'))
                                            &bull; Giảm thêm {{ number_format($tier->get('discount_amount'), 0, ',', '.') }} VND
                                        @endif
                                        @if ($tier->get('freeship'))
                                            &bull; Freeship
                                        @endif
                                        @if ($tier->get('gift_product_id'))
                                            &bull; Tặng {{ $tier->get('combo_description') ?? 'quà kèm' }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted small mb-0">
                                Áp dụng toàn bộ đơn hàng hợp lệ. Kiểm tra giỏ hàng để xem cách tối ưu.
                            </p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 mb-1" id="products">Sản phẩm gợi ý từ hệ thống</h2>
                <p class="text-muted mb-0">
                    Dữ liệu lấy từ bảng products và products_by_id trên Cassandra.
                </p>
            </div>
            <span class="badge rounded-pill text-bg-light">
                Tổng cộng {{ $productCount }} sản phẩm
            </span>
        </div>

        @if ($productList->isEmpty())
            <div class="alert alert-warning">Chưa có sản phẩm nào trong hệ thống.</div>
        @else
            <div class="promo-product-grid">
                @foreach ($productList as $product)
                    <article class="promo-product-card">
                        <div class="promo-product-media">
                            <img src="{{ $product->image_url ?? 'https://images.promoshop.vn/placeholder/product.png' }}"
                                 alt="{{ $product->name ?? 'Product' }}">
                        </div>
                        <div class="promo-product-body">
                            <div>
                                <span class="badge text-bg-light">{{ $product->category ?? 'Khác' }}</span>
                                <h3 class="h6 mt-2 mb-1">{{ $product->name ?? $product->product_id }}</h3>
                                <p class="text-muted small mb-0">
                                    Mã hàng: {{ $product->product_id }}
                                </p>
                            </div>
                            <div class="price-chip">
                                {{ number_format($product->price ?? 0, 0, ',', '.') }} VND
                                @if (!empty($product->compare_at_price) && $product->compare_at_price > $product->price)
                                    <span class="old">{{ number_format($product->compare_at_price, 0, ',', '.') }} VND</span>
                                @endif
                            </div>
                            <div class="promo-product-actions">
                                <a href="{{ route('client.cart') }}" class="btn btn-outline-primary btn-sm">
                                    Xem giỏ hàng
                                </a>
                                <form action="{{ route('client.cart.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        Thêm vào giỏ
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-md-flex align-items-center gap-4">
                <div class="flex-shrink-0">
                    <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2">
                        Cassandra funnel
                    </span>
                    <h3 class="h5 mt-3">Áp dụng ưu đãi trong 3 bước</h3>
                </div>
                <div class="d-flex flex-column flex-md-row gap-3">
                    <div class="promo-recommend flex-fill">
                        <strong>1. Chọn sản phẩm</strong>
                        <span class="text-muted small">
                            Lấy danh sách từ bảng products để hiển thị gợi ý danh mục.
                        </span>
                    </div>
                    <div class="promo-recommend flex-fill">
                        <strong>2. Hệ thống tính ưu đãi</strong>
                        <span class="text-muted small">
                            Promotion_engine đọc promotion_tiers và promotions_by_status để chọn tối ưu.
                        </span>
                    </div>
                    <div class="promo-recommend flex-fill">
                        <strong>3. Đặt hàng</strong>
                        <span class="text-muted small">
                            Thông tin thanh toán được lưu vào orders và promotion_logs để theo dõi.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
