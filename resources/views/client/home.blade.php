@extends('layouts.client.master')

@section('title', 'PromoShop - Trang uu dai thong minh')

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
                Smart deals daily
            </span>
            <h1 class="promo-hero__headline">
                San uu dai da tang, gom tron ma giam gia theo kieu super app
            </h1>
            <p class="promo-hero__lead">
                PromoShop lay du lieu tu Cassandra de dong bo mo hinh khuyen mai bac thang
                giong Grab, Shopee: ket hop giam gia, freeship va combo tang qua trong mot noi dung duy nhat.
            </p>
            <div class="promo-hero__actions">
                <a href="#products" class="btn btn-primary btn-lg">Kham pha san pham</a>
                <a href="#promotions" class="btn btn-outline-primary btn-lg">Xem tat ca uu dai</a>
            </div>
            <div class="promo-stats">
                <div class="promo-stat">
                    <strong>{{ $promotionCount }}</strong>
                    <span>Chuong trinh dang hoat dong</span>
                </div>
                <div class="promo-stat">
                    <strong>{{ $productCount }}</strong>
                    <span>San pham dang mo ban</span>
                </div>
                <div class="promo-stat">
                    <strong>{{ $categoryCount }}</strong>
                    <span>Nhom hang duoc tu dong goi y</span>
                </div>
                <div class="promo-stat">
                    <strong>{{ $bestSavingDisplay }}</strong>
                    <span>Gia tri giam toi da</span>
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
                <h2 class="h4 mb-1">Spotlight khuyen mai</h2>
                <p class="text-muted mb-0">Tong hop chuong trinh noi bat nhat hien tai</p>
            </div>
            <a href="{{ route('client.cart') }}" class="btn btn-light btn-sm">
                Xem uu dai da ap dung
            </a>
        </div>

        @if ($spotlights->isEmpty())
            <div class="alert alert-secondary">Chua co chuong trinh nao duoc kich hoat.</div>
        @else
            <div class="promo-spotlight">
                @foreach ($spotlights as $promotion)
                    @php
                        $start = $promotion->get('start_date') ?? 'Khong gioi han';
                        $end = $promotion->get('end_date') ?? 'Khong gioi han';
                        $tiers = method_exists($promotion, 'tiers') ? collect($promotion->tiers() ?? []) : collect();
                    @endphp
                    <article class="promo-card">
                        <span class="promo-tag">{{ $promotion->statusLabel() }}</span>
                        <h3 class="promo-card__title">
                            {{ $promotion->title ?? $promotion->get('title') ?? $promotion->promo_id ?? 'Khuyen mai' }}
                        </h3>
                        <p class="promo-card__period">
                            Thoi gian: {{ $start }} - {{ $end }}
                        </p>
                        @if ($tiers->isNotEmpty())
                            <ul class="promo-tier-list">
                                @foreach ($tiers as $tier)
                                    <li>
                                        <strong>{{ $tier->label() }}</strong> ·
                                        Don tu {{ $tier->formattedMinValue() }}
                                        @if ($tier->get('discount_percent'))
                                            · Giam {{ $tier->get('discount_percent') }}%
                                        @endif
                                        @if ($tier->get('discount_amount'))
                                            · Giam them {{ number_format($tier->get('discount_amount'), 0, ',', '.') }} VND
                                        @endif
                                        @if ($tier->get('freeship'))
                                            · Freeship
                                        @endif
                                        @if ($tier->get('gift_product_id'))
                                            · Tang {{ $tier->get('combo_description') ?? 'qua kem' }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted small mb-0">
                                Ap dung toan bo don hang hop le. Kiem tra gio hang de xem cach toi uu.
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
                <h2 class="h4 mb-1" id="products">San pham goi y tu he thong</h2>
                <p class="text-muted mb-0">
                    Du lieu lay tu bang products va products_by_id tren Cassandra.
                </p>
            </div>
            <span class="badge rounded-pill text-bg-light">
                Tong cong {{ $productCount }} san pham
            </span>
        </div>

        @if ($productList->isEmpty())
            <div class="alert alert-warning">Chua co san pham nao trong he thong.</div>
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
                                <span class="badge text-bg-light">{{ $product->category ?? 'Khac' }}</span>
                                <h3 class="h6 mt-2 mb-1">{{ $product->name ?? $product->product_id }}</h3>
                                <p class="text-muted small mb-0">
                                    Ma hang: {{ $product->product_id }}
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
                                    Xem gio hang
                                </a>
                                <form action="{{ route('client.cart.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        Them vao gio
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
                    <h3 class="h5 mt-3">Ap dung uu dai trong 3 buoc</h3>
                </div>
                <div class="d-flex flex-column flex-md-row gap-3">
                    <div class="promo-recommend flex-fill">
                        <strong>1. Chon san pham</strong>
                        <span class="text-muted small">
                            Lay danh sach tu bang products de hien thi goi y danh muc.
                        </span>
                    </div>
                    <div class="promo-recommend flex-fill">
                        <strong>2. He thong tinh uu dai</strong>
                        <span class="text-muted small">
                            Promotion_engine doc promotion_tiers va promotions_by_status de chon toi uu.
                        </span>
                    </div>
                    <div class="promo-recommend flex-fill">
                        <strong>3. Dat hang</strong>
                        <span class="text-muted small">
                            Thong tin thanh toan duoc luu vao orders va promotion_logs de theo doi.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
