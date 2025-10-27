@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - ' . ($product ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ $product ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' }}</h1>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Quay lại danh sách</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST"
                  action="{{ $product ? route('admin.products.update', $product->product_id) : route('admin.products.store') }}"
                  class="row g-3">
                @csrf
                @if ($product)
                    @method('PUT')
                @endif
                <div class="col-md-6">
                    <label for="product_id" class="form-label">Mã sản phẩm</label>
                    <input type="text" id="product_id" name="product_id" class="form-control"
                           value="{{ old('product_id', $product->product_id ?? '') }}"
                           {{ $product ? 'readonly' : 'required' }}>
                </div>
                <div class="col-md-6">
                    <label for="name" class="form-label">Tên sản phẩm</label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name', $product->name ?? '') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="category" class="form-label">Danh mục</label>
                    <input type="text" id="category" name="category" class="form-control"
                           value="{{ old('category', $product->category ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label for="price" class="form-label">Giá bán (đ)</label>
                    <input type="number" id="price" name="price" class="form-control"
                           value="{{ old('price', $product->price ?? 0) }}" min="0" step="1000" required>
                </div>
                <div class="col-md-6">
                    <label for="stock" class="form-label">Tồn kho</label>
                    <input type="number" id="stock" name="stock" class="form-control"
                           value="{{ old('stock', $product->stock ?? 0) }}" min="0">
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label">Trạng thái</label>
                    @php
                        $status = old('status', $product->status ?? 'active');
                    @endphp
                    <select id="status" name="status" class="form-select">
                        <option value="active" @selected($status === 'active')>Đang bán</option>
                        <option value="inactive" @selected($status === 'inactive')>Tạm dừng</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="image_url" class="form-label">Ảnh sản phẩm (URL)</label>
                    <input type="url" id="image_url" name="image_url" class="form-control"
                           value="{{ old('image_url', $product->image_url ?? '') }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        {{ $product ? 'Cập nhật sản phẩm' : 'Tạo sản phẩm' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

