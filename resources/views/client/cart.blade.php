@extends('layouts.client.master')

@section('title', 'Giỏ hàng của bạn - PromoShop')

@section('content')
@php
    $cartCollection = collect($cartItems ?? []);
    $totalQuantity = $cartCollection->sum('quantity');
    $promotionList = collect($promotions ?? []);
    $summary = $summary ?? ['subtotal' => 0, 'discount' => 0, 'shipping_fee' => 0, 'final_total' => 0];
    $appliedPromos = collect(session('applied_promos', []));
    $subtotal = $summary['subtotal'] ?? 0;

    // ✅ Gắn thông tin chi tiết mã đã áp dụng (từ danh sách khuyến mãi)
    $appliedDetails = $promotionList->filter(function ($promo) use ($appliedPromos) {
        $code = $promo->promo_code ?? $promo->promo_id;
        return $appliedPromos->contains($code);
    });
@endphp

<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h1 class="fw-bold mb-1"><i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Giỏ hàng của bạn</h1>
            <p class="text-muted mb-0">Cập nhật số lượng, xem ưu đãi và áp dụng khuyến mãi dễ dàng.</p>
        </div>
        <a href="{{ route('client.home') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Tiếp tục mua sắm
        </a>
    </div>

    <div class="row g-4">
        {{-- 🧁 GIỎ HÀNG --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if ($cartCollection->isEmpty())
                        <div class="text-center py-5">
                            <i class="fa-solid fa-cart-arrow-down fa-3x text-muted mb-3"></i>
                            <h5 class="fw-semibold mb-2">Giỏ hàng của bạn đang trống</h5>
                            <p class="text-muted mb-4">Hãy thêm sản phẩm yêu thích để nhận ưu đãi hấp dẫn.</p>
                            <a href="{{ route('client.home') }}" class="btn btn-primary px-4">
                                <i class="fa-solid fa-plus me-1"></i> Bắt đầu mua sắm
                            </a>
                        </div>
                    @else
                        <div class="vstack gap-3">
                            @foreach ($cartCollection as $item)
                                @php $lineTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 0); @endphp
                                <div class="card border-light shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-3 col-md-2">
                                                <img src="{{ $item['image_url'] ?? 'https://placehold.co/160x160?text=PromoShop' }}"
                                                     class="img-fluid rounded">
                                            </div>
                                            <div class="col">
                                                <h6 class="fw-semibold mb-1">{{ $item['name'] ?? $item['product_id'] }}</h6>
                                                <div class="small text-muted">
                                                    Mã: {{ $item['product_id'] }} · {{ number_format($item['price'], 0, ',', '.') }}đ/sp
                                                </div>
                                                <div class="d-flex align-items-center gap-2 mt-2">
                                                    <form action="{{ route('client.cart.update') }}" method="POST" data-auto-submit="quantity" class="d-flex align-items-center gap-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary js-minus px-2 py-1">−</button>
                                                        <input type="number" name="quantity" class="form-control form-control-sm text-center js-cart-qty" min="1" value="{{ $item['quantity'] }}">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary js-plus px-2 py-1">+</button>
                                                    </form>
                                                    <form action="{{ route('client.cart.remove') }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                                        <button type="submit" class="btn btn-link text-danger text-decoration-none small">
                                                            <i class="fa-solid fa-trash-can me-1"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="col-auto text-end">
                                                <strong>{{ number_format($lineTotal, 0, ',', '.') }}đ</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- ✅ HIỂN THỊ MÃ ĐANG ÁP DỤNG --}}
                        @if ($appliedPromos->isNotEmpty())
                            <div class="mt-4">
                                <h6 class="fw-semibold mb-2"><i class="fa-solid fa-gift text-success me-2"></i> Mã khuyến mãi đang áp dụng</h6>
                                <div class="row g-3">
                                    @foreach ($appliedPromos as $code)
                                        @php $promo = $promotionList->firstWhere('promo_code', $code) ?? $promotionList->firstWhere('promo_id', $code); @endphp
                                        <div class="col-md-6">
                                            <div class="card border-success shadow-sm h-100">
                                                <div class="card-body py-3">
                                                    <h6 class="fw-bold text-success mb-1">{{ $promo->title ?? 'Khuyến mãi' }}</h6>
                                                    <div class="text-muted small">{{ $code }}</div>
                                                    <p class="small mb-2">{{ $promo->description ?? 'Không có mô tả' }}</p>
                                                    <form action="{{ route('client.cart.promo.remove') }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="promotion_id" value="{{ $code }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                            <i class="fa-solid fa-xmark me-1"></i> Hủy mã này
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- 💳 TỔNG QUAN THANH TOÁN --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fa-solid fa-tags text-primary me-1"></i> Áp dụng khuyến mãi</h6>
                    <form id="promoForm" action="{{ route('client.cart.promo.apply') }}" method="POST">
                        @csrf
                        <div class="input-group">
                            <input type="text" id="promotion_code" name="promotion_code" class="form-control" placeholder="Nhập mã khuyến mãi...">
                            <button type="submit" class="btn btn-primary">Áp dụng</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#promoModal">
                            <i class="fa-solid fa-gift me-1"></i> Chọn mã khuyến mãi
                        </button>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fa-solid fa-money-bill-wave text-success me-1"></i> Tổng quan thanh toán</h6>
                    <ul class="list-unstyled small mb-3">
                        <li class="d-flex justify-content-between mb-1">
                            <span>Tạm tính</span>
                            <span>{{ number_format($summary['subtotal'], 0, ',', '.') }}đ</span>
                        </li>

                        {{-- ✅ Hiển thị từng mã giảm --}}
                        @if ($appliedDetails->isNotEmpty())
                            @foreach ($appliedDetails as $promo)
                                @php
                                    $code = $promo->promo_code ?? $promo->promo_id;
                                    $type = $promo->promotion_type ?? 'percent';
                                    $value = $promo->value ?? 0;
                                    $display = $type === 'percent' ? "{$value}%" : number_format($value, 0, ',', '.') . 'đ';
                                @endphp
                                <li class="d-flex justify-content-between text-success mb-1">
                                    <span><i class="fa-solid fa-tag me-1"></i>{{ $code }}</span>
                                    <span>-{{ $display }}</span>
                                </li>
                            @endforeach
                        @elseif($summary['discount'] > 0)
                            <li class="d-flex justify-content-between text-success mb-1">
                                <span>Giảm giá</span>
                                <span>-{{ number_format($summary['discount'], 0, ',', '.') }}đ</span>
                            </li>
                        @endif

                        <li class="d-flex justify-content-between mb-1"><span>Phí giao hàng</span><span>{{ number_format($summary['shipping_fee'], 0, ',', '.') }}đ</span></li>
                        <hr>
                        <li class="d-flex justify-content-between fw-semibold fs-5">
                            <span>Tổng thanh toán</span>
                            <span class="text-success">{{ number_format($summary['final_total'], 0, ',', '.') }}đ</span>
                        </li>
                    </ul>
                    <form action="{{ route('client.checkout') }}" method="GET">
                        <button type="submit" class="btn btn-success w-100 fw-semibold" @if ($cartCollection->isEmpty()) disabled @endif>
                            <i class="fa-solid fa-credit-card me-1"></i> Thanh toán ngay
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 🎁 MODAL --}}
<div class="modal fade" id="promoModal" tabindex="-1" aria-labelledby="promoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa-solid fa-gift me-2"></i>Chọn mã khuyến mãi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        @if ($promotionList->isEmpty())
            <p class="text-center text-muted mb-0">Hiện chưa có chương trình nào khả dụng.</p>
        @else
            <div class="list-group">
                @foreach ($promotionList as $promotion)
                    @php
                        $code = $promotion->promo_code ?? $promotion->promo_id;
                        $minValue = (int)($promotion->minimum_order_value ?? 0);
                        $remaining = max(0, $minValue - $subtotal);
                        $isApplied = $appliedPromos->contains($code);
                        $isEligible = $subtotal >= $minValue;
                    @endphp
                    <label class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-start gap-3">
                            <input type="checkbox" class="form-check-input mt-1 js-promo-check"
                                   value="{{ $code }}" {{ $isApplied ? 'checked' : '' }} {{ $isEligible ? '' : 'disabled' }}>
                            <div>
                                <div class="fw-semibold">{{ $promotion->title ?? 'Khuyến mãi' }}</div>
                                <div class="text-muted small">{{ $code }}</div>
                                @if (!$isEligible)
                                    <div class="text-danger small mt-1">Cần mua thêm {{ number_format($remaining, 0, ',', '.') }}đ để đủ điều kiện</div>
                                @endif
                                @if ($isApplied)
                                    <div class="text-success small mt-1"><i class="fa-solid fa-check-circle me-1"></i> Đã được áp dụng</div>
                                @endif
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="applyPromo('{{ $code }}')" {{ $isEligible ? '' : 'disabled' }}>
                            Áp dụng
                        </button>
                    </label>
                @endforeach
            </div>
        @endif
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-plus').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('.js-cart-qty');
            input.stepUp(); input.dispatchEvent(new Event('change'));
        });
    });
    document.querySelectorAll('.js-minus').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('.js-cart-qty');
            if (parseInt(input.value) > 1) input.stepDown();
            input.dispatchEvent(new Event('change'));
        });
    });
    document.querySelectorAll('form[data-auto-submit="quantity"]').forEach(form => {
        form.querySelector('.js-cart-qty').addEventListener('change', () => form.submit());
    });
});

function applyPromo(code) {
    const input = document.getElementById('promotion_code');
    const modal = bootstrap.Modal.getInstance(document.getElementById('promoModal'));
    input.value = code;
    modal.hide();
    document.querySelectorAll('.js-promo-check').forEach(c => c.checked = (c.value === code));
    setTimeout(() => document.getElementById('promoForm').submit(), 400);
}
</script>
@endpush
@endsection
