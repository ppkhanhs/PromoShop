@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Chi tiết đơn hàng')

@php
    use Carbon\Carbon;
    $items = $order->get('items', []);
    $createdAt = $order->get('created_at');
    try {
        $createdAtFormatted = $createdAt ? Carbon::parse($createdAt)->format('d/m/Y H:i') : '---';
    } catch (\Throwable $th) {
        $createdAtFormatted = '---';
    }
    $subtotal = collect($items)->sum(fn ($item) => (int) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? $item['qty'] ?? 1));
    $status = strtolower($order->get('status', 'pending'));
    $statusClasses = [
        'pending' => 'warning',
        'approved' => 'primary',
        'confirmed' => 'primary',
        'processing' => 'info',
        'shipped' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger',
    ];
    $badgeClass = $statusClasses[$status] ?? 'secondary';
    $statusLabelLookup = $statusLabels ?? [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'confirmed' => 'Đã xác nhận',
        'processing' => 'Đang xử lý',
        'shipped' => 'Đã gửi hàng',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];
    $statusLabel = $statusLabelLookup[$status] ?? strtoupper($status);
    $confirmedAt = $order->get('confirmed_at');
    try {
        $confirmedAtFormatted = $confirmedAt ? Carbon::parse($confirmedAt)->format('d/m/Y H:i') : null;
    } catch (\Throwable $th) {
        $confirmedAtFormatted = null;
    }
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Đơn hàng {{ $order->get('order_id') }}</h1>
            <p class="text-muted mb-0">Thông tin chi tiết và lịch sử ưu đãi của đơn.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Quay lại danh sách
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Thông tin khách hàng</h2>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Khách hàng</dt>
                        <dd class="col-7">{{ $order->get('customer_name') ?? 'Không cung cấp' }}</dd>

                        <dt class="col-5">Số điện thoại</dt>
                        <dd class="col-7">{{ $order->get('customer_phone') ?? '---' }}</dd>

                        <dt class="col-5">Tài khoản</dt>
                        <dd class="col-7">{{ $order->get('user_id') ?? 'Khách lẻ' }}</dd>

                        <dt class="col-5">Ngày tạo</dt>
                        <dd class="col-7">{{ $createdAtFormatted }}</dd>

                        <dt class="col-5">Trạng thái</dt>
                        <dd class="col-7">
                            <span class="badge text-bg-{{ $badgeClass }}">
                                {{ $statusLabel }}
                            </span>
                            @if ($confirmedAtFormatted)
                                <div class="text-muted small mt-1">Xác nhận: {{ $confirmedAtFormatted }}</div>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Tổng quan thanh toán</h2>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between mb-2">
                            <span>Tạm tính</span>
                            <strong>{{ number_format($subtotal, 0, ',', '.') }} ₫</strong>
                        </li>
                        <li class="d-flex justify-content-between mb-2 text-success">
                            <span>Giảm giá</span>
                            <strong>-{{ number_format($order->get('discount') ?? 0, 0, ',', '.') }} ₫</strong>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span>Phí giao hàng</span>
                            <strong>{{ number_format($order->get('shipping_fee') ?? 0, 0, ',', '.') }} ₫</strong>
                        </li>
                        <li class="d-flex justify-content-between border-top pt-2">
                            <span>Thành tiền</span>
                            <strong class="text-primary">{{ number_format($order->get('final_amount') ?? 0, 0, ',', '.') }} ₫</strong>
                        </li>
                    </ul>
                    <hr>
                    <dl class="row mb-0">
                        <dt class="col-5">Mã khuyến mãi</dt>
                        <dd class="col-7">{{ $order->get('promo_id') ?? 'Không áp dụng' }}</dd>

                        <dt class="col-5">Tầng áp dụng</dt>
                        <dd class="col-7">{{ $order->get('applied_tier') ? 'Tầng ' . $order->get('applied_tier') : '---' }}</dd>
                    </dl>
                </div>
            </div>

            @if (!in_array($status, ['completed', 'cancelled']))
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Xác nhận đơn hàng</h2>
                    </div>
                    <div class="card-body">
                        @if ($status === 'pending')
                            <form method="POST" action="{{ route('admin.orders.approve', $order->get('order_id')) }}" class="d-flex align-items-center gap-2 mb-3">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fa-solid fa-check me-1"></i>Duyệt nhanh
                                </button>
                                <span class="text-muted small">Đưa đơn sang trạng thái “Đã duyệt”.</span>
                            </form>
                        @endif
                        @php
                            $statusOptions = [
                                'approved' => 'Duyệt đơn (chờ xử lý)',
                                'confirmed' => 'Xác nhận đơn (đã liên hệ khách)',
                                'processing' => 'Đang xử lý / đóng gói',
                                'shipped' => 'Đã gửi hàng',
                                'completed' => 'Hoàn thành',
                                'cancelled' => 'Hủy đơn',
                            ];
                            $selectedStatus = old('status', $status === 'pending' ? 'approved' : $status);
                        @endphp
                        <form method="POST" action="{{ route('admin.orders.confirm', $order->get('order_id')) }}">
                            @csrf
                            <div class="mb-3">
                                <label for="status" class="form-label">Trạng thái mới</label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="admin_note" class="form-label">Ghi chú nội bộ</label>
                                <textarea id="admin_note" name="admin_note" rows="3" class="form-control @error('admin_note') is-invalid @enderror" placeholder="Ví dụ: Đã gọi xác nhận với khách lúc 10h">{{ old('admin_note', $order->get('admin_note')) }}</textarea>
                                @error('admin_note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Ghi chú này chỉ hiển thị trong trang quản trị.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check me-1"></i>Cập nhật trạng thái
                            </button>
                        </form>
                        @if ($confirmedAtFormatted)
                            <div class="text-muted small mt-3">Lần xác nhận gần nhất: {{ $confirmedAtFormatted }}</div>
                        @endif
                    </div>
                </div>
            @else
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Ghi chú xử lý</h2>
                    </div>
                    <div class="card-body">
                        @if ($order->get('admin_note'))
                            <p class="mb-0">{{ $order->get('admin_note') }}</p>
                        @else
                            <p class="text-muted mb-0">Không có ghi chú nội bộ.</p>
                        @endif
                        @if ($confirmedAtFormatted)
                            <div class="text-muted small mt-3">Đã xác nhận: {{ $confirmedAtFormatted }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Danh sách sản phẩm</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php
                                    $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 1);
                                    $price = (int) ($item['price'] ?? $item['unit_price'] ?? 0);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item['name'] ?? $item['product_id'] }}</div>
                                        <div class="text-muted small">Mã: {{ $item['product_id'] ?? '---' }}</div>
                                    </td>
                                    <td class="text-end">{{ number_format($price, 0, ',', '.') }} ₫</td>
                                    <td class="text-center">{{ $qty }}</td>
                                    <td class="text-end">{{ number_format($qty * $price, 0, ',', '.') }} ₫</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Đơn hàng không có sản phẩm.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Ghi chú &amp; giao hàng</h2>
                </div>
                <div class="card-body">
                    <dl class="row mb-3">
                        <dt class="col-3">Địa chỉ giao</dt>
                        <dd class="col-9">{{ $order->get('shipping_address') ?? '---' }}</dd>

                        <dt class="col-3">Ghi chú</dt>
                        <dd class="col-9">{{ $order->get('note') ?? 'Không có ghi chú.' }}</dd>
                    </dl>
                    <div class="text-muted small">
                        Sử dụng biểu mẫu <strong>Xác nhận đơn hàng</strong> ở cột bên để cập nhật trạng thái xử lý và ghi chú nội bộ.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
