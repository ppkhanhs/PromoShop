@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Sản phẩm áp dụng khuyến mãi')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Sản phẩm áp dụng khuyến mãi</h1>
            <p class="text-muted mb-0">Quản lý danh sách sản phẩm được gán mã giảm giá để hiển thị ưu đãi trực tiếp.</p>
        </div>
        <a href="{{ route('admin.product-promotions.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-2"></i>Gán khuyến mãi cho sản phẩm
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Mã khuyến mãi</th>
                        <th class="text-nowrap text-center">Giá trị</th>
                        <th class="text-nowrap">Thuộc chương trình</th>
                        <th class="text-nowrap text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            /** @var \App\Models\ProductVoucher $mapping */
                            $mapping = $item['mapping'];
                            $product = $item['product'];
                            $voucher = $item['voucher'];
                            $discountType = strtolower($mapping->discount_type);
                            $discountValue = (float) $mapping->discount_value;
                            $maxDiscount = $mapping->max_discount_amount !== null
                                ? number_format((float) $mapping->max_discount_amount, 0, ',', '.').' ₫'
                                : 'Không giới hạn';
                            $displayValue = $discountType === 'percent'
                                ? $discountValue . '%'
                                : number_format($discountValue, 0, ',', '.') . ' ₫';
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $product?->name ?? $product?->get('name') ?? $mapping->product_id }}
                                </div>
                                <div class="text-muted small">
                                    Mã: {{ $mapping->product_id }}
                                    @if ($product?->category ?? $product?->get('category'))
                                        · {{ $product?->category ?? $product?->get('category') }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $mapping->voucher_code }}</div>
                                <div class="text-muted small">
                                    {{ $voucher?->get('label') ?? $voucher?->get('description') ?? 'Không có mô tả' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge text-bg-primary">{{ $displayValue }}</span>
                                <div class="text-muted small">Giảm tối đa: {{ $maxDiscount }}</div>
                            </td>
                            <td class="text-muted small">
                                <div class="fw-semibold">
                                    {{ $voucher?->get('promo_id') ?? $mapping->promo_id ?? '—' }}
                                </div>
                                @if ($voucher && $voucher->get('description'))
                                    <div>{{ \Illuminate\Support\Str::limit($voucher->get('description'), 50) }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.product-promotions.destroy', $mapping) }}" method="POST"
                                      onsubmit="return confirm('Bạn chắc chắn muốn gỡ khuyến mãi khỏi sản phẩm này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-xmark me-1"></i>Gỡ khuyến mãi
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Chưa có sản phẩm nào được gán khuyến mãi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
