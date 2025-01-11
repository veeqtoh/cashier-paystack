<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Exceptions;

use Exception;

class IncompletePayment extends Exception
{
    public function __construct($response)
    {
        $message = "Paystack was unable to perform a charge: {$response->message}";
        parent::__construct($message);
    }
}