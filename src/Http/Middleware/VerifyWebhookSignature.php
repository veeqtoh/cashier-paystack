<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class VerifyWebhookSignature
{
    /**
     * Create a new middleware instance.
     *
     * @param Application $app    The application instance.
     * @param Repository  $config The configuration repository instance.
     *
     * @return void
     */
    public function __construct(protected Application $app, protected Config $config)
    {
        //
    }

    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     *
     * @return Response
     */
    public function handle($request, Closure $next): Response
    {
        // Only a post with paystack signature header gets our attention.
        if (!$request->headers->has('HTTP_X_PAYSTACK_SIGNATURE')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Validate the event to prevent timing attacks.
        $signature = $request->header('HTTP_X_PAYSTACK_SIGNATURE');
        $payload   = $request->getContent();
        $secretKey = config('paystack-cashier.secretKey');

        if ($signature !== $this->generateSignature($payload, $secretKey)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }

    /**
     * Generate the Paystack signature using HMAC SHA256.
     *
     * @param  string  $payload
     * @param  string  $secretKey
     *
     * @return string
     */
    private function generateSignature(string $payload, string $secretKey): string
    {
        return hash_hmac('sha256', $payload, $secretKey);
    }
}