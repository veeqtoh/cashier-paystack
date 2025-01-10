<?php

namespace Veeqtoh\Cashier\Tests\Unit;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
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
        return [CashierServiceProvider::class];
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
            'Cashier' => Cashier::class,
        ];
    }
}