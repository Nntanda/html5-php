<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LoanService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoanService::class, function ($app) {
            return new LoanService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
