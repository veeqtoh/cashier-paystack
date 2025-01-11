<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Unicodeveloper\Paystack\Facades\Paystack;
use Veeqtoh\Cashier\Classes\SubscriptionBuilder;
use Veeqtoh\Cashier\Exceptions\FailedToCreatePaystackCustomer;
use Veeqtoh\Cashier\Models\Subscription;
use Veeqtoh\Cashier\Services\PaystackService;

trait ManagesSubscriptions
{
    /**
     * Begin creating a new subscription.
     */
    public function newSubscription(string $plan, string $subscription = 'default'): SubscriptionBuilder
    {
        return new SubscriptionBuilder($this, $subscription, $plan);
    }

    /**
     * Determine if the model is on trial.
     */
    public function onTrial(string $subscription = 'default', ?string $plan = null): bool
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($subscription);

        if (is_null($plan)) {
            return $subscription && $subscription->onTrial();
        }

        return $subscription && $subscription->onTrial() && $subscription->paystack_plan === $plan;
    }

    /**
     * Determine if the model is on a "generic" trial at the user level.
     */
    public function onGenericTrial(): bool
    {
        return $this->trial_ends_at && Carbon::now()->lt($this->trial_ends_at);
    }

    /**
     * Determine if the model has a given subscription.
     */
    public function subscribed(string $subscription = 'default', ?string $plan = null)
    {
        $subscription = $this->subscription($subscription);
        if (is_null($subscription)) {
            return false;
        }
        if (is_null($plan)) {
            return $subscription->valid();
        }
        return $subscription->valid() &&
               $subscription->paystack_plan === $plan;
    }

    /**
     * Get a subscription instance by name.
     */
    public function subscription(string $subscription = 'default'): ?Subscription
    {
        return $this->subscriptions->sortByDesc(function ($value) {
            return $value->created_at->getTimestamp();
        })->first(function ($value) use ($subscription) {
            return $value->name === $subscription;
        });
    }

    /**
     * Get all of the subscriptions for the model.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->orderBy('created_at', 'desc');
    }

    /**
     * Determine if the model is actively subscribed to one of the given plans.
     */
    public function subscribedToPlan(array|string $plans, string $subscription = 'default'): bool
    {
        $subscription = $this->subscription($subscription);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        foreach ((array) $plans as $plan) {
            if ($subscription->paystack_plan === $plan) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the entity is on the given plan.
     */
    public function onPlan(string $plan): bool
    {
        return ! is_null($this->subscriptions->first(function ($subscription) use ($plan) {
            return $subscription->paystack_plan === $plan;
        }));
    }

    /**
     * Create a Paystack customer for the given model.
     */
    public function createAsPaystackCustomer(array $options = []): mixed
    {
        $options  = array_key_exists('email', $options) ? $options : array_merge($options, ['email' => $this->email]);
        $response = PaystackService::createCustomer($options);

        if (! $response['status']) {
            throw new FailedToCreatePaystackCustomer($response);
        }

        $this->paystack_id   = $response['data']['id'];
        $this->paystack_code = $response['data']['customer_code'];

        $this->save();

        return $response['data'];
    }

    /**
     * Get the Paystack customer for the model.
     *
     * @return $customer
     */
    public function asPaystackCustomer()
    {
        $customer = Paystack::fetchCustomer($this->paystack_id)['data'];

        return $customer;
    }
}