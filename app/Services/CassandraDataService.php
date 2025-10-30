<?php

namespace App\Services;

use App\Models\Cassandra\Order;
use App\Models\Cassandra\Product;
use App\Models\Cassandra\Promotion;
use App\Models\Cassandra\PromoCode;
use App\Models\Cassandra\PromotionLog;
use App\Models\Cassandra\PromotionTier;
use App\Models\Cassandra\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Cassandra API data facade.
 *
 * @method array<int, \App\Models\Cassandra\PromoCode> fetchPromoCodes(?string  = null)
 */
class CassandraDataService
{
    public function __construct(protected CassandraApiService $api)
    {
    }

    public function authenticate(array $credentials): ?array
    {
        $res = $this->api->request('POST', 'api/v1/auth/login', ['json' => $credentials]);
        if ($res['status'] !== 200) {
            return null;
        }

        return Arr::get($res, 'json.data');
    }

    public function registerUser(array $payload): ?array
    {
        $res = $this->api->request('POST', 'api/v1/auth/register', ['json' => $payload]);
        if (!in_array($res['status'], [200, 201], true)) {
            return null;
        }

        return Arr::get($res, 'json.data');
    }

    /**
     * @return array<int, User>
     */
    public function fetchUsers(): array
    {
        $res = $this->api->request('GET', 'api/v1/users');
        $data = Arr::get($res, 'json.data', []);

        return collect($data)
            ->map(fn ($row) => User::from((array) $row))
            ->all();
    }

    public function fetchUserById(string $userId): ?User
    {
        $res = $this->api->request('GET', "api/v1/users/{$userId}");
        if ($res['status'] !== 200) {
            return null;
        }

        return User::from((array) Arr::get($res, 'json.data', []));
    }

    public function fetchUserByEmail(string $email): ?User
    {
        $res = $this->api->request('GET', 'api/v1/users/lookup', [
            'query' => ['email' => $email],
        ]);

        if ($res['status'] !== 200) {
            return null;
        }

        return User::from((array) Arr::get($res, 'json.data', []));
    }

    public function saveUser(array $payload, ?string $userId = null): bool
    {
        $endpoint = 'api/v1/users';
        $method = 'POST';

        if ($userId) {
            $endpoint .= '/' . $userId;
            $method = 'PUT';
        }

        $res = $this->api->request($method, $endpoint, ['json' => $payload]);

        return in_array($res['status'], [200, 201], true);
    }

    public function deleteUser(string $userId): bool
    {
        $res = $this->api->request('DELETE', "api/v1/users/{$userId}");

        return $res['status'] === 204;
    }

    /**
     * @return array<int, Product>
     */
    public function fetchProducts(): array
    {
        $res = $this->api->request('GET', 'api/v1/products');

        $data = Arr::get($res, 'json.data', []);
        return collect($data)
            ->map(function ($row) {
                $payload = (array) $row;
                $payload['price'] = (int) ($payload['price'] ?? 0);
                $payload['stock'] = (int) ($payload['stock'] ?? 0);
                return Product::from($payload);
            })
            ->all();
    }

    public function fetchProduct(string $productId): ?Product
    {
        $res = $this->api->request('GET', "api/v1/products/{$productId}");
        if ($res['status'] !== 200) {
            return null;
        }

        $payload = (array) Arr::get($res, 'json.data', []);
        $payload['price'] = (int) ($payload['price'] ?? 0);
        $payload['stock'] = (int) ($payload['stock'] ?? 0);

        return Product::from($payload);
    }

    public function saveProduct(array $payload, ?string $productId = null): bool
    {
        $endpoint = 'api/v1/products';
        $method = 'POST';
        if ($productId) {
            $endpoint .= '/' . $productId;
            $method = 'PUT';
        }

        $res = $this->api->request($method, $endpoint, ['json' => $payload]);

        return in_array($res['status'], [200, 201], true);
    }

    public function deleteProduct(string $productId): bool
    {
        $res = $this->api->request('DELETE', "api/v1/products/{$productId}");

        return $res['status'] === 204;
    }

