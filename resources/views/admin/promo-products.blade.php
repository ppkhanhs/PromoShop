@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Sản phẩm khuyến mãi')

@section('content')
    <section class="admin-section active">
        <h3><i class="fa-solid fa-hand-holding-heart"></i>Sản phẩm áp dụng khuyến mãi</h3>
        <div class="admin-card">
            <div class="admin-toolbar">
                <span class="muted">Lọc theo chương trình</span>
                <select id="promoProductFilter">
                    <option value="all">Tất cả chương trình</option>
                </select>
            </div>
            <form id="promoProductForm" class="form-grid">
                <div class="form-grid two">
                    <div>
                        <label for="promoProductPromoId">Khuyến mãi</label>
                        <select id="promoProductPromoId" required>
                            <option value="">Chọn khuyến mãi</option>
                        </select>
                    </div>
                    <div>
                        <label for="promoProductProductId">Sản phẩm</label>
                        <select id="promoProductProductId" required>
                            <option value="">Chọn sản phẩm</option>
                        </select>
                    </div>
                    <div>
                        <label for="promoProductPercent">Giảm %</label>
                        <input id="promoProductPercent" type="number" min="0" max="100" placeholder="Ví dụ: 10" />
                    </div>
                    <div>
                        <label for="promoProductAmount">Giảm tiền</label>
                        <input id="promoProductAmount" type="number" min="0" step="1000" placeholder="Ví dụ: 5000" />
                    </div>
                    <div>
                        <label for="promoProductGift">Tặng kèm</label>
                        <input id="promoProductGift" type="text" placeholder="Mã sản phẩm quà tặng" />
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i>Lưu áp dụng</button>
                    <button type="button" id="promoProductResetBtn" class="btn-outline">Làm mới</button>
                    <button type="button" id="promoProductDeleteBtn" class="btn-danger hidden">Xóa</button>
                </div>
            </form>
        </div>
        <div class="table-wrapper" style="margin-top:20px;">
            <table>
                <thead>
                <tr>
                    <th>Khuyến mãi</th>
                    <th>Sản phẩm</th>
                    <th>Giảm %</th>
                    <th>Giảm tiền</th>
                    <th>Tặng kèm</th>
                    <th class="text-end">Thao tác</th>
                </tr>
                </thead>
                <tbody id="promoProductTableBody"></tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('admin/js/pages/promo-products-page.js') }}"></script>
@endpush
