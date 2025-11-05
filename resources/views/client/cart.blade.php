@extends('layouts.client.master')

@section('title', 'Giỏ hàng của bạn - PromoShop')

@section('content')
@php
    $cartCollection = collect($cartItems ?? []);
    $totalQuantity = max(0, (int) $cartCollection->sum('quantity'));
    $summary = $summary ?? [
        'subtotal' => 0,
        'discount' => 0,
        'shipping_fee' => 0,
        'shipping_discount' => 0,
        'final_total' => 0,
        'applied_promotions' => [],
        'gifts' => [],
    ];
    $subtotal = (int) ($summary['subtotal'] ?? 0);

    $promotionCollection = collect($promotions ?? [])->map(
        fn ($promotion) => $promotion instanceof \App\Models\Cassandra\Promotion
            ? $promotion
            : \App\Models\Cassandra\Promotion::from((array) $promotion),
    );

    $appliedSummaryItems = collect($summary['applied_promotions'] ?? []);
    $appliedSummaryMap = [];
    foreach ($appliedSummaryItems as $item) {
        $promoData = $item['promotion'] ?? [];
        $identifier = strtoupper((string) ($promoData['promo_id'] ?? $promoData['promo_code'] ?? $promoData['code'] ?? ''));
        if ($identifier !== '') {
            $appliedSummaryMap[$identifier] = $item;
        }
    }

    $promotionOptions = $promotionCollection
        ->map(function (\App\Models\Cassandra\Promotion $promotion) use ($subtotal, $totalQuantity, $appliedSummaryMap) {
            $code = $promotion->get('promo_code') ?? $promotion->get('promo_id') ?? '';
            $identifier = strtoupper((string) $code);

            $tiers = collect($promotion->tiers())
                ->sortBy(fn ($tier) => (int) ($tier->get('min_value') ?? $tier->get('min_order') ?? 0))
                ->values();

            $eligibleTier = null;
            $nextTier = null;
            foreach ($tiers as $tier) {
                $minAmount = (int) ($tier->get('min_value') ?? $tier->get('min_order') ?? 0);
                $minQty = (int) ($tier->get('min_quantity') ?? $tier->get('min_qty') ?? 0);
                $meetsAmount = $subtotal >= $minAmount;
                $meetsQty = $minQty <= 0 || $totalQuantity >= $minQty;

                if ($meetsAmount && $meetsQty) {
                    $eligibleTier = $tier;
                } elseif (! $eligibleTier && ! $nextTier) {
                    $nextTier = $tier;
                }
            }

            $referenceTier = $eligibleTier ?: ($nextTier ?: ($tiers->first() ?: null));
            $minAmountTarget = $referenceTier ? (int) ($referenceTier->get('min_value') ?? $referenceTier->get('min_order') ?? 0) : 0;
            $minQtyTarget = $referenceTier ? (int) ($referenceTier->get('min_quantity') ?? $referenceTier->get('min_qty') ?? 0) : 0;

            $needAmount = max(0, $minAmountTarget - $subtotal);
            $needQty = max(0, $minQtyTarget - $totalQuantity);
            $needMessages = [];
            if ($needAmount > 0) {
                $needMessages[] = 'Cần mua thêm ' . number_format($needAmount, 0, ',', '.') . ' VND';
            }
            if ($needQty > 0) {
                $needMessages[] = 'Cần thêm ' . $needQty . ' sản phẩm';
            }
            $needsMessage = implode(' và ', $needMessages);

            $benefitTier = $eligibleTier ?: ($tiers->first() ?: null);
            $benefitParts = [];
            if ($benefitTier) {
                $percent = (int) ($benefitTier->get('discount_percent') ?? $benefitTier->get('discount_percentual') ?? 0);
                $amount = (int) ($benefitTier->get('discount_amount') ?? $benefitTier->get('discount') ?? 0);
                if ($percent > 0) {
                    $benefitParts[] = 'Giảm ' . $percent . '%';
                }
                if ($amount > 0) {
                    $benefitParts[] = 'Giảm ' . number_format($amount, 0, ',', '.') . ' VND';
                }
                if ($benefitTier->get('freeship') || $benefitTier->get('free_shipping')) {
                    $benefitParts[] = 'Freeship';
                }
                $reward = $benefitTier->get('combo_description') ?? $benefitTier->get('reward');
                if ($reward && empty($benefitParts)) {
                    $benefitParts[] = (string) $reward;
                }
            }
            if (empty($benefitParts)) {
                $benefitParts[] = 'Ưu đãi đặc biệt';
            }

            $conditionParts = [];
            if ($minAmountTarget > 0) {
                $conditionParts[] = 'Đơn từ ' . number_format($minAmountTarget, 0, ',', '.') . ' VND';
            }
            if ($minQtyTarget > 0) {
                $conditionParts[] = 'SL từ ' . $minQtyTarget;
            }

            $isApplied = isset($appliedSummaryMap[$identifier]);
            $appliedDetail = $appliedSummaryMap[$identifier] ?? null;

            return [
                'identifier' => $identifier,
                'code' => $code,
                'title' => $promotion->get('title') ?? $code,
                'status_label' => $promotion->statusLabel(),
                'benefit_text' => implode(' · ', array_filter($benefitParts)),
                'condition_text' => implode(' · ', array_filter($conditionParts)),
                'needs_message' => $needsMessage,
                'is_applied' => $isApplied,
                'is_eligible' => $eligibleTier !== null,
                'is_disabled' => ! $isApplied && $eligibleTier === null,
                'promotion' => $promotion,
                'applied_detail' => $appliedDetail,
            ];
        })
        ->values();

    $gifts = collect($summary['gifts'] ?? []);
