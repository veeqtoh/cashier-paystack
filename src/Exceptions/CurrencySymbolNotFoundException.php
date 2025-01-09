<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Exceptions;

use Exception;

class CurrencySymbolNotFoundException extends Exception
{
    public function __construct($currency)
    {
        $message = "Unable to guess symbol for currency: {$currency}. Please explicitly specify it.";
        parent::__construct($message);
    }
}
