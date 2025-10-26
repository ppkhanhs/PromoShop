<nav class="nav flex-column p-3">
    <a href="{{ route('admin.dashboard') }}"
       class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.dashboard') ? 'active fw-semibold text-primary' : 'text-dark' }}">
        <i class="fa-solid fa-chart-pie"></i>
        Trang tổng quan
    </a>
    <a href="{{ route('admin.promotions.index') }}"
       class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.promotions.*') ? 'active fw-semibold text-primary' : 'text-dark' }}">
        <i class="fa-solid fa-gift"></i>
        Quản lý khuyến mãi
    </a>
    <a href="#"
       class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.promotions.*') ? 'active fw-semibold text-primary' : 'text-dark' }}">
        <i class="fa-solid fa-ticket"></i>
        Quản lý mã giảm giá
    </a>
    <a href="#"
       class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.promotions.*') ? 'active fw-semibold text-primary' : 'text-dark' }}">
        <i class="fa-solid fa-ticket"></i>
        Điều kiện khuyến mãi
    </a>
    <a href="{{ route('admin.products.index') }}"
       class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.products.*') ? 'active fw-semibold text-primary' : 'text-dark' }}">
        <i class="fa-solid fa-box"></i>
        Quản lý sản phẩm
    </a>
    <a href="{{ route('admin.users.index') }}"
       class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.users.*') ? 'active fw-semibold text-primary' : 'text-dark' }}">
        <i class="fa-solid fa-user-group"></i>
        Quản lý người dùng
    </a>
</nav>
