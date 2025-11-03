@extends('layouts.client.master')

@section('title', 'Giỏ hàng của bạn - PromoShop')

@section('content')
    @php
        $cartCollection = collect($cartItems ?? []);
        $totalQuantity = $cartCollection->sum('quantity');
        $appliedPromotions = collect($summary['applied_promotions'] ?? []);
        $gifts = collect($summary['gifts'] ?? []);
        $promotionList = collect($promotions ?? [])->take(3);
        $subtotalValue = (int) ($summary['subtotal'] ?? 0);
        $identifierKeys = ['promo_id', 'promo_code', 'code', 'promotion_code', 'id'];
        $normalizeIdentifiers = function ($promotion) use ($identifierKeys) {
            $payload = $promotion instanceof \App\Models\Cassandra\Promotion ? $promotion->toArray() : (array) $promotion;
            $normalized = [];
            $raw = [];
            foreach ($identifierKeys as $key) {
                $value = $payload[$key] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                $stringValue = (string) $value;
                $raw[] = $stringValue;
                $normalized[] = strtoupper($stringValue);
            }

            return [$normalized, $raw];
        };
        $selectedLookup = collect(session('cart.promotions.selected', []))
            ->mapWithKeys(fn ($value) => [strtoupper((string) $value) => true])
            ->all();
        $disabledLookup = collect(session('cart.promotions.disabled', []))
            ->mapWithKeys(fn ($value) => [strtoupper((string) $value) => true])
            ->all();
        $appliedLookup = [];
        foreach ($appliedPromotions as $appliedPromotion) {
            [$ids] = $normalizeIdentifiers($appliedPromotion['promotion'] ?? []);
            foreach ($ids as $identifier) {
                $appliedLookup[$identifier] = $appliedPromotion;
            }
        }
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
                        <div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
                            <div>
                                <h2 class="h6 mb-1">Ưu đãi cho đơn hàng</h2>
                                <p class="text-muted small mb-0">Chọn hoặc bỏ chọn mã khuyến mãi phù hợp với đơn hàng hiện tại.</p>
                            </div>
                            <form action="{{ route('client.cart.promo.apply') }}" method="POST" class="promo-code-form ms-auto">
                                @csrf
                                <label for="promotion_code" class="visually-hidden">Mã khuyến mãi</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="promotion_code" name="promotion_code" class="form-control"
                                        placeholder="Nhap ma" value="{{ old('promotion_code') }}" autocomplete="off">
                                    <button type="submit" class="btn btn-outline-primary">
                                        Áp dụng
                                    </button>
                                </div>
                            </form>
                        </div>

                        @if ($errors->has('promotion_code'))
                            <p class="text-danger small mt-1 mb-3">
                                {{ $errors->first('promotion_code') }}
                            </p>
                        @endif

                        <div class="promo-option-list">
                            @forelse ($promotions as $promotion)
                                @php
                                    $promotionModel = $promotion instanceof \App\Models\Cassandra\Promotion ? $promotion : \App\Models\Cassandra\Promotion::from((array) $promotion);
                                    [$normalizedIds, $rawIds] = $normalizeIdentifiers($promotionModel);
                                    $primaryIdentifier = $rawIds[0] ?? ($promotionModel->get('promo_id') ?? ($promotionModel->get('promo_code') ?? ''));

                                    $isApplied = false;
                                    $appliedData = null;
                                    foreach ($normalizedIds as $identifier) {
                                        if (isset($appliedLookup[$identifier])) {
                                            $isApplied = true;
                                            $appliedData = $appliedLookup[$identifier];
                                            break;
                                        }
                                    }

                                    $isSelected = $isApplied;
                                    if (! $isSelected) {
                                        foreach ($normalizedIds as $identifier) {
                                            if (isset($selectedLookup[$identifier])) {
                                                $isSelected = true;
                                                break;
                                            }
                                        }
                                    }

                                    $isManuallyDisabled = false;
                                    foreach ($normalizedIds as $identifier) {
                                        if (isset($disabledLookup[$identifier])) {
                                            $isManuallyDisabled = true;
                                            break;
                                        }
                                    }

                                    $tiers = $promotionModel->tiers();
                                    usort($tiers, function ($a, $b) {
                                        $minA = (int) $a->get('min_value', $a->get('min_order', 0));
                                        $minB = (int) $b->get('min_value', $b->get('min_order', 0));
                                        return $minA <=> $minB;
                                    });

                                    $lowestTier = $tiers[0] ?? null;
                                    $eligibleTier = null;
                                    $nextTier = null;
                                    foreach ($tiers as $tier) {
                                        $minAmount = (int) $tier->get('min_value', $tier->get('min_order', 0));
                                        $minQty = (int) $tier->get('min_quantity', $tier->get('min_qty', 0));
                                        $meetsAmount = $subtotalValue >= $minAmount;
                                        $meetsQty = $minQty <= 0 || $totalQuantity >= $minQty;
                                        if ($meetsAmount && $meetsQty) {
                                            $eligibleTier = $tier;
                                        } elseif (! $nextTier) {
                                            $nextTier = $tier;
                                        }
                                    }

                                    $referenceTier = $eligibleTier ?: ($nextTier ?: $lowestTier);
                                    $minAmountTarget = $referenceTier ? (int) $referenceTier->get('min_value', $referenceTier->get('min_order', 0)) : 0;
                                    $minQtyTarget = $referenceTier ? (int) $referenceTier->get('min_quantity', $referenceTier->get('min_qty', 0)) : 0;
                                    $needAmount = max(0, $minAmountTarget - $subtotalValue);
                                    $needQty = max(0, $minQtyTarget - $totalQuantity);
                                    $isEligible = $eligibleTier !== null;
                                    $isDisabled = $isManuallyDisabled || (! $isEligible && ! $isSelected);

                                    $benefitTier = $eligibleTier ?: $lowestTier;
                                    $benefitParts = [];
                                    if ($benefitTier) {
                                        $percent = (int) $benefitTier->get('discount_percent', $benefitTier->get('discount_percentual', 0));
                                        $amount = (int) $benefitTier->get('discount_amount', $benefitTier->get('discount', 0));
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
                                        $conditionParts[] = 'Đơn từ' . number_format($minAmountTarget, 0, ',', '.') . ' VND';
                                    }
                                    if ($minQtyTarget > 0) {
                                        $conditionParts[] = 'Số lượng từ ' . $minQtyTarget;
                                    }

                                    $needsMessages = [];
                                    if ($needAmount > 0) {
                                        $needsMessages[] = 'Cần mua thêm ' . number_format($needAmount, 0, ',', '.') . ' VND';
                                    }
                                    if ($needQty > 0) {
                                        $needsMessages[] = 'Cần thêm ' . $needQty . ' sản phẩm';
                                    }
                                    $needsMessage = implode(' và ', $needsMessages);

                                    $promotionName = $promotionModel->get('title') ?? $promotionModel->get('promo_id');
                                    $checkboxChecked = $isApplied || ($isSelected && ! $isManuallyDisabled);
                                    $checkboxDisabled = $isManuallyDisabled || (! $isEligible && ! $isApplied);
                                    $rowClasses = 'promo-option border rounded-3 p-3 mb-2 d-flex gap-3 align-items-start';
                                    if ($isApplied) {
                                        $rowClasses .= ' promo-option--applied';
                                    } elseif ($isSelected) {
                                        $rowClasses .= ' promo-option--selected';
                                    }
                                    if ($isDisabled) {
                                        $rowClasses .= ' promo-option--disabled';
                                    }
                                    if ($isManuallyDisabled) {
                                        $rowClasses .= ' promo-option--muted';
                                    }
                                @endphp

                                <div class="{{ $rowClasses }}">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox"
                                            @checked($checkboxChecked)
                                            @disabled($checkboxDisabled)>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap justify-content-between gap-2">
                                            <div>
                                                <div class="fw-semibold">{{ $promotionName }}</div>
                                                <div class="text-muted small">
                                                    {{ implode(' · ', array_filter($benefitParts)) }}
                                                    @if (!empty($conditionParts))
                                                        · {{ implode(' · ', array_filter($conditionParts)) }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                @if ($isApplied)
                                                    <span class="badge bg-success-subtle text-success">Đã áp dụng</span>
                                                @elseif ($isSelected && ! $isApplied)
                                                    <span class="badge bg-warning-subtle text-warning">Đang chờ</span>
                                                @elseif ($isManuallyDisabled)
                                                    <span class="badge bg-secondary-subtle text-secondary">Đang tắt</span>
                                                @endif
                                            </div>
                                        </div>

                                        @if (!empty($needsMessage) && ! $isApplied)
                                            <div class="text-danger small mt-2">
                                                {{ $needsMessage }} để áp dụng.
                                            </div>
                                        @endif
                                    </div>
                                    <div class="promo-option__action text-end">
                                        @if ($isApplied || ($isSelected && ! $isManuallyDisabled))
                                            <form action="{{ route('client.cart.promo.remove') }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="promotion_id" value="{{ $primaryIdentifier }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Bỏ chọn</button>
                                            </form>
                                        @elseif ($isManuallyDisabled)
                                            <form action="{{ route('client.cart.promo.enable') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="promotion_id" value="{{ $primaryIdentifier }}">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Sử dụng</button>
                                            </form>
                                        @elseif ($isEligible)
                                            <form action="{{ route('client.cart.promo.apply') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="promotion_code" value="{{ $primaryIdentifier }}">
                                                <button type="submit" class="btn btn-sm btn-outline-success">Chọn</button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Chưa đủ</button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">Chưa có chương trình khuyến mãi khả dụng.</p>
                            @endforelse
                        </div>
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
                            @if ($appliedPromotions->isNotEmpty())
                                @foreach ($appliedPromotions as $applied)
                                    @php
                                        $promoLabel = $applied['promotion']['title'] ?? $applied['promotion']['promo_id'] ?? 'Khuyen mai';
                                        $promoDiscountAmount = (int) ($applied['discount'] ?? 0);
                                        $promoShippingDiscount = (int) ($applied['shipping_discount'] ?? 0);
                                    @endphp
                                    @if ($promoDiscountAmount > 0)
                                        <li class="cart-summary-row small text-success">
                                            <span>{{ $promoLabel }}</span>
                                            <strong>-{{ number_format($promoDiscountAmount, 0, ',', '.') }} VND</strong>
                                        </li>
                                    @endif
                                    @if ($promoShippingDiscount > 0)
                                        <li class="cart-summary-row small text-success">
                                            <span>{{ $promoLabel }} (giam phi giao hang)</span>
                                            <strong>-{{ number_format($promoShippingDiscount, 0, ',', '.') }} VND</strong>
                                        </li>
                                    @endif
                                @endforeach
                            @endif
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

@push('styles')
    <style>
        .promo-option-list {
            display: grid;
            gap: 12px;
        }

        .promo-option {
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .promo-option--applied {
            border-color: rgba(25, 135, 84, 0.35);
            background-color: rgba(25, 135, 84, 0.06);
        }

        .promo-option--selected:not(.promo-option--applied) {
            border-color: rgba(255, 193, 7, 0.35);
            background-color: rgba(255, 193, 7, 0.04);
        }

        .promo-option--disabled {
            opacity: 0.7;
        }

        .promo-option--muted {
            opacity: 0.55;
        }

        .promo-option__action form {
            margin: 0;
        }

        .promo-option__action .btn {
            white-space: nowrap;
        }
    </style>
@endpush
