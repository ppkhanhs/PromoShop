@extends('layouts.client.master')

@section('title', 'Đăng nhập - PromoShop')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white text-center">
                    <h1 class="h4 mb-0">Đăng nhập</h1>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('login.submit') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                        </div>
                        <div class="col-12">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Ghi nhớ đăng nhập
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center bg-white">
                    <span class="text-muted small">Chưa có tài khoản?</span>
                    <a href="{{ route('register') }}" class="small">Đăng ký ngay</a>
                </div>
            </div>
        </div>
    </div>
@endsection

