<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cassandra\Promotion;
use App\Models\Cassandra\PromotionTier;
use App\Services\CassandraDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function __construct(protected CassandraDataService $dataService)
    {
    }

    public function index(): View
    {
        $coupons = collect($this->dataService->fetchPromotions(true))
            ->filter(fn (Promotion $promotion) => !$promotion->get('auto_apply', true) || strtolower($promotion->get('type', '')) === 'coupon')
            ->map(function (Promotion $promotion) {
                $tier = collect($promotion->tiers())
                    ->sortBy(fn (PromotionTier $tier) => $tier->get('tier_level'))
                    ->first();

                return [
                    'promotion' => $promotion,
                    'tier' => $tier,
                ];
            })
            ->values();

        return view('admin.coupons.index', [
            'coupons' => $coupons,
        ]);
    }

    public function create(): View
    {
        return view('admin.coupons.form', [
            'promotion' => null,
            'tier' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$promotionPayload, $tierPayload] = $this->validateCoupon($request);

        $this->dataService->savePromotion($promotionPayload);
        $this->dataService->savePromotionTier($promotionPayload['promo_id'], $tierPayload, 1);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Đã tạo mã giảm giá thành công.');
    }

    public function edit(string $coupon): View
    {
        [$promotion, $tier] = $this->findCouponOrFail($coupon);

        return view('admin.coupons.form', [
            'promotion' => $promotion,
            'tier' => $tier,
        ]);
    }

    public function update(Request $request, string $coupon): RedirectResponse
    {
        [$promotionPayload, $tierPayload] = $this->validateCoupon($request, $coupon);

        $this->dataService->savePromotion($promotionPayload, $coupon);
        $this->dataService->savePromotionTier($coupon, $tierPayload, 1);

        return redirect()
            ->route('admin.coupons.edit', $coupon)
            ->with('success', 'Đã cập nhật mã giảm giá.');
    }

    public function destroy(string $coupon): RedirectResponse
    {
        [$promotion] = $this->findCouponOrFail($coupon);
        $this->dataService->deletePromotion($promotion->get('promo_id'));

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Đã xoá mã giảm giá.');
    }

    /**
     * @return array{0: Promotion, 1: PromotionTier|null}
     */
    protected function findCouponOrFail(string $coupon): array
    {
        $promotion = $this->dataService->fetchPromotion($coupon, true);
        if (!$promotion || ($promotion->get('auto_apply', true) && strtolower($promotion->get('type', '')) !== 'coupon')) {
            abort(404);
        }

        $tier = collect($promotion->tiers())
            ->sortBy(fn (PromotionTier $tier) => $tier->get('tier_level'))
            ->first();

        return [$promotion, $tier];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function validateCoupon(Request $request, ?string $promoId = null): array
    {
        $rules = [
            'code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', 'in:percent,amount'],
            'discount_value' => ['required', 'integer', 'min:0'],
            'min_order' => ['nullable', 'integer', 'min:0'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'string', 'max:50'],
            'stackable' => ['nullable', 'boolean'],
            'freeship' => ['nullable', 'boolean'],
            'tier_label' => ['nullable', 'string', 'max:255'],
        ];

        if ($promoId) {
            $rules['code'][] = function ($attribute, $value, $fail) use ($promoId) {
                if (strtoupper($value) !== strtoupper($promoId)) {
                    $fail('Không thể đổi mã coupon.');
                }
            };
        }

        $data = $request->validate($rules);

        $code = strtoupper($data['code']);
        $discountType = $data['discount_type'];
        $discountValue = (int) $data['discount_value'];
        $freeship = (bool) ($data['freeship'] ?? false);

        $promotionPayload = [
            'promo_id' => $code,
            'title' => $data['title'],
            'type' => 'coupon',
            'description' => $data['description'] ?? null,
            'reward_type' => 'discount',
            'min_order' => (int) ($data['min_order'] ?? 0),
            'discount_percent' => $discountType === 'percent' ? $discountValue : null,
            'max_discount_amount' => isset($data['max_discount']) ? (int) $data['max_discount'] : null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'],
            'auto_apply' => false,
            'stackable' => (bool) ($data['stackable'] ?? false),
        ];

        $tierPayload = [
            'tier_level' => 1,
            'label' => $data['tier_label'] ?? ('Ưu đãi mã ' . $code),
            'min_value' => (int) ($data['min_order'] ?? 0),
            'discount_percent' => $discountType === 'percent' ? $discountValue : null,
            'discount_amount' => $discountType === 'amount' ? $discountValue : null,
            'freeship' => $freeship,
        ];

        return [$promotionPayload, $tierPayload];
    }
}
