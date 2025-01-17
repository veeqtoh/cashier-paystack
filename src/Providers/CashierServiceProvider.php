<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Veeqtoh\Cashier\Cashier;

/**
 * class CashierServiceProvider
 * This class registers the package within Laravel.
 *
 * @package Veeqtoh\Cashier\Providers
 */
class CashierServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerResources();
        $this->registerPublishing();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->configure();
    }

    /**
     * Setup the configuration for Paystack Cashier.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/paystack.php', 'paystack'
        );
    }
    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        if (Cashier::$registersRoutes) {
            Route::group([
                'prefix'    => config('paystack.path'),
                'namespace' => 'Veeqtoh\Cashier\Http\Controllers',
                'as'        => 'paystack.',
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
            });
        }
    }

    /**
     * Register the package resources.
     *
     * @return void
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'cashier-paystack');
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/paystack.php' => $this->app->configPath('paystack.php'),
            ], 'cashier-paystack-config');

            $publishesMigrationsMethod = method_exists($this, 'publishesMigrations')
                ? 'publishesMigrations'
                : 'publishes';

            $this->{$publishesMigrationsMethod}([
                __DIR__.'/../../database/migrations' => $this->app->databasePath('migrations'),
            ], 'cashier-paystack-migrations');

            $this->publishes([
                __DIR__.'/../../resources/views' => $this->app->resourcePath('views/vendor/cashier-paystack'),
            ], 'cashier-paystack-views');
        }
    }
}
