<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Exceptions;

use Exception;

class FailedToCreateSubscription extends Exception
{
    public function __construct($subscription)
    {
        $message = "Paystack failed to create subscription: {$subscription['message']}";
        parent::__construct($message);
    }
}