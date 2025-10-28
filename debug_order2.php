<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$service = $app->make(App\Services\CassandraDataService::class);
$result = $service->createOrder([
    'user_id' => 'USR-TINKER2',
    'customer_name' => 'Khach Hang',
    'customer_phone' => '0987',
    'shipping_address' => 'Dia chi',
    'note' => null,
    'items' => [
        ['product_id' => 'CF002', 'name' => 'Latte caramel nóng', 'price' => 59000, 'quantity' => 1],
    ],
    'summary' => [
        'subtotal' => 59000,
        'discount' => 0,
        'final_total' => 74000,
        'final_shipping_fee' => 15000,
        'applied_promotions' => [],
        'gifts' => [],
    ],
]);
var_dump($result);
