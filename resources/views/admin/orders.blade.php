@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Đơn hàng')

@section('content')
    <section class="admin-section active">
        <h3><i class="fa-solid fa-receipt"></i>Quản lý đơn hàng</h3>
        <div class="admin-card">
            <div class="admin-toolbar">
                <div>
                    <label for="orderPromoFilter">Khuyến mãi</label>
                    <select id="orderPromoFilter">
                        <option value="">Tất cả</option>
                    </select>
                </div>
                <div>
                    <label for="orderCustomerFilter">Số điện thoại</label>
                    <input id="orderCustomerFilter" type="text" placeholder="Ví dụ: 0901234567" />
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <button id="orderFilterBtn" class="btn-primary btn-small">
                        <i class="fa-solid fa-filter"></i>Lọc
                    </button>
                    <button id="orderClearBtn" class="btn-outline btn-small">Làm mới</button>
                </div>
            </div>
        </div>
        <div class="table-wrapper" style="margin-top:20px;">
            <table>
                <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Số điện thoại</th>
                    <th>Họ tên</th>
                    <th>Khuyến mãi</th>
                    <th>Tổng tiền</th>
                    <th>Giảm</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái KM</th>
                    <th>Ngày đặt</th>
                </tr>
                </thead>
                <tbody id="ordersTableBody"></tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('admin/js/pages/orders-page.js') }}"></script>
@endpush
