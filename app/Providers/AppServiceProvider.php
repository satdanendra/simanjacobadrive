<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GoogleDriveService;
use App\Services\LaporanWordService;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleDriveService::class, function ($app) {
            return new GoogleDriveService();
        });

        $this->app->singleton(LaporanWordService::class, function ($app) {
            return new LaporanWordService();
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