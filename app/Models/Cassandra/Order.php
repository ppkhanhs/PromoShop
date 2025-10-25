<?php

namespace App\Models\Cassandra;

class Order extends CassandraModel
{
    public function formattedTotal(): string
    {
        return number_format((int) $this->get('total', 0), 0, ',', '.');
    }

    public function formattedDiscount(): string
    {
        return number_format((int) $this->get('discount', 0), 0, ',', '.');
    }

    public function formattedFinal(): string
    {
        return number_format(
            (int) $this->get('final_amount', (int) $this->get('total', 0) - (int) $this->get('discount', 0)),
            0,
            ',',
            '.'
        );
    }
}

