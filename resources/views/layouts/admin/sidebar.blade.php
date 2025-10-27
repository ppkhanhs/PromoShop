<nav class="nav flex-column p-3">
    <a href="{{ route('admin.dashboard') }}"
       class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.dashboard') ? 'active fw-semibold text-primary' : 'text-dark' }}">
        <i class="fa-solid fa-chart-pie"></i>
        Tổng quan
    </a>
    <a href="{{ route('admin.promotions.index') }}"
       class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.promotions.*') ? 'active fw-semibold text-primary' : 'text-dark' }}">
        <i class="fa-solid fa-gift"></i>
        Khuyến mãi
    </a>
    <a href="{{ route('admin.products.index') }}"
       class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.products.*') ? 'active fw-semibold text-primary' : 'text-dark' }}">
        <i class="fa-solid fa-box"></i>
        Sản phẩm
    </a>
    <a href="{{ route('admin.users.index') }}"
       class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.users.*') ? 'active fw-semibold text-primary' : 'text-dark' }}">
        <i class="fa-solid fa-user-group"></i>
        Người dùng
    </a>
</nav>
