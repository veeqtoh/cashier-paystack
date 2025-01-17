<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Veeqtoh\Cashier\Cashier;
use Veeqtoh\Cashier\Events\WebhookHandled;
use Veeqtoh\Cashier\Events\WebhookReceived;
use Veeqtoh\Cashier\Http\Middleware\VerifyWebhookSignature;
use Veeqtoh\Cashier\Models\Subscription;

class WebhookController extends Controller
{
    /**
     * Create a new webhook controller instance.
     */
    public function __construct()
    {
        Log::info('WebhookController initialized');
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
            Log::error('Invalid payload: Missing event key', ['payload' => $payload]);
            return new Response('Invalid payload', 400);
        }

        // Convert the event to a method name using studly_case (CamelCase).
        $method = 'handle' . Str::studly(str_replace('.', '_', $payload['event']));

        WebhookReceived::dispatch($payload);

        // Check if the method exists in this class.
        if (method_exists($this, $method)) {
            Log::info("Handling event: {$payload['event']}");

            $response = $this->{$method}($payload);

            WebhookHandled::dispatch($payload);

            return $response;
        }

        // Handle missing method or unknown event.
        Log::warning("Unhandled event: {$payload['event']}");
        return $this->missingMethod($payload);
    }

    /**
     * Handle customer subscription create.
     */
    protected function handleSubscriptionCreate(array $payload): Response
    {
        if (!isset($payload['data'])) {
            Log::error('Invalid subscription create payload: Missing data key', ['payload' => $payload]);
            return new Response('Invalid subscription data', 400);
        }

        $data         = $payload['data'];
        $user         = $this->getUserByPaystackCode($data['customer']['customer_code'] ?? null);
        $subscription = $this->getSubscriptionByCode($data['subscription_code'] ?? null);

        if (!$user) {
            Log::error('User not found for subscription create', ['customer_code' => $data['customer']['customer_code'] ?? null]);
            return new Response('User not found', 400);
        }

        if (!$subscription) {
            $plan = $data['plan'] ?? null;
            if ($plan) {
                $subscription = $user->newSubscription($plan['plan_code'], $plan['name']);
                $data['id']   = null;

                $subscription->add($data);

                Log::info('Subscription created successfully', ['subscription_code' => $data['subscription_code']]);
                return new Response('Subscription created', 200);
            }

            Log::error('Plan data missing in subscription payload', ['payload' => $payload]);
            return response()->json(['error' => 'Invalid plan data'], 400);
        }

        Log::info('Subscription already exists', ['subscription_code' => $data['subscription_code']]);
        return new Response('Subscription already exists', 200);
    }

    /**
     * Handle customer successfully charged.
     */
    public function handleChargeSuccess(array $payload) : Response
    {
        if (!isset($payload['data'])) {
            Log::error('Invalid charge success payload: Missing data key', ['payload' => $payload]);
            return new Response('Invalid charge data', 400);
        }

        $data = $payload['data'];
        $user = $this->getUserByEmail($data['customer']['email'] ?? null);

        if (!$user) {
            Log::error('User not found for charge success', ['customer_code' => $data['customer']['customer_code'] ?? null]);
            return new Response('User not found', 400);
        }

        $user->update([
            'paystack_customer_id'   => $data['customer']['id'],
            'paystack_customer_code' => $data['customer']['customer_code'],
            'card_type'              => $data['authorization']['card_type'],
            'card_last_four'         => $data['authorization']['last4'],
        ]);

        $user->save();

        Log::info('Customer data updated');
        return new Response('Charge success handled', 200);
    }

    /**
     * Handle when a subscription on your account's status has changed to non-renewing.
     * This means the subscription will not be charged on the next payment date.
     */
    protected function handleSubscriptionNotRenew(array $payload): Response
    {
        if (!isset($payload['data'])) {
            Log::error('Invalid subscription not renew payload: Missing data key', ['payload' => $payload]);
            return new Response('Invalid subscription not renew data', 400);
        }

        $data             = $payload['data'];
        $subscriptionCode = $data['subscription_code'];

        if (!$subscriptionCode) {
            Log::error('Missing subscription code in subscription not renew payload');
            return new Response('Invalid subscription code', 400);
        }

        $subscription = $this->getSubscriptionByCode($subscriptionCode);

        if ($subscription && (! $subscription->cancelled() || $subscription->onGracePeriod())) {
            $subscription->markAsSuspended(Carbon::parse($data['next_payment_date']));

            Log::info('Subscription will not renew', ['subscription_code' => $subscriptionCode]);
            return new Response('Subscription will not renew', 200);
        }

        Log::warning('Subscription not found or already cancelled', ['subscription_code' => $subscriptionCode]);
        return new Response('Subscription not found or already cancelled', 404);
    }

    /**
     * Handle a subscription disabled notification from paystack.
     */
    protected function handleSubscriptionDisable(array $payload): Response
    {
        return $this->cancelSubscription($payload['data']['subscription_code'] ?? null);
    }

    /**
     * Handle a subscription cancellation notification from paystack.
     */
    protected function cancelSubscription(?string $subscriptionCode): Response
    {
        if (!$subscriptionCode) {
            Log::error('Missing subscription code in cancellation payload');
            return new Response('Invalid subscription code', 400);
        }

        $subscription = $this->getSubscriptionByCode($subscriptionCode);

        if ($subscription && (! $subscription->cancelled() || $subscription->onGracePeriod())) {
            $subscription->markAsCancelled();

            Log::info('Subscription cancelled', ['subscription_code' => $subscriptionCode]);
            return new Response('Subscription cancelled', 200);
        }

        Log::warning('Subscription not found or already cancelled', ['subscription_code' => $subscriptionCode]);
        return new Response('Subscription not found or already cancelled', 404);
    }

    /**
     * Get the model for the given subscription Code.
     */
    protected function getSubscriptionByCode(?string $subscriptionCode): ?Subscription
    {
        if (!$subscriptionCode) {
            return null;
        }

        return Subscription::where('paystack_code', $subscriptionCode)->first();
    }

    /**
     * Get the billable entity instance by Paystack Code.
     */
    protected function getUserByPaystackCode(?string $paystackCode): mixed
    {
        if (!$paystackCode) {
            return null;
        }

        $model = Cashier::paystackModel();

        return (new $model)->where('paystack_customer_code', $paystackCode)->first();
    }

    /**
     * Get the billable entity instance by email address.
     */
    protected function getUserByEmail(?string $email): mixed
    {
        if (!$email) {
            return null;
        }

        $model = Cashier::paystackModel();

        return (new $model)->where('email', $email)->first();
    }

    /**
     * Handle calls to missing methods on the controller.
     */
    public function missingMethod(array $payload): Response
    {
        Log::warning('Unhandled webhook event', ['payload' => $payload]);
        return new Response('Unhandled webhook event', 200);
    }
}
