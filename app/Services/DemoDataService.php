<?php

namespace App\Services;

class DemoDataService
{
    /**
     * Return sample products (copied from db.sql sample data).
     * @return array
     */
    public function getProducts(): array
    {
        return [
            ['product_id' => 'MT001', 'name' => 'Classic Milk Tea', 'category' => 'Milk Tea', 'price' => 45000, 'image_url' => 'https://images.promoshop.vn/products/mt001.jpg', 'status' => 'available'],
            ['product_id' => 'MT002', 'name' => 'Brown Sugar Milk Tea', 'category' => 'Milk Tea', 'price' => 49000, 'image_url' => 'https://images.promoshop.vn/products/mt002.jpg', 'status' => 'available'],
            ['product_id' => 'MT003', 'name' => 'Matcha Latte', 'category' => 'Milk Tea', 'price' => 52000, 'image_url' => 'https://images.promoshop.vn/products/mt003.jpg', 'status' => 'available'],
            ['product_id' => 'FT001', 'name' => 'Peach Fruit Tea', 'category' => 'Fruit Tea', 'price' => 42000, 'image_url' => 'https://images.promoshop.vn/products/ft001.jpg', 'status' => 'available'],
            ['product_id' => 'FT002', 'name' => 'Lychee Fruit Tea', 'category' => 'Fruit Tea', 'price' => 43000, 'image_url' => 'https://images.promoshop.vn/products/ft002.jpg', 'status' => 'available'],
            ['product_id' => 'LT001', 'name' => 'Vietnamese Iced Coffee', 'category' => 'Coffee', 'price' => 39000, 'image_url' => 'https://images.promoshop.vn/products/lt001.jpg', 'status' => 'available'],
        ];
    }

    /**
     * Return sample promotions.
     * @return array
     */
    public function getPromotions(): array
    {
        return [
            ['promo_id' => 'PROMO_PCT10', 'name' => 'Autumn Sale 10%', 'type' => 'percentage', 'start_date' => '2025-10-01', 'end_date' => '2025-10-31', 'description' => 'Giảm 10% cho toàn bộ thức uống Milk Tea', 'stackable' => true, 'min_order_amount' => 0, 'limit_per_customer' => 5, 'channels' => ['online']],
            ['promo_id' => 'PROMO_FIX5K', 'name' => 'Giảm ngay 5K', 'type' => 'fixed', 'start_date' => '2025-10-05', 'end_date' => '2025-11-05', 'description' => 'Giảm 5.000đ cho đơn hàng Fruit Tea', 'stackable' => true, 'min_order_amount' => 0, 'limit_per_customer' => 3, 'channels' => ['online','store']],
        ];
    }

    /**
     * Map of promo->products entries.
     * @return array
     */
    public function getPromoProducts(): array
    {
        return [
            'PROMO_PCT10' => [
                ['product_id' => 'MT001', 'discount_percent' => 10],
                ['product_id' => 'MT002', 'discount_percent' => 10],
                ['product_id' => 'MT003', 'discount_percent' => 10],
            ],
            'PROMO_FIX5K' => [
                ['product_id' => 'FT001', 'discount_amount' => 5000],
                ['product_id' => 'FT002', 'discount_amount' => 5000],
            ],
        ];
    }

    /**
     * Return a demo cart structure for customer (session-backed in controller).
     */
    public function getDemoCart(): array
    {
        return [
            ['item_id' => 'ITEM-0001', 'product_id' => 'MT001', 'name' => 'Classic Milk Tea', 'qty' => 2, 'price' => 45000, 'final_price' => 40500, 'line_total' => 81000],
            ['item_id' => 'ITEM-0002', 'product_id' => 'FT001', 'name' => 'Peach Fruit Tea', 'qty' => 1, 'price' => 42000, 'final_price' => 39000, 'line_total' => 39000],
        ];
    }

    /**
     * Return sample orders list.
     */
    public function getOrders(): array
    {
        return [
            ['order_id' => 'ORD-DEMO-001', 'order_date' => '2025-10-09T10:00:00Z', 'status' => 'pending_confirmation', 'payment_method' => 'cod', 'contact_name' => 'Nguyen Thanh', 'contact_phone' => '0987654321', 'shipping_address' => '123 Nguyen Trai, Q1, TP.HCM', 'total_amount' => 139000, 'discount_amount' => 9000, 'final_amount' => 130000, 'shipping_fee' => 0, 'items_count' => 3, 'promo_id' => 'PROMO_PCT10', 'items' => [
                ['line_id' => 'LINE-1', 'product_id' => 'MT001', 'name' => 'Classic Milk Tea', 'price' => 45000, 'final_price' => 40500, 'qty' => 2, 'image_url' => 'https://images.promoshop.vn/products/mt001.jpg', 'discount_value' => 9000, 'line_total' => 81000],
                ['line_id' => 'LINE-2', 'product_id' => 'FT001', 'name' => 'Peach Fruit Tea', 'price' => 42000, 'final_price' => 39000, 'qty' => 1, 'image_url' => 'https://images.promoshop.vn/products/ft001.jpg', 'discount_value' => 3000, 'line_total' => 39000],
            ]],
            ['order_id' => 'ORD25102401', 'order_date' => '2025-10-24T09:15:00Z', 'status' => 'completed', 'payment_method' => 'momo', 'contact_name' => 'Tran B', 'contact_phone' => '0900000001', 'shipping_address' => 'Somewhere', 'total_amount' => 95000, 'discount_amount' => 0, 'final_amount' => 95000, 'shipping_fee' => 0, 'items_count' => 2, 'promo_id' => null, 'items' => [
                ['line_id' => 'L1', 'product_id' => 'LT001', 'name' => 'Vietnamese Iced Coffee', 'price' => 39000, 'final_price' => 39000, 'qty' => 2, 'image_url' => 'https://images.promoshop.vn/products/lt001.jpg', 'discount_value' => 0, 'line_total' => 78000],
                ['line_id' => 'L2', 'product_id' => 'FT002', 'name' => 'Lychee Fruit Tea', 'price' => 43000, 'final_price' => 43000, 'qty' => 1, 'image_url' => 'https://images.promoshop.vn/products/ft002.jpg', 'discount_value' => 0, 'line_total' => 43000],
            ]],
        ];
    }
}
