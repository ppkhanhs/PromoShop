<?php

namespace App\Services;

use App\Models\Cassandra\Product;
use App\Models\Cassandra\Promotion;
use App\Models\Cassandra\PromotionTier;
use Illuminate\Support\Arr;

class PromotionEngineService
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, Promotion|array<string, mixed>>  $promotions
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function calculate(array $items, array $promotions, array $options = []): array
    {
        $subtotal = $this->calculateSubtotal($items);
        $shippingFee = (int) Arr::get($options, 'shipping_fee', 15000);

        $applied = [];
        $discount = 0;
        $shippingDiscount = 0;
        $gifts = [];

        foreach ($promotions as $promotion) {
            $promo = $promotion instanceof Promotion ? $promotion : Promotion::from((array) $promotion);

            $eligibleTier = $this->resolveEligibleTier($promo, $subtotal, $items);
            if (!$eligibleTier) {
                continue;
            }

            $result = $this->applyTier($promo, $eligibleTier, $subtotal, $shippingFee);
            if ($result['discount'] <= 0 && $result['shipping_discount'] <= 0 && empty($result['gift'])) {
                continue;
            }

            $discount += $result['discount'];
            $shippingDiscount = max($shippingDiscount, $result['shipping_discount']);

            if (!empty($result['gift'])) {
                $gifts[] = $result['gift'];
            }

            $applied[] = [
                'promotion' => $promo->toArray(),
                'tier' => $eligibleTier->toArray(),
                'discount' => $result['discount'],
                'shipping_discount' => $result['shipping_discount'],
                'gift' => $result['gift'],
            ];
        }

        $finalShipping = max($shippingFee - $shippingDiscount, 0);
        $finalTotal = max($subtotal - $discount + $finalShipping, 0);

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_fee' => $shippingFee,
            'shipping_discount' => $shippingDiscount,
            'final_shipping_fee' => $finalShipping,
            'final_total' => $finalTotal,
            'applied_promotions' => $applied,
            'gifts' => $gifts,
        ];
    }

    /**
     * @param  Promotion|array<string, mixed>  $promotion
     */
    protected function resolveEligibleTier(Promotion $promotion, int $subtotal, array $items): ?PromotionTier
    {
        $tiers = $promotion->tiers();
        if (empty($tiers) && is_array($promotion->get('tiers'))) {
            $tiers = collect($promotion->get('tiers'))
                ->map(fn ($tier) => PromotionTier::from((array) $tier))
                ->all();
        }

        if (empty($tiers)) {
            return null;
        }

        usort($tiers, function (PromotionTier $a, PromotionTier $b) {
            $minA = (int) $a->get('min_value', $a->get('min_order', 0));
            $minB = (int) $b->get('min_value', $b->get('min_order', 0));
            return $minA <=> $minB;
        });

        $eligible = null;
        foreach ($tiers as $tier) {
            $minAmount = (int) $tier->get('min_value', $tier->get('min_order', 0));
            $minQty = (int) $tier->get('min_quantity', $tier->get('min_qty', 0));

            $passesAmount = $subtotal >= $minAmount;
            $passesQuantity = $minQty <= 0 || $this->totalQuantity($items) >= $minQty;

            if ($passesAmount && $passesQuantity) {
                $eligible = $tier;
            }
        }

        return $eligible;
    }

    protected function applyTier(Promotion $promotion, PromotionTier $tier, int $subtotal, int $shippingFee): array
    {
        $discountPercent = (int) $tier->get('discount_percent', $tier->get('discount_percentual', 0));
        $discountAmount = (int) $tier->get('discount_amount', $tier->get('discount', 0));
        $maxDiscount = (int) $promotion->get('max_discount_amount', 0);

        $discount = 0;
        if ($discountPercent > 0) {
            $discount = (int) floor($subtotal * $discountPercent / 100);
        }

        if ($discountAmount > 0) {
            $discount += $discountAmount;
        }

        if ($maxDiscount > 0 && $discount > $maxDiscount) {
            $discount = $maxDiscount;
        }

        $shippingDiscount = 0;
        if ($tier->get('freeship') || $tier->get('free_shipping')) {
            $shippingDiscount = $shippingFee;
        }

        $gift = null;
        if ($tier->get('gift_product_id')) {
            $gift = [
                'product_id' => $tier->get('gift_product_id'),
                'quantity' => (int) $tier->get('gift_quantity', 1),
                'description' => $tier->get('combo_description') ?: 'Tặng kèm sản phẩm',
            ];
        } elseif ($tier->get('reward')) {
            $gift = (array) $tier->get('reward');
        }

        return [
            'discount' => max($discount, 0),
            'shipping_discount' => max($shippingDiscount, 0),
            'gift' => $gift,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function calculateSubtotal(array $items): int
    {
        return (int) collect($items)->reduce(function ($carry, $item) {
            $price = (int) Arr::get($item, 'price', Arr::get($item, 'unit_price', 0));
            $qty = (int) Arr::get($item, 'quantity', Arr::get($item, 'qty', 1));

            return $carry + ($price * $qty);
        }, 0);
    }

    protected function totalQuantity(array $items): int
    {
        return (int) collect($items)->reduce(function ($carry, $item) {
            return $carry + (int) Arr::get($item, 'quantity', Arr::get($item, 'qty', 1));
        }, 0);
    }
}

