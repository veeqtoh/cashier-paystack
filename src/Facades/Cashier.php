<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Facades;

use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * class Cashier
 * This class provides the facade for this library.
 *
 * @package Veeqtoh\Cashier\Facades
 */
class Cashier extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cashier';
    }
}
