@extends('layouts.client.master')

@section('title', 'Đăng ký - PromoShop')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white text-center">
                    <h1 class="h4 mb-0">Tạo tài khoản mới</h1>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('register.submit') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label for="name" class="form-label">Họ và tên</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="col-12">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label for="password_confirmation" class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">Đăng ký</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center bg-white">
                    <span class="text-muted small">Đã có tài khoản?</span>
                    <a href="{{ route('login') }}" class="small">Đăng nhập</a>
                </div>
            </div>
        </div>
    </div>
@endsection

