<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$service = $app->make(App\Services\CassandraDataService::class);
$result = $service->createOrder([
    'user_id' => 'USR-TINKER',
    'customer_name' => 'Tester',
    'customer_phone' => '0123',
    'shipping_address' => 'Addr',
    'note' => null,
    'items' => [
        ['product_id' => 'MT002', 'name' => 'Matcha', 'price' => 52000, 'quantity' => 2],
    ],
    'summary' => [
        'subtotal' => 104000,
        'discount' => 15000,
        'final_total' => 89000,
        'final_shipping_fee' => 0,
        'applied_promotions' => [
            [
                'promotion' => ['promo_id' => 'SPRING2025', 'title' => 'Spring'],
                'tier' => ['tier_level' => 1, 'label' => 'Giam 10%'],
                'discount' => 15000,
                'shipping_discount' => 0,
            ],
        ],
        'gifts' => [],
    ],
]);
var_dump($result);
