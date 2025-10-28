@php
    $cartItems = session('cart.items', []);
    $cartCount = collect($cartItems)->sum('quantity') ?: count($cartItems);
    $user = auth()->user();
    $displayName = $user?->getAttribute('name') ?? $user?->getAuthIdentifier();
    $initials = $displayName ? collect(explode(' ', $displayName))->map(fn ($part) => mb_substr($part, 0, 1))->join('') : 'PS';
@endphp
<header class="site-header">
    <div class="site-header__inner">
        <a href="{{ route('client.home') }}" class="site-logo">
            <span class="site-logo__mark">PS</span>
            <div class="site-logo__text">
                <strong>Promo</strong>Shop
            </div>
        </a>

        <nav class="site-nav">
            <a class="{{ request()->routeIs('client.home') ? 'is-active' : '' }}" href="{{ route('client.home') }}">Trang chủ</a>
            @auth
                <a class="{{ request()->routeIs('client.orders') ? 'is-active' : '' }}" href="{{ route('client.orders') }}">Đơn hàng</a>
            @endauth
            <a class="{{ request()->routeIs('admin.*') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">Quản trị</a>
        </nav>

        <div class="site-actions">
            <a href="{{ route('client.cart') }}" class="site-cart">
                <span class="site-cart__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13l-1.2 6H19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9" cy="20" r="1" fill="currentColor"/>
                        <circle cx="17" cy="20" r="1" fill="currentColor"/>
                    </svg>
                </span>
                <span>Giỏ hàng</span>
                <span class="site-cart__count">{{ $cartCount }}</span>
            </a>

            @auth
                <div class="dropdown">
                    <button class="user-chip btn p-0 border-0" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="user-avatar">{{ $initials }}</span>
                        <span class="user-name text-truncate">{{ $displayName }}</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m6 10 6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end user-dropdown shadow-sm border-0 rounded-4 mt-2">
                        <span class="dropdown-item-text text-muted small">{{ $user?->getAttribute('email') }}</span>
                        <a class="dropdown-item" href="{{ route('client.orders') }}">Đơn hàng của tôi</a>
                        <a class="dropdown-item" href="{{ route('client.cart') }}">Giỏ hàng</a>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger fw-semibold">Đăng xuất</button>
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
