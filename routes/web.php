<?php

use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ClientController::class, 'home'])->name('client.home');
Route::get('/cart', [ClientController::class, 'cart'])->name('client.cart');
Route::post('/cart/add', [ClientController::class, 'addToCart'])->name('client.cart.add');
Route::patch('/cart/update', [ClientController::class, 'updateCart'])->name('client.cart.update');
Route::delete('/cart/remove', [ClientController::class, 'removeFromCart'])->name('client.cart.remove');
Route::get('/checkout', [ClientController::class, 'checkout'])->name('client.checkout');
Route::post('/checkout', [ClientController::class, 'submitCheckout'])->name('client.checkout.submit');
Route::post('/cart/promo/apply', [ClientController::class, 'applyPromotion'])->name('client.cart.promo.apply');
Route::delete('/cart/promo/remove', [ClientController::class, 'removePromotion'])->name('client.cart.promo.remove');
Route::post('/cart/promo/enable', [ClientController::class, 'enablePromotion'])->name('client.cart.promo.enable');

Route::middleware('auth')->group(function () {
    Route::get('/orders', [ClientController::class, 'orders'])->name('client.orders');
    Route::post('/orders/reorder', [ClientController::class, 'reorderOrder'])->name('client.orders.reorder');
    Route::get('/orders/{order}/invoice', [ClientController::class, 'downloadInvoice'])->name('client.orders.invoice');
    Route::get('/orders/{order}/track', [ClientController::class, 'trackOrder'])->name('client.orders.track');
});

Route::get('/support', [ClientController::class, 'support'])->name('client.support');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')
    ->name('admin.')
    ->middleware('auth')
    ->group(function () {
        Route::redirect('/', '/admin/dashboard');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('promotions/conditions', [PromotionController::class, 'conditions'])
            ->name('promotions.conditions');
        Route::resource('promotions', PromotionController::class)
            ->except(['show']);

        Route::resource('coupons', CouponController::class)->except(['show']);

        Route::post('promotions/{promotion}/tiers', [PromotionController::class, 'storeTier'])
            ->name('promotions.tiers.store');
        Route::put('promotions/{promotion}/tiers/{tier}', [PromotionController::class, 'updateTier'])
            ->name('promotions.tiers.update');
        Route::delete('promotions/{promotion}/tiers/{tier}', [PromotionController::class, 'destroyTier'])
            ->name('promotions.tiers.destroy');

        Route::resource('orders', OrderController::class)->only(['index', 'show']);

        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
    });
