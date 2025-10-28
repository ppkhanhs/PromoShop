<footer class="site-footer">
    <div class="site-footer__inner">
        <div>
            <strong>PromoShop</strong>
            <p class="text-muted mb-0 small">Nền tảng khuyến mãi đa tầng kết nối dữ liệu Cassandra.</p>
        </div>
        <div class="site-footer__links">
            <a href="{{ route('client.home') }}">Trang chủ</a>
            <a href="{{ route('client.cart') }}">Giỏ hàng</a>
            <a href="{{ route('client.orders') }}">Đơn hàng</a>
            <a href="{{ route('admin.dashboard') }}">Quản trị</a>
        </div>
        <span class="text-muted small">
            &copy; {{ date('Y') }} PromoShop. Tất cả quyền được bảo lưu.
        </span>
    </div>
</footer>
