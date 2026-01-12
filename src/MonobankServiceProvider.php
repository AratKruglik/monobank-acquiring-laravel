<?php

namespace AratKruglik\Monobank;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Route;
use AratKruglik\Monobank\Http\Controllers\MonobankWebhookController;

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

            // Register commands here later
            // $this->commands([]);
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
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/monobank.php', 'monobank');

        // Register the main class to use with the Facade
        $this->app->singleton('monobank', function () {
            return new Monobank(config('monobank'));
        });

        $this->app->singleton(\AratKruglik\Monobank\Services\PubKeyProvider::class, function ($app) {
            return new \AratKruglik\Monobank\Services\PubKeyProvider(new \AratKruglik\Monobank\Client(config('monobank')));
        });
    }
}