    /**
     * @return array<int, Promotion>
     */
    public function fetchPromotions(bool $withTiers = false): array
    {
        $res = $this->api->request('GET', 'api/v1/promotions', [
            'query' => $withTiers ? ['with_tiers' => 1] : [],
        ]);

        $data = Arr::get($res, 'json.data', []);
        return collect($data)
            ->map(function ($row) use ($withTiers) {
                $payload = (array) $row;
                $payload['min_order'] = isset($payload['min_order']) ? (int) $payload['min_order'] : 0;
                if (array_key_exists('max_discount_amount', $payload)) {
                    $payload['max_discount_amount'] = $payload['max_discount_amount'] === null
                        ? null
                        : (int) $payload['max_discount_amount'];
                }
                $payload['start_date'] = $this->normalizeDate($payload['start_date'] ?? null);
                $payload['end_date'] = $this->normalizeDate($payload['end_date'] ?? null);

                $promotion = Promotion::from($payload);
                if ($withTiers && isset($row['tiers'])) {
                    $promotion->set('tiers', collect($row['tiers'])
                        ->map(function ($tier) {
                            $tierPayload = (array) $tier;
                            $tierPayload['tier_level'] = (int) ($tierPayload['tier_level'] ?? 0);
                            $tierPayload['min_value'] = (int) ($tierPayload['min_value'] ?? 0);
                            $tierPayload['discount_percent'] = isset($tierPayload['discount_percent'])
                                ? (int) $tierPayload['discount_percent']
                                : null;
                            $tierPayload['discount_amount'] = isset($tierPayload['discount_amount'])
                                ? (int) $tierPayload['discount_amount']
                                : null;
                            $tierPayload['freeship'] = (bool) ($tierPayload['freeship'] ?? false);
                            $tierPayload['gift_quantity'] = isset($tierPayload['gift_quantity'])
                                ? (int) $tierPayload['gift_quantity']
                                : null;
                            return PromotionTier::from($tierPayload)->toArray();
                        })
                        ->all());
                }
                return $promotion;
            })
            ->all();
    }

    public function fetchPromotion(string $promoId, bool $withTiers = true): ?Promotion
    {
        $res = $this->api->request('GET', "api/v1/promotions/{$promoId}", [
            'query' => $withTiers ? ['with_tiers' => 1] : [],
        ]);
        if ($res['status'] !== 200) {
            return null;
        }

        $data = Arr::get($res, 'json.data', []);
        $payload = (array) $data;
        $payload['min_order'] = isset($payload['min_order']) ? (int) $payload['min_order'] : 0;
        if (array_key_exists('max_discount_amount', $payload)) {
            $payload['max_discount_amount'] = $payload['max_discount_amount'] === null
                ? null
                : (int) $payload['max_discount_amount'];
        }
        $payload['start_date'] = $this->normalizeDate($payload['start_date'] ?? null);
        $payload['end_date'] = $this->normalizeDate($payload['end_date'] ?? null);

        $promotion = Promotion::from($payload);

        if ($withTiers && isset($data['tiers'])) {
            $promotion->set('tiers', collect($data['tiers'])
                ->map(function ($tier) {
                    $payload = (array) $tier;
                    $payload['tier_level'] = (int) ($payload['tier_level'] ?? 0);
                    $payload['min_value'] = (int) ($payload['min_value'] ?? 0);
                    $payload['discount_percent'] = isset($payload['discount_percent'])
                        ? (int) $payload['discount_percent']
                        : null;
                    $payload['discount_amount'] = isset($payload['discount_amount'])
                        ? (int) $payload['discount_amount']
                        : null;
                    $payload['freeship'] = (bool) ($payload['freeship'] ?? false);
                    $payload['gift_quantity'] = isset($payload['gift_quantity'])
                        ? (int) $payload['gift_quantity']
                        : null;
                    return PromotionTier::from($payload)->toArray();
                })
                ->all());
        }

        return $promotion;
    }

    public function savePromotion(array $payload, ?string $promoId = null): array
    {
        $endpoint = 'api/v1/promotions';
        $method = 'POST';

        if ($promoId) {
            $endpoint .= '/' . $promoId;
            $method = 'PUT';
        }

        $res = $this->api->request($method, $endpoint, ['json' => $payload]);

        if (!in_array($res['status'], [200, 201], true)) {
            throw new RuntimeException('Không thể lưu khuyến mãi.');
        }

        return (array) Arr::get($res, 'json.data', []);
    }

