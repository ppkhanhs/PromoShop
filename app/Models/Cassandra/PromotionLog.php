<?php

namespace App\Models\Cassandra;

class PromotionLog extends CassandraModel
{
    public function appliedAt(): string
    {
        return (string) $this->get('applied_at', '');
    }
}

