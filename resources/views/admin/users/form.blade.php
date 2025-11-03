@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - ' . ($user ? 'Chỉnh sửa người dùng' : 'Thêm người dùng'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">{{ $user ? 'Chỉnh sửa người dùng' : 'Thêm người dùng mới' }}</h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Quay lại danh sách</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST"
                  action="{{ $user ? route('admin.users.update', $user->get('user_id')) : route('admin.users.store') }}"
                  class="row g-3">
                @csrf
                @if ($user)
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label for="user_id" class="form-label">Mã người dùng</label>
                    <input type="text" id="user_id" name="user_id" class="form-control"
                           value="{{ old('user_id', $user?->get('user_id')) }}"
                           {{ $user ? 'readonly' : 'readonly required' }}>
                </div>

                <div class="col-md-6">
                    <label for="name" class="form-label">Họ và tên</label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name', $user?->get('name')) }}" required>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="{{ old('email', $user?->get('email')) }}" required>
                </div>

                <div class="col-md-6">
                    <label for="role" class="form-label">Vai trò</label>
                    @php
                        $role = old('role', $user?->get('role') ?? 'customer');
                    @endphp
                    <select id="role" name="role" class="form-select">
                        <option value="customer" @selected($role === 'customer')>Khách hàng</option>
                        <option value="admin" @selected($role === 'admin')>Quản trị</option>
                        <option value="staff" @selected($role === 'staff')>Nhân viên</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Mật khẩu {{ $user ? '(bỏ trống nếu giữ nguyên)' : '' }}</label>
                    <input type="password" id="password" name="password" class="form-control" {{ $user ? '' : 'required' }}>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        {{ $user ? 'Cập nhật người dùng' : 'Tạo người dùng' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if (!$user)
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const idField = document.getElementById("user_id");
                if (idField && !idField.value) {
                    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                    let code = "";
                    for (let i = 0; i < 5; i++) {
                        code += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    idField.value = code;
                }
            });
        </script>
    @endif
@endsection
