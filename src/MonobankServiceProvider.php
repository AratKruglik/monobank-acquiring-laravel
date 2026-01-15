<?php

namespace AratKruglik\Monobank;

use AratKruglik\Monobank\Contracts\ClientInterface;
use AratKruglik\Monobank\Http\Controllers\MonobankWebhookController;
use AratKruglik\Monobank\Services\PubKeyProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MonobankServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/monobank.php' => config_path('monobank.php'),
            ], 'monobank-config');
        }

        Route::macro('monobankWebhook', function (string $url) {
            return Route::post($url, MonobankWebhookController::class)->name('monobank.webhook');
        });
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/monobank.php', 'monobank');

        $this->app->singleton(ClientInterface::class, function ($app) {
            return new Client(config('monobank'));
        });

        $this->app->singleton('monobank', function ($app) {
            return new Monobank(
                config('monobank'),
                $app->make(ClientInterface::class)
            );
        });

        $this->app->singleton(PubKeyProvider::class, function ($app) {
            return new PubKeyProvider($app->make(ClientInterface::class));
        });
    }
}