    public function deletePromotion(string $promoId): bool
    {
        $res = $this->api->request('DELETE', "api/v1/promotions/{$promoId}");

        return $res['status'] === 204;
    }

    /**
     * @return array<int, PromotionTier>
     */
    public function fetchPromotionTiers(string $promoId): array
    {
        $res = $this->api->request('GET', "api/v1/promotions/{$promoId}/tiers");
        $data = Arr::get($res, 'json.data', []);

        return collect($data)
            ->map(function ($row) {
                $payload = (array) $row;
                $payload['tier_level'] = (int) ($payload['tier_level'] ?? 0);
                $payload['min_value'] = (int) ($payload['min_value'] ?? 0);
                $payload['discount_percent'] = isset($payload['discount_percent'])
                    ? (int) $payload['discount_percent']
                    : null;
                $payload['discount_amount'] = isset($payload['discount_amount'])
                    ? (int) $payload['discount_amount']
                    : null;
                $payload['freeship'] = (bool) ($payload['freeship'] ?? false);
                $payload['gift_quantity'] = isset($payload['gift_quantity'])
                    ? (int) $payload['gift_quantity']
                    : null;
                return PromotionTier::from($payload);
            })
            ->all();
    }

    public function savePromotionTier(string $promoId, array $payload, ?int $tierLevel = null): array
    {
        $endpoint = "api/v1/promotions/{$promoId}/tiers";
        $method = 'POST';
        if ($tierLevel !== null) {
            $endpoint .= '/' . $tierLevel;
            $method = 'PUT';
        }

        $res = $this->api->request($method, $endpoint, ['json' => $payload]);
        if (!in_array($res['status'], [200, 201], true)) {
            throw new RuntimeException('Không thể lưu tầng khuyến mãi.');
        }

        return (array) Arr::get($res, 'json.data', []);
    }

    public function deletePromotionTier(string $promoId, int $tierLevel): bool
    {
        $res = $this->api->request('DELETE', "api/v1/promotions/{$promoId}/tiers/{$tierLevel}");

        return $res['status'] === 204;
    }

