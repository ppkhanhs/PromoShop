<?php

namespace App\Http\Controllers;

use App\Models\Cassandra\Promotion;
use App\Services\CassandraDataService;
use App\Services\PromotionEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ClientController extends Controller
{
    private const CART_SELECTED_PROMOTIONS_KEY = 'cart.promotions.selected';

    public function __construct(
        protected CassandraDataService $dataService,
        protected PromotionEngineService $promotionEngine
    ) {
    }

    public function home(): View
    {
        $products = $this->dataService->fetchProducts();
        $promotions = $this->dataService->fetchPromotions(true);

        return view('client.home', [
            'products' => $products,
            'promotions' => $promotions,
        ]);
    }

    public function cart(Request $request): View
    {
        $items = $this->getCartItems($request);
        $promotions = $this->dataService->fetchPromotions(true);
        $selectedPromotionIds = $request->session()->get(self::CART_SELECTED_PROMOTIONS_KEY, []);
        $activePromotions = $this->filterPromotionsForCart($promotions, $selectedPromotionIds);

        $summary = $this->promotionEngine->calculate($items, $activePromotions, [
            'shipping_fee' => 15000,
        ]);
        $selectedPromotionDetails = $this->matchPromotions($promotions, $selectedPromotionIds);
        $pendingPromotions = $this->resolvePendingPromotions($selectedPromotionDetails, Arr::get($summary, 'applied_promotions', []));

        return view('client.cart', [
            'cartItems' => $items,
            'summary' => $summary,
            'promotions' => $promotions,
            'selectedPromotions' => array_map(fn (Promotion $promotion) => $promotion->toArray(), $selectedPromotionDetails),
            'pendingPromotions' => $pendingPromotions,
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $quantity = (int) ($data['quantity'] ?? 1);
        $product = $this->dataService->fetchProduct($data['product_id']);

        if (!$product) {
            return back()->with('error', 'Không tìm thấy sản phẩm.');
        }

        $items = $this->getCartItems($request);
        $existingKey = array_search($product->product_id, array_column($items, 'product_id'), true);

        if ($existingKey !== false) {
            $items[$existingKey]['quantity'] += $quantity;
        } else {
            $items[] = [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'price' => (int) $product->price,
                'quantity' => $quantity,
            ];
        }

        $this->storeCartItems($request, $items);

        return redirect()->back()->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function updateCart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $items = $this->getCartItems($request);
        foreach ($items as $index => $item) {
            if ($item['product_id'] === $data['product_id']) {
                if ($data['quantity'] <= 0) {
                    unset($items[$index]);
                } else {
                    $items[$index]['quantity'] = $data['quantity'];
                }
            }
        }

        $this->storeCartItems($request, array_values($items));

        return redirect()->route('client.cart')->with('success', 'Cập nhật giỏ hàng thành công.');
    }

    public function removeFromCart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string'],
        ]);

        $items = $this->getCartItems($request);
        $items = array_values(array_filter($items, fn ($item) => $item['product_id'] !== $data['product_id']));

        $this->storeCartItems($request, $items);

        return redirect()->route('client.cart')->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function applyPromotion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'promotion_code' => ['required', 'string'],
        ]);

        $items = $this->getCartItems($request);
        if (empty($items)) {
            return redirect()->route('client.home')->with('warning', 'Giỏ hàng đang trống. Hãy thêm sản phẩm trước khi áp dụng khuyến mãi.');
        }

        $code = strtoupper(trim($data['promotion_code']));
        $promotions = $this->dataService->fetchPromotions(true);
        $promotion = $this->findPromotionByCode($promotions, $code);

        if (!$promotion) {
            return back()
                ->withErrors(['promotion_code' => 'Không tìm thấy khuyến mãi khớp với mã đã nhập.'])
                ->withInput();
        }

        $identifier = $this->primaryPromotionIdentifier($promotion);
        $selected = $request->session()->get(self::CART_SELECTED_PROMOTIONS_KEY, []);
        if (!in_array($identifier, $selected, true)) {
            $selected[] = $identifier;
            $request->session()->put(self::CART_SELECTED_PROMOTIONS_KEY, $selected);
        }

        $preview = $this->promotionEngine->calculate($items, [$promotion], [
            'shipping_fee' => 15000,
        ]);

        $applied = Arr::get($preview, 'applied_promotions', []);
        $message = 'Đã áp dụng khuyến mãi. Tiếp tục mua sắm để tối ưu đơn hàng của bạn!';
        if (empty($applied)) {
            $message = 'Đã lưu mã khuyến mãi. Hãy tăng giá trị đơn hàng hoặc số lượng sản phẩm để kích hoạt ưu đãi.';
        }

        return redirect()->route('client.cart')->with('success', $message);
    }

    public function removePromotion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'promotion_id' => ['required', 'string'],
        ]);

        $selected = $request->session()->get(self::CART_SELECTED_PROMOTIONS_KEY, []);
        $target = strtoupper(trim($data['promotion_id']));
        $filtered = array_values(array_filter($selected, fn ($value) => strtoupper((string) $value) !== $target));

        if (empty($filtered)) {
            $request->session()->forget(self::CART_SELECTED_PROMOTIONS_KEY);
        } else {
            $request->session()->put(self::CART_SELECTED_PROMOTIONS_KEY, $filtered);
        }

        return redirect()->route('client.cart')->with('success', 'Đã hủy áp dụng khuyến mãi.');
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $items = $this->getCartItems($request);
        if (empty($items)) {
            return redirect()->route('client.home')->with('warning', 'Giỏ hàng đang trống.');
        }

        $promotions = $this->dataService->fetchPromotions(true);
        $selectedIds = $request->session()->get(self::CART_SELECTED_PROMOTIONS_KEY, []);
        $activePromotions = $this->filterPromotionsForCart($promotions, $selectedIds);

        $summary = $this->promotionEngine->calculate($items, $activePromotions, [
            'shipping_fee' => 15000,
        ]);

        return view('client.checkout', [
            'cartItems' => $items,
            'summary' => $summary,
            'promotions' => $promotions,
            'user' => Auth::user(),
        ]);
    }

    public function submitCheckout(Request $request): RedirectResponse
    {
        $items = $this->getCartItems($request);
        if (empty($items)) {
            return redirect()->route('client.home')->with('warning', 'Giỏ hàng đang trống.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        $promotions = $this->dataService->fetchPromotions(true);
        $selectedIds = $request->session()->get(self::CART_SELECTED_PROMOTIONS_KEY, []);
        $activePromotions = $this->filterPromotionsForCart($promotions, $selectedIds);

        $summary = $this->promotionEngine->calculate($items, $activePromotions, [
            'shipping_fee' => 15000,
        ]);

        $payload = [
            'user_id' => Auth::id(),
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'shipping_address' => $data['shipping_address'],
            'note' => $data['note'] ?? null,
            'items' => $items,
            'summary' => $summary,
        ];

        $order = $this->dataService->createOrder($payload);
        if (!$order) {
            return back()->with('error', 'Không thể tạo đơn hàng, vui lòng thử lại.');
        }

        $this->storeCartItems($request, []);
        $request->session()->forget(self::CART_SELECTED_PROMOTIONS_KEY);

        return redirect()->route('client.orders')->with('success', 'Đặt hàng thành công.');
    }

    public function orders(Request $request): View
    {
        $user = Auth::user();
        $orders = [];

        if ($user) {
            $orders = $this->dataService->fetchOrders((string) $user->getAuthIdentifier());
            $orders = $this->filterOrders($orders, $request);
        }

        return view('client.orders', [
            'orders' => $orders,
        ]);
    }

    public function reorderOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'string'],
        ]);

        $userId = Auth::id();
        if (!$userId) {
            return redirect()->route('login');
        }

        $orders = $this->dataService->fetchOrders((string) $userId);
        $target = strtoupper(trim($data['order_id']));

        $order = collect($orders)->first(function ($order) use ($target) {
            $orderId = strtoupper((string) ($order->get('order_id') ?? ''));
            $orderCode = strtoupper((string) ($order->get('order_code') ?? ''));

            return $orderId === $target || $orderCode === $target;
        });

        if (!$order) {
            return redirect()->back()->with('error', 'Không tìm thấy đơn hàng để mua lại.');
        }

        $items = (array) $order->get('items', []);
        $cartItems = collect($items)
            ->map(function ($item) {
                $productId = $item['product_id'] ?? $item['sku'] ?? null;
                if (!$productId) {
                    return null;
                }

                return [
                    'product_id' => (string) $productId,
                    'name' => $item['name'] ?? $productId,
                    'price' => (int) ($item['price'] ?? $item['unit_price'] ?? 0),
                    'quantity' => max((int) ($item['quantity'] ?? $item['qty'] ?? 1), 1),
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (empty($cartItems)) {
            return redirect()->back()->with('warning', 'Đơn hàng chưa có dữ liệu sản phẩm để mua lại.');
        }

        $this->storeCartItems($request, $cartItems);
        $request->session()->forget(self::CART_SELECTED_PROMOTIONS_KEY);

        return redirect()->route('client.cart')->with('success', 'Đã thêm sản phẩm từ đơn hàng vào giỏ.');
    }

    public function downloadInvoice(Request $request, string $order): RedirectResponse
    {
        return redirect()->back()->with('info', 'Chức năng tải hóa đơn đang được phát triển.');
    }

    public function trackOrder(Request $request, string $order): RedirectResponse
    {
        return redirect()->back()->with('info', 'Tính năng theo dõi vận chuyển sẽ sớm ra mắt.');
    }

    public function support(Request $request): RedirectResponse
    {
        return redirect()->back()->with('info', 'Liên hệ đội ngũ hỗ trợ qua email support@promoshop.test để được trợ giúp.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getCartItems(Request $request): array
    {
        return $request->session()->get('cart.items', []);
    }

    protected function storeCartItems(Request $request, array $items): void
    {
        $request->session()->put('cart.items', $items);
    }

    /**
     * @param  array<int, mixed>  $orders
     */
    protected function filterOrders(array $orders, Request $request): array
    {
        $status = strtolower(trim((string) $request->input('status', '')));
        $keyword = strtolower(trim((string) $request->input('keyword', '')));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $fromTimestamp = $dateFrom ? strtotime($dateFrom . ' 00:00:00') : null;
        $toTimestamp = $dateTo ? strtotime($dateTo . ' 23:59:59') : null;

        return collect($orders)->filter(function ($order) use ($status, $keyword, $fromTimestamp, $toTimestamp) {
            $orderStatus = strtolower((string) ($order->get('status') ?? ''));
            if ($status !== '' && $orderStatus !== $status) {
                return false;
            }

            $orderDateRaw = $order->get('created_at') ?? $order->get('order_date');
            $orderTimestamp = $orderDateRaw ? strtotime((string) $orderDateRaw) : null;
            if ($fromTimestamp && $orderTimestamp && $orderTimestamp < $fromTimestamp) {
                return false;
            }
            if ($toTimestamp && $orderTimestamp && $orderTimestamp > $toTimestamp) {
                return false;
            }

            if ($keyword !== '') {
                $items = $order->get('items', []);
                if (is_string($items)) {
                    $decoded = json_decode($items, true);
                    $items = is_array($decoded) ? $decoded : [];
                }

                $haystack = strtolower(
                    (string) ($order->get('order_id') ?? '') . ' ' .
                    (string) ($order->get('order_code') ?? '') . ' ' .
                    json_encode($items, JSON_UNESCAPED_UNICODE)
                );

                if (!str_contains($haystack, $keyword)) {
                    return false;
                }
            }

            return true;
        })->values()->all();
    }

    /**
     * @param  array<int, Promotion>|array<int, array<string, mixed>>  $promotions
     */
    protected function findPromotionByCode(array $promotions, string $code): ?Promotion
    {
        foreach ($promotions as $promotion) {
            $promotion = $promotion instanceof Promotion ? $promotion : Promotion::from((array) $promotion);
            foreach ($this->promotionIdentifiers($promotion) as $identifier) {
                if ($identifier === $code) {
                    return $promotion;
                }
            }
        }

        return null;
    }

    protected function primaryPromotionIdentifier(Promotion $promotion): string
    {
        $identifiers = $this->promotionIdentifiers($promotion);
        if (!empty($identifiers)) {
            return (string) $identifiers[0];
        }

        return (string) ($promotion->get('promo_id') ?? uniqid('promo_', true));
    }

    /**
     * @param  Promotion|array<string, mixed>  $promotion
     * @return array<int, string>
     */
    protected function promotionIdentifiers(Promotion|array $promotion): array
    {
        $payload = $promotion instanceof Promotion ? $promotion->toArray() : (array) $promotion;
        $keys = ['promo_id', 'promo_code', 'code', 'promotion_code', 'id'];
        $values = [];

        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $values[] = strtoupper((string) $value);
        }

        return array_values(array_unique($values));
    }

    /**
     * @param  array<int, Promotion>  $promotions
     * @param  array<int, string>  $selectedIds
     * @return array<int, Promotion>
     */
    protected function filterPromotionsForCart(array $promotions, array $selectedIds): array
    {
        if (empty($selectedIds)) {
            return $promotions;
        }

        $needle = array_map(fn ($value) => strtoupper((string) $value), $selectedIds);
        $filtered = [];

        foreach ($promotions as $promotion) {
            foreach ($this->promotionIdentifiers($promotion) as $identifier) {
                if (in_array($identifier, $needle, true)) {
                    $filtered[] = $promotion;
                    break;
                }
            }
        }

        return empty($filtered) ? $promotions : $filtered;
    }

    /**
     * @param  array<int, Promotion>  $promotions
     * @param  array<int, string>  $identifiers
     * @return array<int, Promotion>
     */
    protected function matchPromotions(array $promotions, array $identifiers): array
    {
        if (empty($identifiers)) {
            return [];
        }

        $needle = array_map(fn ($value) => strtoupper((string) $value), $identifiers);
        $matches = [];

        foreach ($promotions as $promotion) {
            foreach ($this->promotionIdentifiers($promotion) as $identifier) {
                if (in_array($identifier, $needle, true)) {
                    $matches[] = $promotion;
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * @param  array<int, Promotion>  $selectedPromotions
     * @param  array<int, mixed>  $appliedSummary
     * @return array<int, array<string, mixed>>
     */
    protected function resolvePendingPromotions(array $selectedPromotions, array $appliedSummary): array
    {
        if (empty($selectedPromotions)) {
            return [];
        }

        $appliedIds = [];
        foreach ($appliedSummary as $applied) {
            $promotionData = Arr::get($applied, 'promotion', []);
            foreach ($this->promotionIdentifiers((array) $promotionData) as $identifier) {
                $appliedIds[] = $identifier;
            }
        }

        $pending = [];
        foreach ($selectedPromotions as $promotion) {
            $identifiers = $this->promotionIdentifiers($promotion);
            $isApplied = false;
            foreach ($identifiers as $identifier) {
                if (in_array($identifier, $appliedIds, true)) {
                    $isApplied = true;
                    break;
                }
            }

            if (!$isApplied) {
                $pending[] = $promotion->toArray();
            }
        }

        return $pending;
    }
}
