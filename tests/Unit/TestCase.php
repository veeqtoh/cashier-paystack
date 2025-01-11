<?php

namespace Veeqtoh\Cashier\Tests\Unit;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Unicodeveloper\Paystack\Facades\Paystack;
use Unicodeveloper\Paystack\PaystackServiceProvider;
use Veeqtoh\Cashier\Facades\Cashier;
use Veeqtoh\Cashier\Providers\CashierServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use LazilyRefreshDatabase;
    use WithWorkbench;

    /**
     * Load package service provider.
     *
     * @param  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            CashierServiceProvider::class,
            PaystackServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Cashier'  => Cashier::class,
            'Paystack' => Paystack::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('paystack-cashier.publicKey', 'your_public_key');
        $app['config']->set('paystack-cashier.secretKey', 'your_secret_key');
        $app['config']->set('paystack-cashier.paymentUrl', 'https://api.paystack.co');
        $app['config']->set('paystack-cashier.merchantEmail', 'merchant@example.com');
    }
}