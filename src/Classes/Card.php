<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Classes;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Veeqtoh\Cashier\Exceptions\ReusablePaymentMethodException;
use Veeqtoh\Cashier\Services\PaystackService;


class Card
{
    /**
     * Create a new card instance.
     *
     * @param Model $owner The Paystack model instance.
     * @param object $card The Paystack card instance.
     *
     * @return void
     */
    public function __construct(protected Model $owner, protected object $card)
    {
        //
    }

    /**
     * Check if the payment method has sufficient funds for the payment.
     *
     * @throws ReusablePaymentMethodException
     */
    public function check(mixed $amount): mixed
    {
        try {
            $data = [
                'email'              => $this->owner->getAttribute('email'),
                'amount'             => $amount,
                'authorization_code' => $this->card->authorization_code,
            ];

            if ($this->card->reusable) {
                return PaystackService::checkAuthorization($data);
            }

            throw new ReusablePaymentMethodException('Payment Method is no longer reusable.');
        } catch (Exception $e) {
            // Log the exception or handle it as needed.
            throw new Exception('An error occurred while checking payment method: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete the payment Method.
     */
    public function delete(): mixed
    {
        return PaystackService::deactivateAuthorization($this->card->authorization_code);
    }

    /**
     * Get the Paystack payment authorization object.
     */
    public function asPaystackAuthorization(): object
    {
        return $this->card;
    }

    /**
     * Dynamically get values from the Paystack card.
     */
    public function __get(string $key): mixed
    {
        return $this->card->{$key};
    }
}