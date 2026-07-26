<?php

namespace App\Providers;

use App\Contracts\ShippingProviderInterface;
use App\Services\Providers\MockShippingProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
     
        $this->app->bind(
            ShippingProviderInterface::class,
            MockShippingProvider::class
        );
    }
    

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
