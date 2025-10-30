<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cassandra\Order;
use App\Services\CassandraDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const ADMIN_ORDER_STATUSES = [
        'pending',
        'approved',
        'confirmed',
        'processing',
        'shipped',
        'completed',
        'cancelled',
    ];

    private const STATUS_LABELS = [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'confirmed' => 'Đã xác nhận',
        'processing' => 'Đang xử lý',
        'shipped' => 'Đã gửi hàng',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];

    private const STATUS_BADGES = [
        'pending' => 'warning',
        'approved' => 'primary',
        'confirmed' => 'primary',
        'processing' => 'info',
        'shipped' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger',
    ];

    public function __construct(protected CassandraDataService $dataService)
    {
    }

    public function index(Request $request): View
    {
        $orders = collect($this->dataService->fetchOrders());

        $statusFilter = strtolower(trim((string) $request->input('status', '')));
        $keywordFilter = strtolower(trim((string) $request->input('keyword', '')));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $fromTimestamp = $dateFrom ? strtotime($dateFrom . ' 00:00:00') : null;
        $toTimestamp = $dateTo ? strtotime($dateTo . ' 23:59:59') : null;

        $filtered = $orders->filter(function (Order $order) use ($statusFilter, $keywordFilter, $fromTimestamp, $toTimestamp) {
            $orderStatus = strtolower((string) ($order->get('status') ?? ''));
            if ($statusFilter !== '' && $orderStatus !== $statusFilter) {
                return false;
            }

            $createdAt = $order->get('created_at') ?? $order->get('order_date');
            $orderTimestamp = $createdAt ? strtotime((string) $createdAt) : null;
            if ($fromTimestamp && $orderTimestamp && $orderTimestamp < $fromTimestamp) {
                return false;
            }
            if ($toTimestamp && $orderTimestamp && $orderTimestamp > $toTimestamp) {
                return false;
            }

            if ($keywordFilter !== '') {
                $haystack = strtolower(trim(sprintf(
                    '%s %s %s %s',
                    (string) ($order->get('order_id') ?? ''),
                    (string) ($order->get('order_code') ?? ''),
                    (string) ($order->get('customer_name') ?? ''),
                    (string) ($order->get('customer_phone') ?? '')
                )));

                if (!str_contains($haystack, $keywordFilter)) {
                    return false;
                }
            }

            return true;
        })->values();

        $summary = [
            'total' => $filtered->count(),
            'revenue' => $filtered->sum(fn (Order $order) => (int) $order->get('final_amount', 0)),
            'discount' => $filtered->sum(fn (Order $order) => (int) $order->get('discount', 0)),
            'shipping' => $filtered->sum(fn (Order $order) => (int) $order->get('shipping_fee', 0)),
        ];

        $promotionStats = $filtered
            ->groupBy(fn (Order $order) => $order->get('promo_id') ?: 'Không áp dụng')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'discount' => $group->sum(fn (Order $order) => (int) $order->get('discount', 0)),
                ];
            })
            ->sortByDesc('count')
            ->take(5);

        return view('admin.orders.index', [
            'orders' => $filtered,
            'summary' => $summary,
            'promotionStats' => $promotionStats,
            'statusLabels' => self::STATUS_LABELS,
            'statusBadges' => self::STATUS_BADGES,
            'filters' => [
                'status' => $statusFilter,
                'keyword' => $keywordFilter,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function show(string $order): View
    {
        $record = collect($this->dataService->fetchOrders())
            ->first(function (Order $item) use ($order) {
                return strtoupper($item->get('order_id')) === strtoupper($order);
            });

        if (!$record) {
            abort(404);
        }

        return view('admin.orders.show', [
            'order' => $record,
            'statusLabels' => self::STATUS_LABELS,
            'statusBadges' => self::STATUS_BADGES,
        ]);
    }

    public function confirm(Request $request, string $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', Rule::in(self::ADMIN_ORDER_STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $status = strtolower($data['status'] ?? 'confirmed');
        $payload = [
            'status' => $status,
        ];

        if (array_key_exists('admin_note', $data)) {
            $payload['admin_note'] = $data['admin_note'] !== null
                ? trim((string) $data['admin_note'])
                : null;
        }

        if (!$this->dataService->confirmOrder($order, $payload)) {
            return back()
                ->withInput()
                ->with('error', 'Không thể cập nhật trạng thái đơn hàng. Vui lòng thử lại.');
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }

    public function approve(Request $request, string $order): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = ['status' => 'approved'];
        if (array_key_exists('admin_note', $data)) {
            $note = $data['admin_note'] !== null ? trim((string) $data['admin_note']) : null;
            if ($note !== null && $note !== '') {
                $payload['admin_note'] = $note;
            }
        }

        if (!$this->dataService->confirmOrder($order, $payload)) {
            return back()
                ->withInput()
                ->with('error', 'Không thể duyệt đơn hàng. Vui lòng thử lại.');
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Đã duyệt đơn hàng.');
    }
}
