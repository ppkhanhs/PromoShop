@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Tài khoản')

@section('content')
    <section class="admin-section active">
        <h3><i class="fa-solid fa-user-check"></i>Quản lý tài khoản</h3>
        <div class="admin-card">
            <form id="accountForm" class="form-grid">
                <div class="form-grid two">
                    <div>
                        <label for="account_phone">Số điện thoại</label>
                        <input id="account_phone" type="text" placeholder="Ví dụ: 0987654321" required />
                    </div>
                    <div>
                        <label for="account_name">Họ tên</label>
                        <input id="account_name" type="text" placeholder="Họ tên khách hàng" />
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i>Lưu</button>
                    <button type="button" id="accountResetBtn" class="btn-outline">Làm mới</button>
                    <button type="button" id="accountDeleteBtn" class="btn-danger hidden">Xóa</button>
                </div>
            </form>
        </div>

        <div class="admin-card" style="margin-top:20px;">
            <div class="admin-toolbar">
                <div>
                    <label for="accountSearchInput">Tìm số điện thoại hoặc tên</label>
                    <input id="accountSearchInput" type="text" placeholder="Nhập từ khóa" />
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <button id="accountSearchBtn" class="btn-primary btn-small">
                        <i class="fa-solid fa-magnifying-glass"></i>Lọc
                    </button>
                    <button id="accountSearchClearBtn" class="btn-outline btn-small">Làm mới</button>
                </div>
            </div>
        </div>

        <div class="table-wrapper" style="margin-top:20px;">
            <table>
                <thead>
                <tr>
                    <th>Số điện thoại</th>
                    <th>Họ tên</th>
                    <th class="text-end">Thao tác</th>
                </tr>
                </thead>
                <tbody id="accountsTableBody"></tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('admin/js/pages/accounts-page.js') }}"></script>
@endpush
