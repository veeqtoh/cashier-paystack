<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array  $payload  The webhook payload.
     * @return void
     */
    public function __construct(public array $payload)
    {
        //
    }
}