<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Concerns;

use Veeqtoh\Cashier\Exceptions\IncompletePayment;
use Veeqtoh\Cashier\Services\PaystackService;

/**
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
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
     * Get the pool to use based on the type of prefix hash.
     */
    private static function getPool(string $type = 'alnum'): string
    {
        switch ($type) {
            case 'alnum':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alpha':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'hexdec':
                $pool = '0123456789abcdef';
                break;
            case 'numeric':
                $pool = '0123456789';
                break;
            case 'nozero':
                $pool = '123456789';
                break;
            case 'distinct':
                $pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
                break;
            default:
                $pool = (string) $type;
                break;
        }

        return $pool;
    }

    /**
     * Generate a random secure crypt figure.
     */
    private static function secureCrypt(int $min, int $max): int
    {
        $range = $max - $min;

        if ($range <= 0) {
            return $min; // not so random...
        }

        // Use PHP's cryptographically secure random_int to generate an integer
        // in the half-open interval [min, max), matching the previous behavior.
        return random_int($min, $max - 1);
    }

    /**
     * Generate a hashed token for use as a unique identifier for a charge or subscription.
     */
    protected static function getHashedToken(int $length = 25): string
    {
        $token = "";
        $poolLength = strlen(static::getPool());

        for ($i = 0; $i < $length; $i++) {
            $token .= static::getPool()[static::secureCrypt(0, $poolLength)];
        }

        return $token;
    }
}