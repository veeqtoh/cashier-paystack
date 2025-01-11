<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier;

use Carbon\Carbon;
use Veeqtoh\Cashier\Exceptions\FailedToCreateSubscription;
use Veeqtoh\Cashier\Models\Subscription;
use Veeqtoh\Cashier\Services\PaystackService;

class SubscriptionBuilder
{
    /**
     * The number of trial days to apply to the subscription.
     *
     * @var int|null
     */
    protected $trialDays;

    /**
     * Indicates that the trial should end immediately.
     *
     * @var bool
     */
    protected $skipTrial = false;

    /**
     * The coupon code being applied to the customer.
     *
     * @var string|null
     */
    protected $coupon;

    /**
     * Create a new subscription builder instance.
     *
     * @param mixed  $owner The model that is subscribing.
     * @param string $name  The name of the subscription.
     * @param string $plan  The name of the plan being subscribed to.
     *
     * @return void
     */
    public function __construct(protected mixed $owner, protected string $name, protected string $plan)
    {
        //
    }

    /**
     * Specify the ending date of the trial.
     */
    public function trialDays(int $trialDays): static
    {
        $this->trialDays = $trialDays;

        return $this;
    }

    /**
     * Force the trial to end immediately.
     */
    public function skipTrial(): static
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * Add a new Paystack subscription to the model.
     */
    public function add(array $options = []): Subscription
    {
        if ($this->skipTrial) {
            $trialEndsAt = null;
        } else {
            $trialEndsAt = $this->trialDays ? Carbon::now()->addDays($this->trialDays) : null;
        }

        return $this->owner->subscriptions()->create([
            'name'          => $this->name,
            'paystack_id'   => $options['id'],
            'paystack_code' => $options['subscription_code'],
            'paystack_plan' => $this->plan,
            'quantity'      => 1,
            'trial_ends_at' => $trialEndsAt,
            'ends_at'       => null,
        ]);
    }

    /**
     * Charge for a Paystack subscription.
     */
    public function charge(array $options = []): mixed
    {
        $options = array_merge([
            'plan' => $this->plan
        ], $options);

        return $this->owner->charge(100, $options);
    }

    /**
     * Create a new Paystack subscription.
     */
    public function create(?string $token = null, array $options = []): Subscription
    {
        $payload = $this->getSubscriptionPayload($this->getPaystackCustomer(), $options);

        // Set the desired authorization you wish to use for this subscription here.
        // If this is not supplied, the customer's most recent authorization would be used.

        if (isset($token)) {
            $payload['authorization'] = $token;
        }

        $subscription = PaystackService::createSubscription($payload);

        if (! $subscription['status']) {
            throw new FailedToCreateSubscription($subscription);
        }

        return $this->add($subscription['data']);
    }

     /**
     * Get the subscription payload data for Paystack.
     */
    protected function getSubscriptionPayload(array $customer): array
    {
        if ($this->skipTrial) {
            $startDate = Carbon::now();
        } else {
            $startDate =  $this->trialDays ? Carbon::now()->addDays($this->trialDays) : Carbon::now();
        }

        return [
            'customer'   => $customer['customer_code'], // Customer email or code
            'plan'       => $this->plan,
            'start_date' => $startDate->format('c'),
        ];
    }

    /**
     * Get the Paystack customer instance for the current user and token.
     */
    protected function getPaystackCustomer(array $options = []): mixed
    {
        if (!$this->owner->paystack_id) {
            $customer = $this->owner->createAsPaystackCustomer($options);
        } else {
            $customer = $this->owner->asPaystackCustomer();
        }

        return $customer;
    }
}
