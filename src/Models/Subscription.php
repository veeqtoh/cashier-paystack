<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Veeqtoh\Cashier\Cashier;
use Veeqtoh\Cashier\Exceptions\SubscriptionNotFound;
use Veeqtoh\Cashier\Services\PaystackService;

/**
 * @property \Veeqtoh\Cashier\Billable|\Illuminate\Database\Eloquent\Model $owner
 */
class Subscription extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'trial_ends_at', 'ends_at',
        'created_at', 'updated_at',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): mixed
    {
        return $this->owner();
    }

    /**
     * Get the model related to the subscription.
     */
    public function owner(): BelongsTo
    {
        $class = Cashier::paystackModel();

        return $this->belongsTo($class, (new $class)->getForeignKey());
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     */
    public function valid(): bool
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is active.
     */
    public function active(): bool
    {
        return is_null($this->ends_at) || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is recurring and not on trial.
     */
    public function recurring(): bool
    {
        return ! $this->onTrial() && ! $this->cancelled();
    }

    /**
     * Determine if the subscription is no longer active.
     */
    public function cancelled(): bool
    {
        return ! is_null($this->ends_at);
    }

    /**
     * Determine if the subscription has ended and the grace period has expired.
     */
    public function ended(): bool
    {
        return $this->cancelled() && ! $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is within its trial period.
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Determine if the subscription is within its grace period after cancellation.
     */
    public function onGracePeriod(): bool
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Force the trial to end immediately.
     * This method must be combined with swap, resume, etc.
     */
    public function skipTrial(): static
    {
        $this->trial_ends_at = null;

        return $this;
    }

    /**
     * Cancel the subscription at the end of the billing period.
     */
    public function cancel(): static
    {
        $subscription = $this->asPaystackSubscription();

        PaystackService::disableSubscription([
            'token' => $subscription['email_token'],
            'code'  => $subscription['subscription_code'],
        ]);

        // If the user was on trial, we will set the grace period to end when the trial
        // would have ended. Otherwise, we'll retrieve the end of the billing period
        // period and make that the end of the grace period for this current user.

        if ($this->onTrial()) {
            $this->ends_at = $this->trial_ends_at;
        } else {
            $this->ends_at = Carbon::parse(
                $subscription['next_payment_date']
            );
        }

        $this->save();

        return $this;
    }

    /**
     * Cancel the subscription immediately.
     */
    public function cancelNow(): static
    {
        $this->cancel();
        $this->markAsCancelled();

        return $this;
    }

    /**
     * Mark the subscription as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->fill(['ends_at' => Carbon::now()])->save();
    }

    /**
     * Resume the cancelled subscription.
     */
    public function resume(): static
    {
        $subscription = $this->asPaystackSubscription();

        // To resume the subscription we need to enable the Paystack subscription.
        // Then Paystack will resume this subscription where we left off.

        PaystackService::enableSubscription([
            'token' => $subscription['email_token'],
            'code'  => $subscription['subscription_code'],
        ]);

        // Finally, we will remove the ending timestamp from the user's record in the
        // local database to indicate that the subscription is active again and is
        // no longer "cancelled". Then we will save this record in the database.

        $this->fill(['ends_at' => null])->save();

        return $this;
    }

    /**
     * Get the subscription as a Paystack subscription object.
     *
     * @throws SubscriptionNotFound If no subscription is found.
     */
    public function asPaystackSubscription(): mixed
    {
        try {
            $subscriptions = PaystackService::customerSubscriptions($this->user->paystack_id);

            if (empty($subscriptions)) {
                throw new SubscriptionNotFound('The Paystack customer does not have any subscriptions.');
            }

            foreach ($subscriptions as $subscription) {
                if ($subscription['id'] == $this->paystack_id) {
                    return $subscription;
                }
            }

            throw new SubscriptionNotFound('The Paystack subscription does not exist for this customer.');
        } catch (Exception $e) {
            // Log the exception or handle it as needed.
            throw new Exception('An error occurred while retrieving the Paystack subscription: ' . $e->getMessage(), 0, $e);
        }
    }
}