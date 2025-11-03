@extends('layouts.admin.master')

@section('title', 'PromoShop Admin - Quản lý khuyến mãi')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Khuyến mãi</h1>
        <a href="{{ route('admin.promotions.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Thêm khuyến mãi
        </a>
    </div>

    @php
        if (!function_exists('formatCassandraDate')) {
            function formatCassandraDate($value) {
                if (empty($value)) return '---';
                try {
                    if (is_object($value)) {
                        if (method_exists($value, 'toDateTime')) {
                            return \Carbon\Carbon::instance($value->toDateTime())->format('d/m/Y');
                        }
                        return (string)$value;
                    }
                    if (is_numeric($value)) {
                        $val = (int)$value;
                        if ($val < 1000000) {
                            return \Carbon\Carbon::createFromTimestamp($val * 86400)->format('d/m/Y');
                        }
                        return \Carbon\Carbon::createFromTimestamp($val)->format('d/m/Y');
                    }
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                        return \Carbon\Carbon::parse($value)->format('d/m/Y');
                    }
                    return $value;
                } catch (\Exception $e) {
                    return 'Không xác định';
                }
            }
        }
    @endphp

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên chương trình</th>
                        <th>Thời gian</th>
                        <th>Loại</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Tầng</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promotions as $promotion)
                        @php
                            $start = formatCassandraDate($promotion->get('start_date'));
                            $end = formatCassandraDate($promotion->get('end_date'));
                            $status = $promotion->get('status');
                        @endphp
                        <tr>
                            <td>{{ $promotion->get('promo_id') }}</td>
                            <td>{{ $promotion->get('title') }}</td>
                            <td>
                                {{ $start }}<br>
                                <span class="text-muted small">{{ $end }}</span>
                            </td>
                            <td>{{ ucfirst($promotion->get('type', 'tiered')) }}</td>
                            <td>
                                <span class="badge text-bg-{{ $status === 'active' ? 'success' : ($status === 'scheduled' ? 'warning' : 'secondary') }}">
                                    {{ strtoupper($status ?? 'INACTIVE') }}
                                </span>
                            </td>
                            <td class="text-end">{{ count($promotion->tiers()) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.promotions.edit', $promotion->get('promo_id')) }}" class="btn btn-sm btn-outline-primary">Chỉnh sửa</a>
                                <form action="{{ route('admin.promotions.destroy', $promotion->get('promo_id')) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Bạn chắc chắn muốn xóa khuyến mãi này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Chưa có khuyến mãi nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
