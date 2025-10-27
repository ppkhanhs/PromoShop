@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Điều kiện khuyến mãi')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Điều kiện khuyến mãi</h1>
            <p class="text-muted mb-0">Tra cứu nhanh các tiêu chí áp dụng của từng chương trình.</p>
        </div>
        <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-primary">
            <i class="fa-solid fa-gift me-2"></i>Quản lý khuyến mãi
        </a>
    </div>

    @if (empty($promotions))
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                Chưa có chương trình khuyến mãi nào.
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach ($promotions as $promotion)
                @php
                    /** @var \App\Models\Cassandra\Promotion $promotion */
                    $tiers = $promotion->tiers();
                    $minOrder = (int) $promotion->get('min_order', 0);
                    $autoApply = $promotion->get('auto_apply');
                    $stackable = $promotion->get('stackable');
                    $status = $promotion->get('status', 'inactive');
                @endphp
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <h2 class="h5 mb-1">{{ $promotion->get('title') }}</h2>
                                <div class="text-muted small">{{ $promotion->get('promo_id') }}</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge text-bg-{{ $status === 'active' ? 'success' : ($status === 'scheduled' ? 'warning' : 'secondary') }}">
                                    {{ strtoupper($status) }}
                                </span>
                                <span class="badge text-bg-light">
                                    Loại: {{ strtoupper($promotion->get('type', 'tiered')) }}
                                </span>
                                <span class="badge text-bg-light">
                                    Áp dụng: {{ $autoApply ? 'Tự động' : 'Nhập mã' }}
                                </span>
                                <span class="badge text-bg-light">
                                    Cộng dồn: {{ $stackable ? 'Có' : 'Không' }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100">
                                        <h3 class="h6 text-muted text-uppercase mb-2">Giới hạn thời gian</h3>
                                        <p class="mb-1">Bắt đầu: <strong>{{ $promotion->get('start_date') ?? 'Không giới hạn' }}</strong></p>
                                        <p class="mb-0">Kết thúc: <strong>{{ $promotion->get('end_date') ?? 'Không giới hạn' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100">
                                        <h3 class="h6 text-muted text-uppercase mb-2">Điều kiện chung</h3>
                                        <p class="mb-1">
                                            Giá trị tối thiểu:
                                            <strong>{{ $minOrder > 0 ? number_format($minOrder, 0, ',', '.') . ' ₫' : 'Không yêu cầu' }}</strong>
                                        </p>
                                        <p class="mb-1">Thưởng: <strong>{{ strtoupper($promotion->get('reward_type', 'discount')) }}</strong></p>
                                        @if ($promotion->get('max_discount_amount'))
                                            <p class="mb-0">
                                                Giảm tối đa: <strong>{{ number_format($promotion->get('max_discount_amount'), 0, ',', '.') }} ₫</strong>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 h-100">
                                        <h3 class="h6 text-muted text-uppercase mb-2">Mô tả</h3>
                                        <p class="mb-0">{{ $promotion->get('description') ?? 'Chưa có mô tả.' }}</p>
                                    </div>
                                </div>
                            </div>

                            @if (empty($tiers))
                                <div class="alert alert-warning mb-0">
                                    Chương trình chưa có tầng khuyến mãi nào. Vui lòng bổ sung để khuyến mãi hoạt động đúng.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" style="width: 120px;">Tầng</th>
                                                <th>Điều kiện</th>
                                                <th>Ưu đãi</th>
                                                <th>Ghi chú</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($tiers as $tier)
                                                @php
                                                    /** @var \App\Models\Cassandra\PromotionTier $tier */
                                                    $tierMin = (int) $tier->get('min_value', 0);
                                                    $percent = $tier->get('discount_percent');
                                                    $amount = $tier->get('discount_amount');
                                                    $gift = $tier->get('gift_product_id');
                                                @endphp
                                                <tr>
                                                    <td class="text-center fw-semibold align-middle">
                                                        {{ $tier->get('tier_level') }}
                                                    </td>
                                                    <td>
                                                        <div>Đơn tối thiểu: <strong>{{ number_format($tierMin, 0, ',', '.') }} ₫</strong></div>
                                                        @if ($tier->get('min_quantity'))
                                                            <div>Số lượng tối thiểu: <strong>{{ $tier->get('min_quantity') }}</strong></div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <ul class="mb-0">
                                                            @if ($percent)
                                                                <li>Giảm <strong>{{ $percent }} %</strong></li>
                                                            @endif
                                                            @if ($amount)
                                                                <li>Giảm <strong>{{ number_format($amount, 0, ',', '.') }} ₫</strong></li>
                                                            @endif
                                                            @if ($tier->get('freeship'))
                                                                <li>Miễn phí giao hàng</li>
                                                            @endif
                                                            @if ($gift)
                                                                <li>Tặng kèm sản phẩm: <strong>{{ $gift }}</strong></li>
                                                            @endif
                                                        </ul>
                                                    </td>
                                                    <td>
                                                        {{ $tier->get('label') ?? $tier->get('combo_description') ?? '---' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
