<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Veeqtoh\Cashier\Cashier;

/**
 * @property \Veeqtoh\Cashier\Models\Subscription|null $subscription
 */
class SubscriptionItem extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the subscription that the item belongs to.
     */
    public function subscription(): BelongsTo
    {
        $model = Cashier::$subscriptionModel;

        return $this->belongsTo($model, (new $model)->getForeignKey());
    }

}