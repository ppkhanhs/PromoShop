@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Gán khuyến mãi cho sản phẩm')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Gán khuyến mãi cho sản phẩm</h1>
            <p class="text-muted mb-0">Chọn sản phẩm và mã giảm giá tương ứng để hiển thị ưu đãi trực tiếp.</p>
        </div>
        <a href="{{ route('admin.product-promotions.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Quay lại danh sách
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.product-promotions.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label for="product_id" class="form-label">Sản phẩm</label>
                            <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">-- Chọn sản phẩm --</option>
                                @foreach ($products as $product)
                                    @php
                                        $id = strtoupper($product->product_id ?? $product->get('product_id'));
                                        $name = $product->name ?? $product->get('name') ?? $id;
                                        $category = $product->category ?? $product->get('category');
                                    @endphp
                                    <option value="{{ $id }}" @selected(old('product_id') === $id)>
                                        {{ $name }} ({{ $id }}){{ $category ? ' · '.$category : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="voucher_code" class="form-label">Mã khuyến mãi</label>
                            <select id="voucher_code" name="voucher_code" class="form-select @error('voucher_code') is-invalid @enderror" required>
                                <option value="">-- Chọn mã giảm giá --</option>
                                @foreach ($vouchers as $voucher)
                                    @php
                                        $code = strtoupper($voucher->get('code'));
                                        $label = $voucher->get('label') ?? $code;
                                        $promo = $voucher->get('promo_id');
                                        $type = strtolower($voucher->get('discount_type', 'amount'));
                                        $value = (float) $voucher->get('discount_value', 0);
                                        $display = $type === 'percent'
                                            ? $value . '%'
                                            : number_format($value, 0, ',', '.') . ' ₫';
                                    @endphp
                                    <option value="{{ $code }}" @selected(old('voucher_code') === $code)>
                                        {{ $label }} ({{ $code }}) — {{ $display }}{{ $promo ? ' · '.$promo : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('voucher_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-link me-2"></i>Gán khuyến mãi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 h-100 bg-light-subtle">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Gợi ý sử dụng</h2>
                    <ul class="mb-0 text-muted">
                        <li>Mỗi sản phẩm nên gán tối đa một mã chính để khách hàng dễ nhận biết ưu đãi.</li>
                        <li>Sau khi gán, giá ưu đãi sẽ hiển thị trực tiếp trên danh sách sản phẩm.</li>
                        <li>Có thể gỡ khuyến mãi bất kỳ lúc nào từ trang danh sách.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
