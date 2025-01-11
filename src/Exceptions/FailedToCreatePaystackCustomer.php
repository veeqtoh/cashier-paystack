<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Exceptions;

use Exception;

class FailedToCreatePaystackCustomer extends Exception
{
    public function __construct($response)
    {
        $message = "Unable to create Paystack customer: {$response['message']}";
        parent::__construct($message);
    }
}
