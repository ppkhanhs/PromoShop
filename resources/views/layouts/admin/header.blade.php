@php
    $admin = $adminAccount ?? auth()->user();
@endphp
<header class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="{{ route('admin.dashboard') }}">Ứng dụng Quản lý Khuyến Mãi</a>
        <div class="d-flex align-items-center gap-3">
            @if ($admin)
                <div class="text-white small text-end">
                    <div class="fw-semibold">{{ $admin->getAttribute('name') ?? $admin->getAuthIdentifier() }}</div>
                    <div class="text-white-50 text-uppercase">{{ $admin->getAttribute('role') ?? 'Administrator' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Đăng xuất</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Đăng nhập</a>
            @endif
        </div>
    </div>
</header>
