<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Exceptions;

use Exception;

class NotPaystackCustomer extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
