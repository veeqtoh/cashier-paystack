<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Veeqtoh\Cashier\Models\Subscription;
use Veeqtoh\Cashier\SubscriptionBuilder;

trait ManagesSubscriptions
{
    /**
     * Begin creating a new subscription.
     */
    public function newSubscription(string $subscription = 'default', string $plan): SubscriptionBuilder
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
}