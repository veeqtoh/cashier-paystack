<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Exceptions;

use Exception;

class IsNull extends Exception
{
    public function __construct()
    {
        $message = "Empty method not allowed.";
        parent::__construct($message);
    }
}