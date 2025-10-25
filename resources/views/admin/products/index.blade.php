@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Sản phẩm')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Sản phẩm</h1>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Thêm sản phẩm
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th class="text-end">Giá bán</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $product->product_id }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category ?? '---' }}</td>
                            <td class="text-end">{{ number_format($product->price, 0, ',', '.') }} đ</td>
                            <td>
                                <span class="badge text-bg-{{ ($product->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">
                                    {{ ($product->status ?? 'active') === 'active' ? 'Đang bán' : 'Tạm dừng' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.products.edit', $product->product_id) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                                <form action="{{ route('admin.products.destroy', $product->product_id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Xóa sản phẩm này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Chưa có sản phẩm nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

