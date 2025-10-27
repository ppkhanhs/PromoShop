@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - ' . ($promotion ? 'Chỉnh sửa mã giảm giá' : 'Thêm mã giảm giá'))

@php
    $code = old('code', $promotion?->get('promo_id'));
    $title = old('title', $promotion?->get('title'));
    $description = old('description', $promotion?->get('description'));
    $minOrder = old('min_order', $promotion?->get('min_order') ?? $tier?->get('min_value'));
    $maxDiscount = old('max_discount', $promotion?->get('max_discount_amount'));
    $startDate = old('start_date', $promotion?->get('start_date'));
    $endDate = old('end_date', $promotion?->get('end_date'));
    $status = old('status', $promotion?->get('status') ?? 'active');
    $stackable = old('stackable', $promotion?->get('stackable', false));
    $freeship = old('freeship', $tier?->get('freeship', false));
    $tierLabel = old('tier_label', $tier?->get('label'));
    $discountPercent = old('discount_percent', $tier?->get('discount_percent'));
    $discountAmount = old('discount_amount', $tier?->get('discount_amount'));
    $discountType = old('discount_type', $discountPercent ? 'percent' : 'amount');
    $discountValue = old('discount_value', $discountType === 'percent' ? $discountPercent : $discountAmount);
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">
                {{ $promotion ? 'Chỉnh sửa mã giảm giá' : 'Thêm mã giảm giá mới' }}
            </h1>
            <p class="text-muted mb-0">
                Định nghĩa mã nhập tay với mức ưu đãi và điều kiện áp dụng rõ ràng.
            </p>
        </div>
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Quay lại danh sách
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Thông tin chung</h2>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="{{ $promotion ? route('admin.coupons.update', $promotion->get('promo_id')) : route('admin.coupons.store') }}"
                          class="row g-3">
                        @csrf
                        @if($promotion)
                            @method('PUT')
                        @endif

                        <div class="col-md-5">
                            <label for="code" class="form-label">Mã coupon</label>
                            <input type="text"
                                   id="code"
                                   name="code"
                                   class="form-control @error('code') is-invalid @enderror"
                                   value="{{ $code }}"
                                   {{ $promotion ? 'readonly' : 'required' }}>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Mã sẽ tự động chuyển về chữ in hoa.</div>
                        </div>
                        <div class="col-md-7">
                            <label for="title" class="form-label">Tên ưu đãi</label>
                            <input type="text"
                                   id="title"
                                   name="title"
                                   required
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ $title }}">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea id="description"
                                      name="description"
                                      rows="2"
                                      class="form-control @error('description') is-invalid @enderror">{{ $description }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Giá trị ưu đãi</label>
                            <div class="row g-2">
                                <div class="col-sm-4">
                                    <select name="discount_type"
                                            class="form-select @error('discount_type') is-invalid @enderror">
                                        <option value="percent" @selected($discountType === 'percent')>Theo %</option>
                                        <option value="amount" @selected($discountType === 'amount')>Theo số tiền</option>
                                    </select>
                                    @error('discount_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-8">
                                    <input type="number"
                                           name="discount_value"
                                           min="0"
                                           step="1"
                                           class="form-control @error('discount_value') is-invalid @enderror"
                                           value="{{ $discountValue }}"
                                           required>
                                    @error('discount_value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <span class="text-muted">Ví dụ: 10 (tương đương 10% hoặc 10.000 ₫).</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="min_order" class="form-label">Giá trị đơn tối thiểu (₫)</label>
                            <input type="number"
                                   id="min_order"
                                   name="min_order"
                                   min="0"
                                   step="1000"
                                   class="form-control @error('min_order') is-invalid @enderror"
                                   value="{{ $minOrder }}">
                            @error('min_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="max_discount" class="form-label">Mức giảm tối đa (₫)</label>
                            <input type="number"
                                   id="max_discount"
                                   name="max_discount"
                                   min="0"
                                   step="1000"
                                   class="form-control @error('max_discount') is-invalid @enderror"
                                   value="{{ $maxDiscount }}">
                            @error('max_discount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Để trống nếu không giới hạn.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Ngày bắt đầu</label>
                            <input type="date"
                                   id="start_date"
                                   name="start_date"
                                   class="form-control @error('start_date') is-invalid @enderror"
                                   value="{{ $startDate }}">
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">Ngày kết thúc</label>
                            <input type="date"
                                   id="end_date"
                                   name="end_date"
                                   class="form-control @error('end_date') is-invalid @enderror"
                                   value="{{ $endDate }}">
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">Trạng thái</label>
                            <select id="status"
                                    name="status"
                                    class="form-select @error('status') is-invalid @enderror">
                                @foreach (['active' => 'Đang hoạt động', 'scheduled' => 'Lên lịch', 'inactive' => 'Tạm dừng', 'draft' => 'Nháp'] as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch me-4">
                                <input class="form-check-input" type="checkbox" role="switch" id="stackable" name="stackable"
                                       value="1" {{ $stackable ? 'checked' : '' }}>
                                <label class="form-check-label" for="stackable">Cho phép cộng dồn</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="freeship" name="freeship"
                                       value="1" {{ $freeship ? 'checked' : '' }}>
                                <label class="form-check-label" for="freeship">Freeship</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="tier_label" class="form-label">Thông điệp hiển thị</label>
                            <input type="text"
                                   id="tier_label"
                                   name="tier_label"
                                   class="form-control @error('tier_label') is-invalid @enderror"
                                   value="{{ $tierLabel ?? ('Ưu đãi mã ' . ($code ?? '')) }}">
                            @error('tier_label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Hiển thị trong phần giỏ hàng / lịch sử khuyến mãi.</div>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">
                                Huỷ
                            </a>
                            <button type="submit" class="btn btn-primary">
                                {{ $promotion ? 'Cập nhật' : 'Tạo mã giảm giá' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Gợi ý thiết lập</h2>
                </div>
                <div class="card-body">
                    <ul class="mb-0 text-muted">
                        <li>Mã giảm giá luôn yêu cầu người dùng nhập tay, vì vậy hãy mô tả rõ trong phần mô tả.</li>
                        <li>Sử dụng “Cho phép cộng dồn” khi bạn muốn mã có thể đi cùng các chương trình tự động.</li>
                        <li>Freeship sẽ trừ toàn bộ phí giao hàng hiện tại của đơn hàng.</li>
                        <li>Đặt “Mức giảm tối đa” cho mã giảm theo % nhằm tránh giảm quá sâu.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
