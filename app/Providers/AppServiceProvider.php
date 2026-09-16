<?php

namespace App\Providers;

use App\Contracts\MediaProvider;
use App\MediaProviders\MediaProviderManager;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MediaProviderManager::class, function ($app) {
            return new MediaProviderManager(config('calls.media'));
        });

        $this->app->bind(MediaProvider::class, function ($app) {
            return $app->make(MediaProviderManager::class)->driver();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
