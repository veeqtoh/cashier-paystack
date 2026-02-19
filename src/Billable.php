<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier;

use Veeqtoh\Cashier\Cashier;
use Veeqtoh\Cashier\Concerns\ManagesCards;
use Veeqtoh\Cashier\Concerns\ManagesInvoices;
use Veeqtoh\Cashier\Concerns\ManagesSubscriptions;
use Veeqtoh\Cashier\Concerns\PerformsCharges;

/**
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait Billable
{
    use PerformsCharges;
    use ManagesInvoices;
    use ManagesSubscriptions;
    use ManagesCards;

    /**
     * Get the Paystack supported currency used by the entity.
     */
    public function preferredCurrency(): string
    {
        return Cashier::usesCurrency();
    }
}