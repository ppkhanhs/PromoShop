<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Auth::provider('cassandra', function ($app, array $config) {
            return new \App\Auth\CassandraUserProvider(
                $app->make(\App\Services\CassandraDataService::class)
            );
        });
    }
}
