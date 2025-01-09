<?php
declare(strict_types=1);

namespace Veeqtoh\CashierPaystack\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * class CashierPaystackProvider
 * This class registers the package within Laravel.
 *
 * @package Veeqtoh\CashierPaystack\Providers
 */
class CashierPaystackProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Merge the package's configuration with the Laravel application's configuration.
        $this->mergeConfigFrom(__DIR__ . '/../../config/paystack.php', 'paystack');
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
            __DIR__ . '/../../config/paystack.php' => config_path('paystack.php'),
        ], 'config');

        // Publish the package's migrations.
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'cashier-paystack-migrations');
    }
}