<?php

namespace App\Models\Cassandra;

class PromotionTier extends CassandraModel
{
    public function label(): string
    {
        if ($this->get('label')) {
            return (string) $this->get('label');
        }

        $level = $this->get('tier_level', $this->get('priority', 1));
        return 'Tầng ' . $level;
    }

    public function formattedMinValue(): string
    {
        return number_format((int) $this->get('min_value', 0), 0, ',', '.');
    }
}

