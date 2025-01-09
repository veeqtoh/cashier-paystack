<?php

declare(strict_types=1);

namespace Veeqtoh\CashierPaystack\Facades;

use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * class CashierPaystack
 * This class provides the facade for this library.
 *
 * @package Veeqtoh\CashierPaystack\Facades
 */
class CashierPaystack extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cashier-paystack';
    }
}
