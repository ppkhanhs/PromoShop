<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cassandra\Order;
use App\Services\CassandraDataService;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(protected CassandraDataService $dataService)
    {
    }

    public function index(): View
    {
        $orders = collect($this->dataService->fetchOrders());

        $summary = [
            'total' => $orders->count(),
            'revenue' => $orders->sum(fn (Order $order) => (int) $order->get('final_amount', 0)),
            'discount' => $orders->sum(fn (Order $order) => (int) $order->get('discount', 0)),
            'shipping' => $orders->sum(fn (Order $order) => (int) $order->get('shipping_fee', 0)),
        ];

        $promotionStats = $orders
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
            'orders' => $orders,
            'summary' => $summary,
            'promotionStats' => $promotionStats,
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
        ]);
    }
}

