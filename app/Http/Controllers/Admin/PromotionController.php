<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CassandraDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(protected CassandraDataService $dataService)
    {
    }

    public function index(): View
    {
        $promotions = $this->dataService->fetchPromotions(true);

        return view('admin.promotions.index', [
            'promotions' => $promotions,
        ]);
    }

    public function conditions(): View
    {
        $promotions = $this->dataService->fetchPromotions(true);

        return view('admin.promotions.conditions', [
            'promotions' => $promotions,
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions.form', [
            'promotion' => null,
            'tiers' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatePromotion($request);

        $this->dataService->savePromotion($payload);

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Tạo khuyến mãi thành công.');
    }

    public function edit(string $promotion): View
    {
        $promo = $this->dataService->fetchPromotion($promotion, true);
        if (!$promo) {
            abort(404);
        }

        $tiers = $this->dataService->fetchPromotionTiers($promotion);

        return view('admin.promotions.form', [
            'promotion' => $promo,
            'tiers' => $tiers,
        ]);
    }

    public function update(Request $request, string $promotion): RedirectResponse
    {
        $payload = $this->validatePromotion($request, $promotion);

        $this->dataService->savePromotion($payload, $promotion);

        return redirect()
            ->route('admin.promotions.edit', $promotion)
            ->with('success', 'Cập nhật khuyến mãi thành công.');
    }

    public function destroy(string $promotion): RedirectResponse
    {
        $this->dataService->deletePromotion($promotion);

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Đã xóa khuyến mãi.');
    }

    public function storeTier(Request $request, string $promotion): RedirectResponse
    {
        $data = $this->validateTier($request);

        $this->dataService->savePromotionTier($promotion, $data);

        return redirect()
            ->route('admin.promotions.edit', $promotion)
            ->with('success', 'Đã thêm tầng khuyến mãi.');
    }

    public function updateTier(Request $request, string $promotion, int $tier): RedirectResponse
    {
        $data = $this->validateTier($request, $tier);

        $this->dataService->savePromotionTier($promotion, $data, $tier);

        return redirect()
            ->route('admin.promotions.edit', $promotion)
            ->with('success', 'Đã cập nhật tầng khuyến mãi.');
    }

    public function destroyTier(string $promotion, int $tier): RedirectResponse
    {
        $this->dataService->deletePromotionTier($promotion, $tier);

        return redirect()
            ->route('admin.promotions.edit', $promotion)
            ->with('success', 'Đã xóa tầng khuyến mãi.');
    }

    protected function validatePromotion(Request $request, ?string $promoId = null): array
    {
        $rules = [
            'promo_id' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'reward_type' => ['nullable', 'string', 'max:50'],
            'min_order' => ['nullable', 'integer', 'min:0'],
            'max_discount_amount' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', 'max:50'],
            'auto_apply' => ['nullable', 'boolean'],
            'stackable' => ['nullable', 'boolean'],
        ];

        if ($promoId) {
            $rules['promo_id'][] = function ($attribute, $value, $fail) use ($promoId) {
                if ($value !== $promoId) {
                    $fail('Không thể thay đổi mã khuyến mãi.');
                }
            };
        }

        $data = $request->validate($rules);

        $payload = [
            'promo_id' => strtoupper($data['promo_id']),
            'title' => $data['title'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'reward_type' => $data['reward_type'] ?? 'discount',
            'min_order' => (int) ($data['min_order'] ?? 0),
            'max_discount_amount' => isset($data['max_discount_amount'])
                ? (int) $data['max_discount_amount']
                : null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'active',
            'auto_apply' => (bool) ($data['auto_apply'] ?? true),
            'stackable' => (bool) ($data['stackable'] ?? false),
        ];

        return $payload;
    }

    protected function validateTier(Request $request, ?int $tierLevel = null): array
    {
        $rules = [
            'tier_level' => ['required', 'integer', 'min:1'],
            'label' => ['nullable', 'string', 'max:255'],
            'min_value' => ['required', 'integer', 'min:0'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'freeship' => ['nullable', 'boolean'],
            'gift_product_id' => ['nullable', 'string', 'max:50'],
            'gift_quantity' => ['nullable', 'integer', 'min:1'],
            'combo_description' => ['nullable', 'string'],
        ];

        if ($tierLevel !== null) {
            $rules['tier_level'][] = function ($attribute, $value, $fail) use ($tierLevel) {
                if ((int) $value !== $tierLevel) {
                    $fail('Không thể thay đổi thứ tự tầng khuyến mãi.');
                }
            };
        }

        $data = $request->validate($rules);

        if (
            empty($data['discount_percent']) &&
            empty($data['discount_amount']) &&
            empty($data['freeship']) &&
            empty($data['gift_product_id'])
        ) {
            throw ValidationException::withMessages([
                'discount_percent' => 'Cần chọn ít nhất một lợi ích cho tầng khuyến mãi.',
            ]);
        }

        return [
            'tier_level' => (int) $data['tier_level'],
            'label' => $data['label'] ?? null,
            'min_value' => (int) $data['min_value'],
            'discount_percent' => isset($data['discount_percent']) ? (int) $data['discount_percent'] : null,
            'discount_amount' => isset($data['discount_amount']) ? (int) $data['discount_amount'] : null,
            'freeship' => (bool) ($data['freeship'] ?? false),
            'gift_product_id' => $data['gift_product_id'] ?? null,
            'gift_quantity' => isset($data['gift_quantity']) ? (int) $data['gift_quantity'] : null,
            'combo_description' => $data['combo_description'] ?? null,
        ];
    }
}