    /**
     * @return array<int, Order>
     */
    public function fetchOrders(?string $userId = null): array
    {
        $query = [];
        if ($userId) {
            $query['user_id'] = $userId;
        }

        $res = $this->api->request('GET', 'api/v1/orders', ['query' => $query]);
        $data = Arr::get($res, 'json.data', []);

        return collect($data)->map(function ($row) {
            $payload = (array) $row;

            foreach (['total', 'discount', 'final_amount', 'shipping_fee'] as $key) {
                if (isset($payload[$key])) {
                    $payload[$key] = (int) $payload[$key];
                }
            }

            if (isset($payload['items'])) {
                if (is_string($payload['items'])) {
                    $decoded = json_decode($payload['items'], true);
                    $payload['items'] = is_array($decoded) ? $decoded : [];
                } elseif (!is_array($payload['items'])) {
                    $payload['items'] = [];
                }
            }

            foreach (['promotion_snapshot', 'gifts'] as $key) {
                if (!array_key_exists($key, $payload)) {
                    continue;
                }
                $value = $payload[$key];
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $payload[$key] = is_array($decoded) ? $decoded : [];
                } elseif (!is_array($value)) {
                    $payload[$key] = [];
                }
            }

            return Order::from($payload);
        })->all();
    }

    public function createOrder(array $payload): ?array
    {
        $res = $this->api->request('POST', 'api/v1/orders', ['json' => $payload]);
        if (!in_array($res['status'], [200, 201], true)) {
            logger()->error('Failed to create order via Cassandra API', [
                'status' => $res['status'],
                'body' => $res['body'],
                'response' => $res['json'],
            ]);
            return null;
        }

        return (array) Arr::get($res, 'json.data', []);
    }

    public function confirmOrder(string $orderId, array $payload = []): bool
    {
        $options = [];
        if (!empty($payload)) {
            $options['json'] = $payload;
        }

        $res = $this->api->request('POST', "api/v1/orders/{$orderId}/confirm", $options);
        if ($res['status'] !== 200) {
            logger()->error('Failed to confirm order via Cassandra API', [
                'order_id' => $orderId,
                'status' => $res['status'],
                'body' => $res['body'],
                'response' => $res['json'],
            ]);
            return false;
        }

        return true;
    }

    public function cancelOrder(string $orderId, ?string $note = null): bool
    {
        $payload = ['status' => 'cancelled'];
        if ($note !== null && $note !== '') {
            $payload['admin_note'] = $note;
        }

        return $this->confirmOrder($orderId, $payload);
    }

    /**
     * @return array<int, PromotionLog>
     */
    public function fetchPromotionLogs(?string $promoId = null): array
    {
        $query = [];
        if ($promoId) {
            $query['promo_id'] = $promoId;
        }

        $res = $this->api->request('GET', 'api/v1/promotion-logs', ['query' => $query]);
        $data = Arr::get($res, 'json.data', []);

        return collect($data)
            ->map(function ($row) {
                $payload = (array) $row;
                if (isset($payload['discount_amount'])) {
                    $payload['discount_amount'] = (int) $payload['discount_amount'];
                }
                $payload['freeship'] = (bool) ($payload['freeship'] ?? false);
                return PromotionLog::from($payload);
            })
            ->all();
    }

    public function fetchDashboardStats(): array
    {
        $res = $this->api->request('GET', 'api/v1/dashboard');
        return (array) Arr::get($res, 'json.data', []);
    }

    /**
     * @return array<int, PromoCode>
     */
    public function fetchPromoCodes(?string $promoId = null): array
    {
        $options = [];
        if ($promoId) {
            $options['query'] = ['promo_id' => $promoId];
        }

        $res = $this->api->request('GET', 'api/v1/promo-codes', $options);
        $data = Arr::get($res, 'json.data', []);

        return collect($data)
            ->map(fn ($row) => PromoCode::from((array) $row))
            ->all();
    }

    public function fetchPromoCodeByCode(string $code): ?PromoCode
    {
        $res = $this->api->request('GET', "api/v1/promo-codes/{$code}");
        if ($res['status'] !== 200) {
            return null;
        }

        return PromoCode::from((array) Arr::get($res, 'json.data', []));
    }

    public function createPromoCode(string $promoId, array $payload): ?PromoCode
    {
        $res = $this->api->request('POST', "api/v1/promotions/{$promoId}/codes", [
            'json' => $payload,
        ]);

        if (!in_array($res['status'], [200, 201], true)) {
            logger()->warning('Failed to create promo code', [
                'promo_id' => $promoId,
                'status' => $res['status'],
                'body' => $res['body'],
                'response' => $res['json'],
            ]);
            return null;
        }

        $created = Arr::get($res, 'json.data', []);
        $first = (array) Arr::first($created);

        return $first ? PromoCode::from($first) : null;
    }

    public function updatePromoCode(string $promoId, string $code, array $payload): ?PromoCode
    {
        $res = $this->api->request('PUT', "api/v1/promotions/{$promoId}/codes/{$code}", [
            'json' => $payload,
        ]);

        if ($res['status'] !== 200) {
            logger()->warning('Failed to update promo code', [
                'promo_id' => $promoId,
                'code' => $code,
                'status' => $res['status'],
                'body' => $res['body'],
                'response' => $res['json'],
            ]);
            return null;
        }

        return PromoCode::from((array) Arr::get($res, 'json.data', []));
    }

    public function deletePromoCode(string $promoId, string $code): bool
    {
        $res = $this->api->request('DELETE', "api/v1/promotions/{$promoId}/codes/{$code}");

        return $res['status'] === 204;
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_array($value)) {
            if (isset($value['year'], $value['month'], $value['day'])) {
                return sprintf('%04d-%02d-%02d', (int) $value['year'], (int) $value['month'], (int) $value['day']);
            }

            if (isset($value['date'])) {
                return $this->normalizeDate($value['date']);
            }

            $first = reset($value);
            return $this->normalizeDate($first);
        }

        return (string) $value;
    }
}
