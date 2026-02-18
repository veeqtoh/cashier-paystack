<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Concerns;

use Veeqtoh\Cashier\Exceptions\IncompletePayment;
use Veeqtoh\Cashier\Services\PaystackService;

trait PerformsCharges
{
    /**
     * Make a "one off" or "recurring" charge on the customer for the given amount or plan respectively.
     *
     * @throws IncompletePayment
     */
    public function charge(int $amount, array $options = []): mixed
    {
        $options = array_merge([
            'currency'  => $this->preferredCurrency(),
            'reference' => self::getHashedToken(),
        ], $options);

        $options['email']  = $this->email;
        $options['amount'] = intval($amount);

        if ( array_key_exists('authorization_code', $options) ) {
            $response = PaystackService::chargeAuthorization($options);
        } elseif (array_key_exists('card', $options) || array_key_exists('bank', $options)) {
            $response = PaystackService::charge($options);
        } else {
            $response = PaystackService::makePaymentRequest($options);
        }

        if (! $response['status']) {
            throw new IncompletePayment($response);
        }

        return $response;
    }

    /**
     * Refund a customer for a charge.
     */
    public function refund(string $transaction, array $options = []): mixed
    {
        $options['transaction'] = $transaction;
        $response = PaystackService::refund($options);

        return $response;
    }

    /**
     * Generate a hashed token for use as a unique identifier for a charge or subscription.
     */
    public static function getHashedToken(int $length = 25): string
    {
        $token = "";
        $max   = strlen(static::getPool());

        for ($i = 0; $i < $length; $i++) {
            $token .= static::getPool()[static::secureCrypt(0, $max)];
        }

        return $token;
    }
}