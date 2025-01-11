<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Veeqtoh\Cashier\Cashier;
use Veeqtoh\Cashier\Http\Middleware\VerifyWebhookSignature;
use Veeqtoh\Cashier\Models\Subscription;

class WebhookController extends Controller
{
    /**
     * Create a new webhook controller instance.
     */
    public function __construct()
    {
        if (config('paystack.secretKey')) {
            $this->middleware(VerifyWebhookSignature::class);
        }
    }

    /**
     * Handle a Paystack webhook call.
     */
    public function handleWebhook(Request $request): mixed
    {
        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['event'])) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Convert the event to a method name using studly_case (CamelCase)
        $method = 'handle' . Str::studly(str_replace('.', '_', $payload['event']));

        // Check if the method exists in this class.
        if (method_exists($this, $method)) {
            return $this->{$method}($payload);
        }

        // Handle missing method or unknown event.
        return $this->missingMethod();
    }

    /**
     * Handle customer subscription create.
     */
    protected function handleSubscriptionCreate(array $payload): Response
    {
        $data         = $payload['data'];
        $user         = $this->getUserByPaystackCode($data['customer']['customer_code']);
        $subscription = $this->getSubscriptionByCode($data['subscription_code']);

        if ($user && !isset($subscription)) {
            $plan         = $data['plan'];
            $subscription = $user->newSubscription($plan['name'], $plan['plan_code']);
            $data['id']   =  null;

            $subscription->add($data);
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle a subscription disabled notification from paystack.
     */
    protected function handleSubscriptionDisable(array $payload): Response
    {
        return $this->cancelSubscription($payload['data']['subscription_code']);
    }

    /**
     * Handle a subscription cancellation notification from paystack.
     */
    protected function cancelSubscription(string $subscriptionCode): Response
    {
        $subscription = $this->getSubscriptionByCode($subscriptionCode);

        if ($subscription && (! $subscription->cancelled() || $subscription->onGracePeriod())) {
            $subscription->markAsCancelled();
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Get the model for the given subscription Code.
     */
    protected function getSubscriptionByCode(string $subscriptionCode): ?Subscription
    {
        return Subscription::where('paystack_code', $subscriptionCode)->first();
    }

    /**
     * Get the billable entity instance by Paystack Code.
     */
    protected function getUserByPaystackCode(string $paystackCode): mixed
    {
        $model = Cashier::paystackModel();

        return (new $model)->where('paystack_code', $paystackCode)->first();
    }

    /**
     * Handle calls to missing methods on the controller.
     */
    public function missingMethod(): Response
    {
        return new Response;
    }
}
