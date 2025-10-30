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
        Schema::create('product_voucher', function (Blueprint $table) {
            $table->id();
            $table->string('product_id');
            $table->string('voucher_code');
            $table->string('promo_id')->nullable();
            $table->string('discount_type')->default('amount');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'voucher_code']);
            $table->index('voucher_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_voucher');
    }
};
