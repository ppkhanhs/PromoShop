@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - ' . ($promotion ? 'Chỉnh sửa khuyến mãi' : 'Tạo khuyến mãi'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">
            {{ $promotion ? 'Chỉnh sửa khuyến mãi: ' . $promotion->get('promo_id') : 'Thêm khuyến mãi mới' }}
        </h1>
        <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">Quay lại danh sách</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Thông tin khuyến mãi</h2>
                </div>
                <div class="card-body">
                    <form method="POST"
                          action="{{ $promotion ? route('admin.promotions.update', $promotion->get('promo_id')) : route('admin.promotions.store') }}"
                          class="row g-3">
                        @csrf
                        @if ($promotion)
                            @method('PUT')
                        @endif
                        <div class="col-md-6">
                            <label for="promo_id" class="form-label">Mã khuyến mãi</label>
                            <input type="text" id="promo_id" name="promo_id" class="form-control"
                                   value="{{ old('promo_id', $promotion?->get('promo_id')) }}"
                                   {{ $promotion ? 'readonly' : 'required' }}>
                        </div>
                        <div class="col-md-6">
                            <label for="title" class="form-label">Tên chương trình</label>
                            <input type="text" id="title" name="title" class="form-control"
                                   value="{{ old('title', $promotion?->get('title')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Loại khuyến mãi</label>
                            <select id="type" name="type" class="form-select">
                                @php
                                    $type = old('type', $promotion?->get('type') ?? 'tiered');
                                @endphp
                                <option value="tiered" @selected($type === 'tiered')>Giảm theo tầng</option>
                                <option value="percent" @selected($type === 'percent')>Giảm phần trăm</option>
                                <option value="amount" @selected($type === 'amount')>Giảm tiền</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="reward_type" class="form-label">Hình thức thưởng</label>
                            <select id="reward_type" name="reward_type" class="form-select">
                                @php
                                    $reward = old('reward_type', $promotion?->get('reward_type') ?? 'discount');
                                @endphp
                                <option value="discount" @selected($reward === 'discount')>Giảm giá</option>
                                <option value="shipping" @selected($reward === 'shipping')>Freeship</option>
                                <option value="combo" @selected($reward === 'combo')>Tặng combo</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Ngày bắt đầu</label>
                            <input type="date" id="start_date" name="start_date" class="form-control"
                                   value="{{ old('start_date', $promotion?->get('start_date')) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">Ngày kết thúc</label>
                            <input type="date" id="end_date" name="end_date" class="form-control"
                                   value="{{ old('end_date', $promotion?->get('end_date')) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="min_order" class="form-label">Giá trị đơn tối thiểu</label>
                            <input type="number" id="min_order" name="min_order" class="form-control"
                                   value="{{ old('min_order', $promotion?->get('min_order')) }}" min="0" step="1000">
                        </div>
                        <div class="col-md-6">
                            <label for="max_discount_amount" class="form-label">Giảm tối đa</label>
                            <input type="number" id="max_discount_amount" name="max_discount_amount" class="form-control"
                                   value="{{ old('max_discount_amount', $promotion?->get('max_discount_amount')) }}" min="0" step="1000">
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Trạng thái</label>
                            <select id="status" name="status" class="form-select">
                                @php
                                    $status = old('status', $promotion?->get('status') ?? 'active');
                                @endphp
                                <option value="active" @selected($status === 'active')>Đang chạy</option>
                                <option value="draft" @selected($status === 'draft')>Nháp</option>
                                <option value="inactive" @selected($status === 'inactive')>Ngừng áp dụng</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" value="1" id="auto_apply" name="auto_apply"
                                       {{ old('auto_apply', $promotion?->get('auto_apply', true)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_apply">
                                    Tự động áp dụng
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" value="1" id="stackable" name="stackable"
                                       {{ old('stackable', $promotion?->get('stackable', false)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="stackable">
                                    Cho phép cộng dồn
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $promotion?->get('description')) }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                {{ $promotion ? 'Cập nhật khuyến mãi' : 'Tạo khuyến mãi' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @if ($promotion)
            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Tầng khuyến mãi</h2>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#newTierForm">
                            <i class="fa-solid fa-plus"></i> Thêm tầng
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="collapse mb-3" id="newTierForm">
                            <form action="{{ route('admin.promotions.tiers.store', $promotion->get('promo_id')) }}" method="POST" class="row g-2">
                                @csrf
                                <div class="col-4">
                                    <label class="form-label">Tầng</label>
                                    <input type="number" name="tier_level" class="form-control" value="{{ old('tier_level', count($tiers) + 1) }}" min="1" required>
                                </div>
                                <div class="col-8">
                                    <label class="form-label">Nhan đề</label>
                                    <input type="text" name="label" class="form-control" value="{{ old('label') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Đơn tối thiểu</label>
                                    <input type="number" name="min_value" class="form-control" value="{{ old('min_value', 0) }}" min="0" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">% giảm</label>
                                    <input type="number" name="discount_percent" class="form-control" value="{{ old('discount_percent') }}" min="0" max="100">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Giảm tiền (đ)</label>
                                    <input type="number" name="discount_amount" class="form-control" value="{{ old('discount_amount') }}" min="0" step="1000">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Quà tặng</label>
                                    <input type="text" name="gift_product_id" class="form-control" value="{{ old('gift_product_id') }}" placeholder="Mã sản phẩm tặng">
                                </div>
                                <div class="col-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="freeship" id="freeship_new" value="1" {{ old('freeship') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="freeship_new">Freeship</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Số lượng quà</label>
                                    <input type="number" name="gift_quantity" class="form-control" value="{{ old('gift_quantity') }}" min="1">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Mô tả combo</label>
                                    <textarea name="combo_description" class="form-control" rows="2">{{ old('combo_description') }}</textarea>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary btn-sm">Lưu tầng</button>
                                </div>
                            </form>
                        </div>

                        <div class="list-group">
                            @forelse ($tiers as $tier)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h3 class="h6 mb-1">{{ $tier->label() }}</h3>
                                            <p class="text-muted small mb-1">Đơn từ {{ number_format($tier->get('min_value'), 0, ',', '.') }} đ</p>
                                            <ul class="mb-2 text-muted small">
                                                @if ($tier->get('discount_percent'))
                                                    <li>Giảm {{ $tier->get('discount_percent') }}%</li>
                                                @endif
                                                @if ($tier->get('discount_amount'))
                                                    <li>Giảm thêm {{ number_format($tier->get('discount_amount'), 0, ',', '.') }} đ</li>
                                                @endif
                                                @if ($tier->get('freeship'))
                                                    <li>Freeship</li>
                                                @endif
                                                @if ($tier->get('gift_product_id'))
                                                    <li>Quà: {{ $tier->get('combo_description') ?? $tier->get('gift_product_id') }}</li>
                                                @endif
                                            </ul>
                                        </div>
                                        <div class="text-end">
                                            <a href="#" class="btn btn-sm btn-link" data-bs-toggle="collapse" data-bs-target="#tier-edit-{{ $tier->get('tier_level') }}">Sửa</a>
                                            <form action="{{ route('admin.promotions.tiers.destroy', [$promotion->get('promo_id'), $tier->get('tier_level')]) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Xóa tầng khuyến mãi này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-link text-danger">Xóa</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="collapse mt-3" id="tier-edit-{{ $tier->get('tier_level') }}">
                                        <form action="{{ route('admin.promotions.tiers.update', [$promotion->get('promo_id'), $tier->get('tier_level')]) }}"
                                              method="POST" class="row g-2">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-4">
                                                <label class="form-label">Tầng</label>
                                                <input type="number" name="tier_level" class="form-control" value="{{ $tier->get('tier_level') }}" readonly>
                                            </div>
                                            <div class="col-8">
                                                <label class="form-label">Nhan đề</label>
                                                <input type="text" name="label" class="form-control" value="{{ $tier->get('label') }}">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Đơn tối thiểu</label>
                                                <input type="number" name="min_value" class="form-control" value="{{ $tier->get('min_value') }}" min="0" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">% giảm</label>
                                                <input type="number" name="discount_percent" class="form-control" value="{{ $tier->get('discount_percent') }}" min="0" max="100">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Giảm tiền (đ)</label>
                                                <input type="number" name="discount_amount" class="form-control" value="{{ $tier->get('discount_amount') }}" min="0" step="1000">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Mã quà tặng</label>
                                                <input type="text" name="gift_product_id" class="form-control" value="{{ $tier->get('gift_product_id') }}">
                                            </div>
                                            <div class="col-6">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" name="freeship" id="freeship_{{ $tier->get('tier_level') }}" value="1" {{ $tier->get('freeship') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="freeship_{{ $tier->get('tier_level') }}">Freeship</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Số lượng quà</label>
                                                <input type="number" name="gift_quantity" class="form-control" value="{{ $tier->get('gift_quantity') }}" min="1">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Mô tả combo</label>
                                                <textarea name="combo_description" class="form-control" rows="2">{{ $tier->get('combo_description') }}</textarea>
                                            </div>
                                            <div class="col-12 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-primary btn-sm">Cập nhật tầng</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-muted text-center">
                                    Chưa có tầng khuyến mãi nào.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

