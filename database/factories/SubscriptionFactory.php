<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Veeqtoh\Cashier\Cashier;
use Veeqtoh\Cashier\Models\Subscription;

class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $model = Cashier::$customerModel;

        return [
            //
        ];
    }

}