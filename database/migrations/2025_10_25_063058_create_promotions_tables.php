<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('promo_id')->unique();
            $table->string('name');
            $table->string('type')->default('percentage');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('min_order_amount')->default(0);
            $table->unsignedInteger('limit_per_customer')->default(0);
            $table->unsignedInteger('global_quota')->nullable();
            $table->json('channels')->nullable();
            $table->boolean('stackable')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_id')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->string('image_url')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('promotion_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->unsignedInteger('discount_amount')->nullable();
            $table->timestamps();

            $table->unique(['promotion_id', 'product_id']);
        });

        Schema::create('promotion_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->string('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->unsignedInteger('total_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('final_amount')->default(0);
            $table->date('order_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('promotion_codes');
        Schema::dropIfExists('promotion_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('promotions');
    }
};
