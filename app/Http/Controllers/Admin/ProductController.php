<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CassandraDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(protected CassandraDataService $dataService)
    {
    }

    public function index(): View
    {
        $products = $this->dataService->fetchProducts();

        return view('admin.products.index', [
            'products' => $products,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validateProduct($request);
        $this->dataService->saveProduct($payload);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Tạo sản phẩm thành công.');
    }

    public function edit(string $product): View
    {
        $item = $this->dataService->fetchProduct($product);
        if (!$item) {
            abort(404);
        }

        return view('admin.products.form', [
            'product' => $item,
        ]);
    }

    public function update(Request $request, string $product): RedirectResponse
    {
        $payload = $this->validateProduct($request, $product);
        $this->dataService->saveProduct($payload, $product);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy(string $product): RedirectResponse
    {
        $this->dataService->deleteProduct($product);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Đã xóa sản phẩm.');
    }

    protected function validateProduct(Request $request, ?string $productId = null): array
    {
        $rules = [
            'product_id' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'image_url' => ['nullable', 'url'],
        ];

        if ($productId) {
            $rules['product_id'][] = function ($attribute, $value, $fail) use ($productId) {
                if ($value !== $productId) {
                    $fail('Không thể thay đổi mã sản phẩm.');
                }
            };
        }

        $data = $request->validate($rules);

        return [
            'product_id' => strtoupper($data['product_id']),
            'name' => $data['name'],
            'price' => (int) $data['price'],
            'stock' => (int) ($data['stock'] ?? 0),
            'category' => $data['category'] ?? null,
            'status' => $data['status'] ?? 'active',
            'image_url' => $data['image_url'] ?? null,
        ];
    }
}

