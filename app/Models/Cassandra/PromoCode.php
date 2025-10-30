<?php

namespace App\Models\Cassandra;

class PromoCode extends CassandraModel
{
    public function discountLabel(): string
    {
        $type = strtolower((string) $this->get('discount_type', 'amount'));
        $value = (float) $this->get('discount_value', 0);

        if ($type === 'percent') {
            return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%';
        }

        return number_format($value, 0, ',', '.') . ' ₫';
    }

    public function statusBadge(): string
    {
        return match (strtolower((string) $this->get('status', 'draft'))) {
            'active' => 'success',
            'paused' => 'warning',
            'expired' => 'secondary',
            'revoked' => 'danger',
            default => 'secondary',
        };
    }

    public function promotionPeriod(): string
    {
        $start = $this->formatDateValue($this->get('start_date'));
        $end = $this->formatDateValue($this->get('end_date'));

        if (!$start && !$end) {
            return 'Không giới hạn';
        }

        return trim(($start ?: '---') . ' → ' . ($end ?: '---'));
    }

    private function formatDateValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (class_exists('\Cassandra\Date') && $value instanceof \Cassandra\Date) {
            /** @var \DateTimeInterface $dateTime */
            $dateTime = $value->toDateTime();
            return $dateTime->format('Y-m-d');
        }

        if (is_object($value) && method_exists($value, 'toDateTime')) {
            try {
                return $value->toDateTime()->format('Y-m-d');
            } catch (\Throwable) {
                // ignore and continue fallback
            }
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }

            if (is_numeric($trimmed)) {
                $value = (int) $trimmed;
            } else {
                try {
                    return \Carbon\Carbon::parse($trimmed)->format('Y-m-d');
                } catch (\Throwable) {
                    return $trimmed;
                }
            }
        }

        if (is_numeric($value)) {
            $days = (int) $value;
            try {
                return \Carbon\Carbon::create(1970, 1, 1)->addDays($days)->format('Y-m-d');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        if ($value instanceof \Stringable) {
            $stringValue = trim((string) $value);
            return $stringValue === '' ? null : $stringValue;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $stringValue = trim((string) $value);
            if ($stringValue === '') {
                return null;
            }

            if (is_numeric($stringValue)) {
                return $this->formatDateValue((int) $stringValue);
            }

            try {
                return \Carbon\Carbon::parse($stringValue)->format('Y-m-d');
            } catch (\Throwable) {
                return $stringValue;
            }
        }

        if (is_array($value)) {
            if (isset($value['date']) && is_string($value['date'])) {
                return $this->formatDateValue($value['date']);
            }

            if (isset($value['value']) && is_string($value['value'])) {
                return $this->formatDateValue($value['value']);
            }

            foreach ($value as $item) {
                $formatted = $this->formatDateValue($item);
                if ($formatted !== null) {
                    return $formatted;
                }
            }
        }

        return null;
    }
}
