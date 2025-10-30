<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVoucher;
use App\Services\CassandraDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProductPromotionController extends Controller
{
    public function __construct(protected CassandraDataService $dataService)
    {
    }

    public function index(): View
    {
        $assignments = Schema::hasTable('product_voucher')
            ? ProductVoucher::orderByDesc('updated_at')->get()
            : collect();

        $products = collect($this->dataService->fetchProducts())
            ->keyBy(fn ($product) => strtoupper((string) ($product->product_id ?? $product->get('product_id'))));
        $vouchers = collect($this->dataService->fetchPromoCodes())
            ->keyBy(fn ($voucher) => strtoupper((string) $voucher->get('code')));

        $items = $assignments->map(function (ProductVoucher $mapping) use ($products, $vouchers) {
            $product = $products->get(strtoupper($mapping->product_id));
            $voucher = $vouchers->get(strtoupper($mapping->voucher_code));

            return [
                'mapping' => $mapping,
                'product' => $product,
                'voucher' => $voucher,
            ];
        });

        return view('admin.promotions.products.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        $products = $this->dataService->fetchProducts();
        $vouchers = $this->dataService->fetchPromoCodes();

        return view('admin.promotions.products.create', [
            'products' => $products,
            'vouchers' => $vouchers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string', 'max:120'],
            'voucher_code' => ['required', 'string', 'max:80'],
        ]);

        $productId = strtoupper(trim($data['product_id']));
        $voucherCode = strtoupper(trim($data['voucher_code']));

        if (
            ProductVoucher::where('product_id', $productId)
                ->where('voucher_code', $voucherCode)
                ->exists()
        ) {
            return redirect()
                ->route('admin.product-promotions.index')
                ->with('error', 'Sản phẩm đã được gán mã khuyến mãi này.');
        }

        $product = $this->dataService->fetchProduct($productId);
        if (!$product) {
            return back()
                ->withInput()
                ->withErrors(['product_id' => 'Không tìm thấy sản phẩm trong hệ thống Cassandra.']);
        }

        $voucher = $this->dataService->fetchPromoCodeByCode($voucherCode);
        if (!$voucher) {
            return back()
                ->withInput()
                ->withErrors(['voucher_code' => 'Không tìm thấy mã khuyến mãi.']);
        }

        ProductVoucher::create([
            'product_id' => $productId,
            'voucher_code' => $voucherCode,
            'promo_id' => strtoupper((string) $voucher->get('promo_id')),
            'discount_type' => strtolower((string) $voucher->get('discount_type', 'amount')),
            'discount_value' => (float) $voucher->get('discount_value', 0),
            'max_discount_amount' => $voucher->get('max_discount_amount') !== null
                ? (float) $voucher->get('max_discount_amount')
                : null,
        ]);

        return redirect()
            ->route('admin.product-promotions.index')
            ->with('success', 'Đã gán khuyến mãi cho sản phẩm.');
    }

    public function destroy(ProductVoucher $productPromotion): RedirectResponse
    {
        $productPromotion->delete();

        return redirect()
            ->route('admin.product-promotions.index')
            ->with('success', 'Đã hủy áp dụng khuyến mãi cho sản phẩm.');
    }
}
