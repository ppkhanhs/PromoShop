@php
    $cartItems = session('cart.items', []);
    $cartCount = collect($cartItems)->sum('quantity') ?: count($cartItems);
@endphp
<header class="border-bottom bg-white shadow-sm">
    <div class="container py-3 d-flex align-items-center justify-content-between">
        <a href="{{ route('client.home') }}" class="navbar-brand fw-bold text-primary">PromoShop</a>
        <nav class="nav gap-3">
            <a class="nav-link {{ request()->routeIs('client.home') ? 'active fw-semibold' : '' }}" href="{{ route('client.home') }}">Trang chủ</a>
            @auth
                <a class="nav-link {{ request()->routeIs('client.orders') ? 'active fw-semibold' : '' }}" href="{{ route('client.orders') }}">Đơn hàng</a>
            @endauth
            <a class="nav-link" href="{{ route('admin.dashboard') }}">Quản trị</a>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('client.cart') }}" class="btn btn-outline-primary position-relative">
                Giỏ hàng
                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                    {{ $cartCount }}
                </span>
            </a>
            @auth
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle text-decoration-none" data-bs-toggle="dropdown">
                        {{ auth()->user()->getAttribute('name') ?? auth()->user()->getAuthIdentifier() }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <span class="dropdown-item-text small text-muted">{{ auth()->user()->getAttribute('email') }}</span>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">Đăng xuất</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-secondary">Đăng nhập</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Đăng ký</a>
            @endauth
        </div>
    </div>
</header>
