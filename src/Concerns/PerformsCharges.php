<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Concerns;

use Unicodeveloper\Paystack\Facades\Paystack;
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
            'reference' => Paystack::genTranxRef(),
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
}