@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Mã giảm giá')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Mã giảm giá</h1>
            <p class="text-muted mb-0">Quản lý các mã nhập tay cùng điều kiện áp dụng.</p>
        </div>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-2"></i>Thêm mã giảm giá
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã</th>
                        <th>Tên ưu đãi</th>
                        <th class="text-center">Giá trị</th>
                        <th>Điều kiện</th>
                        <th>Thời gian</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($coupons as $row)
                        @php
                            /** @var \App\Models\Cassandra\Promotion $promotion */
                            $promotion = $row['promotion'];
                            /** @var \App\Models\Cassandra\PromotionTier|null $tier */
                            $tier = $row['tier'];
                            $discountPercent = $tier?->get('discount_percent');
                            $discountAmount = $tier?->get('discount_amount');
                            $discountLabel = $discountPercent
                                ? $discountPercent . '%'
                                : ($discountAmount ? number_format($discountAmount, 0, ',', '.') . ' ₫' : '---');
                            $minOrder = (int) $promotion->get('min_order', $tier?->get('min_value', 0));
                            $status = $promotion->get('status', 'draft');
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $promotion->get('promo_id') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $promotion->get('title') }}</div>
                                <div class="text-muted small">{{ $promotion->get('description') ?? 'Không có mô tả' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge text-bg-primary">{{ $discountLabel }}</span>
                                @if ($tier?->get('freeship'))
                                    <div class="small text-success mt-1">+ Freeship</div>
                                @endif
                            </td>
                            <td>
                                @if ($minOrder > 0)
                                    <div>Tối thiểu: <strong>{{ number_format($minOrder, 0, ',', '.') }} ₫</strong></div>
                                @else
                                    <div>Không yêu cầu giá trị đơn</div>
                                @endif
                                <div>Cho phép cộng dồn: <strong>{{ $promotion->get('stackable') ? 'Có' : 'Không' }}</strong></div>
                            </td>
                            <td>
                                <div>Bắt đầu: <strong>{{ $promotion->get('start_date') ?? 'Không giới hạn' }}</strong></div>
                                <div>Kết thúc: <strong>{{ $promotion->get('end_date') ?? 'Không giới hạn' }}</strong></div>
                            </td>
                            <td class="text-center">
                                <span class="badge text-bg-{{ $status === 'active' ? 'success' : ($status === 'draft' ? 'secondary' : 'warning') }}">
                                    {{ strtoupper($status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.coupons.edit', $promotion->get('promo_id')) }}" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('admin.coupons.destroy', $promotion->get('promo_id')) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Bạn chắc chắn muốn xoá mã giảm giá này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Chưa có mã giảm giá nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
