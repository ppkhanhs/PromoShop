<?php

namespace App\Models\Cassandra;

class Promotion extends CassandraModel
{
    /**
     * @return array<int, PromotionTier>
     */
    public function tiers(): array
    {
        $tiers = [];
        foreach ($this->get('tiers', []) as $tier) {
            $tiers[] = PromotionTier::from($tier);
        }

        return $tiers;
    }

    public function statusLabel(): string
    {
        $start = $this->get('start_date');
        $end = $this->get('end_date');
        $today = date('Y-m-d');

        if ($start && $today < $start) {
            return 'Chờ áp dụng';
        }

        if ($end && $today > $end) {
            return 'Hết hạn';
        }

        return 'Đang chạy';
    }
}

