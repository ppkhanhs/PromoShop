@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Mã giảm giá')

@section('content')
    <section class="admin-section active">
        <h3><i class="fa-solid fa-ticket"></i>Quản lý mã giảm giá</h3>
        <div class="admin-card">
            <form id="codeForm" class="form-grid">
                <div class="form-grid two">
                    <div>
                        <label for="codeInput">Mã code</label>
                        <input id="codeInput" type="text" maxlength="30" placeholder="Ví dụ: SALE10" required />
                    </div>
                    <div>
                        <label for="codePromoId">Khuyến mãi áp dụng</label>
                        <select id="codePromoId" required>
                            <option value="">Chọn khuyến mãi</option>
                        </select>
                    </div>
                    <div>
                        <label for="codeExpire">Ngày hết hạn</label>
                        <input id="codeExpire" type="date" required />
                    </div>
                    <div>
                        <label for="codeEnabled">Trạng thái</label>
                        <div class="toggle">
                            <input id="codeEnabled" type="checkbox" checked />
                            <label for="codeEnabled">Đang kích hoạt</label>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i>Lưu</button>
                    <button type="button" id="codeResetBtn" class="btn-outline">Làm mới</button>
                    <button type="button" id="codeDeleteBtn" class="btn-danger hidden">Xóa</button>
                </div>
            </form>
        </div>
        <div class="table-wrapper" style="margin-top:20px;">
            <table>
                <thead>
                <tr>
                    <th>Mã</th>
                    <th>Khuyến mãi</th>
                    <th>Hết hạn</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
                </thead>
                <tbody id="codesTableBody"></tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('admin/js/pages/codes-page.js') }}"></script>
@endpush
