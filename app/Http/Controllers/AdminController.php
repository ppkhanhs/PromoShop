<?php

namespace App\Http\Controllers;

use App\Models\Cassandra\Order;
use App\Services\CassandraDataService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    protected ?Authenticatable $adminAccount = null;

    public function __construct(protected CassandraDataService $dataService)
    {
        $this->adminAccount = Auth::user();
        view()->share('adminAccount', $this->adminAccount);
    }

    public function dashboard(): View
    {
        $stats = $this->dataService->fetchDashboardStats();
        $orders = collect($this->dataService->fetchOrders());

        $ordersByStatus = $orders
            ->groupBy(fn (Order $order) => $order->get('status') ?: 'khác')
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        $trendByMonth = $orders
            ->groupBy(function (Order $order) {
                $date = $this->resolveDate($order->get('created_at'));
                return $date?->format('Y-m');
            })
            ->filter(fn ($_, $key) => !empty($key))
            ->sortKeys()
            ->map(function ($group) {
                return [
                    'orders' => $group->count(),
                    'revenue' => $group->sum(fn (Order $order) => (int) $order->get('final_amount', 0)),
                    'discount' => $group->sum(fn (Order $order) => (int) $order->get('discount', 0)),
                ];
            });

        $promotionChart = collect($stats['top_promotions'] ?? [])
            ->map(function ($promo) {
                return [
                    'label' => $promo['promo_id'] ?? $promo['title'] ?? 'Không xác định',
                    'usage' => (int) ($promo['usage'] ?? 0),
                    'discount' => (int) ($promo['discount_amount'] ?? 0),
                ];
            })
            ->values();

        $chartData = [
            'status' => [
                'labels' => $ordersByStatus->keys()->values()->all(),
                'series' => $ordersByStatus->values()->all(),
            ],
            'revenue' => [
                'categories' => $trendByMonth->keys()->values()->all(),
                'series' => [
                    'revenue' => $trendByMonth->pluck('revenue')->values()->all(),
                    'discount' => $trendByMonth->pluck('discount')->values()->all(),
                    'orders' => $trendByMonth->pluck('orders')->values()->all(),
                ],
            ],
            'promotions' => [
                'labels' => $promotionChart->pluck('label')->all(),
                'usage' => $promotionChart->pluck('usage')->all(),
                'discount' => $promotionChart->pluck('discount')->all(),
            ],
        ];

        return $this->renderAdminView('admin.dashboard', [
            'stats' => $stats,
            'chartData' => $chartData,
        ]);
    }

    protected function renderAdminView(string $view, array $data = []): View
    {
        return view($view, array_merge([
            'adminAccount' => $this->adminAccount,
        ], $data));
    }

    protected function resolveDate(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_array($value)) {
            if (isset($value['date'])) {
                return $this->resolveDate($value['date']);
            }

            if (isset($value['year'], $value['month'], $value['day'])) {
                return Carbon::create(
                    (int) $value['year'],
                    (int) $value['month'],
                    (int) $value['day']
                );
            }

            $first = reset($value);
            return $this->resolveDate($first);
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable $th) {
                return null;
            }
        }

        return null;
    }
}
