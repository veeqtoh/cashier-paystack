<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * class CashierServiceProvider
 * This class registers the package within Laravel.
 *
 * @package Veeqtoh\Cashier\Providers
 */
class CashierServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Merge the package's configuration with the Laravel application's configuration.
        $this->mergeConfigFrom(__DIR__ . '/../../config/paystack-cashier.php', 'paystack-cashier');
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish the package's configuration file to the Laravel application.
        $this->publishes([
            __DIR__ . '/../../config/paystack-cashier.php' => config_path('paystack-cashier.php'),
        ], 'config');

        // Publish the package's migrations.
        // $this->publishes([
        //     __DIR__.'/../../database/migrations' => database_path('migrations'),
        // ], 'cashier-paystack-migrations');

        $publishesMigrationsMethod = method_exists($this, 'publishesMigrations')
        ? 'publishesMigrations'
        : 'publishes';

        $this->{$publishesMigrationsMethod}([
            __DIR__.'/../../database/migrations' => $this->app->databasePath('migrations'),
        ], 'cashier-paystack-migrations');

        $this->publishes([
            __DIR__.'/../../resources/views' => $this->app->resourcePath('views/vendor/paystack-cashier'),
        ], 'paystack-cashier-views');
    }
}
