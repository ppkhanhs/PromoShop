<?php

namespace App\Http\Controllers;

use App\Services\CassandraDataService;
use App\Services\PromotionEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClientController extends Controller
{
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
        $summary = $this->promotionEngine->calculate($items, $promotions, [
            'shipping_fee' => 15000,
        ]);

        return view('client.cart', [
            'cartItems' => $items,
            'summary' => $summary,
            'promotions' => $promotions,
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

        return redirect()->route('client.cart')->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
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

    public function checkout(Request $request): View|RedirectResponse
    {
        $items = $this->getCartItems($request);
        if (empty($items)) {
            return redirect()->route('client.home')->with('warning', 'Giỏ hàng đang trống.');
        }

        $promotions = $this->dataService->fetchPromotions(true);
        $summary = $this->promotionEngine->calculate($items, $promotions, [
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
        $summary = $this->promotionEngine->calculate($items, $promotions, [
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

        return redirect()->route('client.orders')->with('success', 'Đặt hàng thành công.');
    }

    public function orders(): View
    {
        $user = Auth::user();
        $orders = [];

        if ($user) {
            $orders = $this->dataService->fetchOrders((string) $user->getAuthIdentifier());
        }

        return view('client.orders', [
            'orders' => $orders,
        ]);
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
}

