<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier;

use Veeqtoh\Cashier\Cashier;
use Veeqtoh\Cashier\Concerns\PerformsCharges;

trait Billable
{
    use PerformsCharges;

    /**
     * Get the Paystack supported currency used by the entity.
     */
    public function preferredCurrency(): string
    {
        return Cashier::usesCurrency();
    }
}