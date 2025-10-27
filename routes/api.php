<?php

use App\Http\Controllers\ApiProxyController;
use Illuminate\Support\Facades\Route;

Route::any('/', [ApiProxyController::class, 'handle']);

Route::any('{path}', [ApiProxyController::class, 'handle'])
    ->where('path', '.*');
