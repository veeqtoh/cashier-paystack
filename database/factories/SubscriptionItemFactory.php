<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Veeqtoh\Cashier\Models\Subscription;
use Veeqtoh\Cashier\Models\SubscriptionItem;

class SubscriptionItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}