<?php

namespace App\Models\Cassandra;

class Product extends CassandraModel
{
    public function formattedPrice(): string
    {
        return number_format((int) $this->get('price', 0), 0, ',', '.');
    }
}

