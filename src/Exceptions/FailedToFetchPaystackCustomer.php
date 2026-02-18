<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Exceptions;

use Exception;

class FailedToFetchPaystackCustomer extends Exception
{
    public function __construct($response)
    {
        $message = "Unable to fetch Paystack customer: {$response['message']}";
        parent::__construct($message);
    }
}