@endphp

<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 gap-3">
        <div>
            <h1 class="fw-bold mb-1"><i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Giỏ hàng của bạn</h1>
            <p class="text-muted mb-0">Cập nhật số lượng, theo dõi ưu đãi và áp dụng khuyến mãi dễ dàng.</p>
        </div>
        <a href="{{ route('client.home') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Tiếp tục mua sắm
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @if ($cartCollection->isEmpty())
                        <div class="text-center py-5">
                            <i class="fa-solid fa-cart-arrow-down fa-3x text-muted mb-3"></i>
                            <h5 class="fw-semibold mb-2">Giỏ hàng của bạn đang trống</h5>
                            <p class="text-muted mb-4">Hãy thêm sản phẩm yêu thích để trải nghiệm ưu đãi hấp dẫn.</p>
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
                                                <img src="{{ $item['image_url'] ?? 'https://placehold.co/160x160?text=PromoShop' }}" class="img-fluid rounded-3" alt="{{ $item['name'] ?? $item['product_id'] }}">
                                            </div>
                                            <div class="col">
                                                <h6 class="fw-semibold mb-1">{{ $item['name'] ?? $item['product_id'] }}</h6>
                                                <div class="small text-muted">Mã: {{ $item['product_id'] }} · {{ number_format($item['price'], 0, ',', '.') }}đ/sp</div>
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
                                                            <i class="fa-solid fa-trash-can me-1"></i> Xóa
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
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fa-solid fa-tags text-primary me-2"></i>Áp dụng khuyến mãi</h6>
                    <form action="{{ route('client.cart.promo.apply') }}" method="POST" class="promo-code-form mb-3">
                        @csrf
                        <div class="input-group">
                            <input type="text" id="promotion_code" name="promotion_code" class="form-control" placeholder="Nhập mã khuyến mãi">
                            <button type="submit" class="btn btn-primary">Áp dụng</button>
                        </div>
                    </form>
                    <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#promotionModal">
                        <i class="fa-solid fa-gift me-1"></i> Chọn mã khuyến mãi
                    </button>

                    @if (!empty($appliedSummaryMap))
                        <div class="mt-3">
                            <strong class="small text-uppercase text-muted">Đang áp dụng</strong>
                            <div class="promo-applied-list mt-2">
                                @foreach ($appliedSummaryMap as $identifier => $applied)
                                    @php
                                        $promoData = $applied['promotion'] ?? [];
                                        $promoTitle = $promoData['title'] ?? ($promoData['promo_id'] ?? $promoData['promo_code'] ?? 'Khuyến mãi');
                                        $discountValue = (int) ($applied['discount'] ?? 0);
                                        $shippingValue = (int) ($applied['shipping_discount'] ?? 0);
                                    @endphp
                                    <div class="promo-applied-pill">
                                        <div>
                                            <div class="fw-semibold">{{ $promoTitle }}</div>
                                            <div class="text-success small">
                                                @if ($discountValue > 0)
                                                    Giảm {{ number_format($discountValue, 0, ',', '.') }}đ
                                                @endif
                                                @if ($shippingValue > 0)
                                                    @if ($discountValue > 0) · @endif Freeship {{ number_format($shippingValue, 0, ',', '.') }}đ
                                                @endif
                                            </div>
                                        </div>
                                        <form action="{{ route('client.cart.promo.remove') }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="promotion_id" value="{{ $promoData['promo_id'] ?? $promoData['promo_code'] ?? $promoData['code'] ?? '' }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Bỏ</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fa-solid fa-money-bill-wave text-success me-2"></i>Tổng quan thanh toán</h6>
                    <ul class="list-unstyled small mb-3 promo-summary">
                        <li class="d-flex justify-content-between mb-1">
                            <span>Tạm tính</span>
                            <span>{{ number_format($summary['subtotal'], 0, ',', '.') }}đ</span>
                        </li>
                        @if (!empty($appliedSummaryMap))
                            @foreach ($appliedSummaryMap as $identifier => $applied)
                                @php
                                    $promoData = $applied['promotion'] ?? [];
                                    $promoTitle = $promoData['title'] ?? ($promoData['promo_id'] ?? $promoData['promo_code'] ?? 'Khuyến mãi');
                                    $discountValue = (int) ($applied['discount'] ?? 0);
                                    $shippingValue = (int) ($applied['shipping_discount'] ?? 0);
                                @endphp
                                @if ($discountValue > 0)
                                    <li class="d-flex justify-content-between text-success mb-1">
                                        <span><i class="fa-solid fa-tag me-1"></i>{{ $promoTitle }}</span>
                                        <span>-{{ number_format($discountValue, 0, ',', '.') }}đ</span>
                                    </li>
                                @endif
                                @if ($shippingValue > 0)
                                    <li class="d-flex justify-content-between text-success mb-1">
                                        <span class="ps-4">Freeship</span>
                                        <span>-{{ number_format($shippingValue, 0, ',', '.') }}đ</span>
                                    </li>
                                @endif
                            @endforeach
                        @endif
                        <li class="d-flex justify-content-between text-success mb-1">
                            <span>Giảm giá</span>
                            <span>-{{ number_format($summary['discount'] ?? 0, 0, ',', '.') }}đ</span>
                        </li>
                        <li class="d-flex justify-content-between mb-1">
                            <span>Phí giao hàng</span>
                            <span>{{ number_format($summary['shipping_fee'] ?? 0, 0, ',', '.') }}đ</span>
                        </li>
                        @if (!empty($summary['shipping_discount']))
                            <li class="d-flex justify-content-between text-success mb-1">
                                <span>Giảm phí giao hàng</span>
                                <span>-{{ number_format($summary['shipping_discount'], 0, ',', '.') }}đ</span>
                            </li>
                        @endif
                    </ul>
                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <span class="fw-semibold">Tổng thanh toán</span>
                        <span class="fs-5 fw-bold text-primary">{{ number_format($summary['final_total'], 0, ',', '.') }}đ</span>
                    </div>
                    <form action="{{ route('client.checkout') }}" method="GET" class="mt-3">
                        <button type="submit" class="btn btn-success w-100" @if ($cartCollection->isEmpty()) disabled @endif>
                            <i class="fa-solid fa-cash-register me-1"></i> Thanh toán ngay
                        </button>
                    </form>
                </div>
            </div>

            @if ($gifts->isNotEmpty())
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-2"><i class="fa-solid fa-gift text-warning me-2"></i>Quà tặng kèm</h6>
                        <ul class="list-unstyled small mb-0">
                            @foreach ($gifts as $gift)
                                <li class="d-flex justify-content-between align-items-center py-1">
                                    <span>{{ $gift['description'] ?? 'Quà tặng' }}</span>
                                    <span class="badge bg-light text-primary">x{{ $gift['quantity'] ?? 1 }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<div class="modal fade" id="promotionModal" tabindex="-1" aria-labelledby="promotionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="promotionModalLabel">Chọn mã khuyến mãi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                @if ($promotionOptions->isEmpty())
                    <p class="text-muted mb-0">Hiện chưa có mã khuyến mãi khả dụng.</p>
                @else
                    <div class="promo-modal-list">
                        @foreach ($promotionOptions as $option)
                            @php
                                $isApplied = $option['is_applied'];
                                $isEligible = $option['is_eligible'];
                                $isDisabled = $option['is_disabled'];
                                $detail = $option['applied_detail'];
                                $discountValue = $detail['discount'] ?? 0;
                                $shippingValue = $detail['shipping_discount'] ?? 0;
                            @endphp
                            <div class="promo-modal-item @if($isApplied) is-applied @elseif($isDisabled) is-disabled @endif">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" disabled @checked($isApplied)>
                                </div>
                                <div class="promo-modal-info">
                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                        <div>
                                            <div class="fw-semibold">{{ $option['title'] }}</div>
                                            <div class="text-muted small">{{ $option['benefit_text'] }}</div>
                                            @if (!empty($option['condition_text']))
                                                <div class="text-muted small">Điều kiện: {{ $option['condition_text'] }}</div>
                                            @endif
                                            @if (! $isApplied && !empty($option['needs_message']))
                                                <div class="text-danger small mt-1">{{ $option['needs_message'] }}</div>
                                            @endif
                                            @if ($isApplied)
                                                <div class="text-success small mt-1">
                                                    Đã giảm {{ number_format($discountValue ?? 0, 0, ',', '.') }}đ
                                                    @if (($shippingValue ?? 0) > 0)
                                                        · Freeship {{ number_format($shippingValue ?? 0, 0, ',', '.') }}đ
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        <span class="badge bg-light text-secondary align-self-start">{{ $option['status_label'] }}</span>
                                    </div>
                                </div>
                                <div class="promo-modal-actions">
                                    @if ($isApplied)
                                        <form action="{{ route('client.cart.promo.remove') }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="promotion_id" value="{{ $option['code'] }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Bỏ chọn</button>
                                        </form>
                                    @elseif ($isEligible)
                                        <form action="{{ route('client.cart.promo.apply') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="promotion_code" value="{{ $option['code'] }}">
                                            <button type="submit" class="btn btn-sm btn-outline-success">Áp dụng</button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Chưa đủ điều kiện</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="modal-footer justify-content-between">
                <span class="text-muted small">Đơn của bạn: {{ number_format($subtotal, 0, ',', '.') }}đ · {{ $totalQuantity }} sản phẩm</span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.promo-code-form .form-control {
    border-radius: 0.75rem 0 0 0.75rem;
}
.promo-code-form .btn {
    border-radius: 0 0.75rem 0.75rem 0;
}
.promo-applied-list {
    display: grid;
    gap: 12px;
}
.promo-applied-pill {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 14px;
    background: rgba(25, 135, 84, 0.05);
}
.promo-modal-list {
    display: grid;
    gap: 14px;
}
.promo-modal-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 14px;
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    transition: border-color 0.2s ease, background-color 0.2s ease;
}
.promo-modal-item.is-applied {
    border-color: rgba(25, 135, 84, 0.35);
    background: rgba(25, 135, 84, 0.06);
}
.promo-modal-item.is-disabled {
    opacity: 0.65;
}
.promo-modal-actions form {
    margin: 0;
}
.promo-summary li + li {
    border-top: 1px dashed rgba(148, 163, 184, 0.35);
    padding-top: 6px;
    margin-top: 6px;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-auto-submit="quantity"]').forEach(form => {
        const qtyInput = form.querySelector('.js-cart-qty');

        form.addEventListener('change', () => {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });

        form.querySelectorAll('.js-plus').forEach(btn => {
            btn.addEventListener('click', () => {
                qtyInput.stepUp();
                qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        form.querySelectorAll('.js-minus').forEach(btn => {
            btn.addEventListener('click', () => {
                if (parseInt(qtyInput.value, 10) > 1) {
                    qtyInput.stepDown();
                }
                qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
});
</script>
@endpush
