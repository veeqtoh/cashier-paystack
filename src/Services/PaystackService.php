<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Veeqtoh\Cashier\Exceptions\IsNull;

class PaystackService {
    /**
     * Issue Secret Key from your Paystack Dashboard.
     *
     * @var string
     */
    protected $secretKey;

    /**
     * Instance of Client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Response from requests made to Paystack.
     *
     * @var mixed
     */
    protected $response;

    /**
     * Paystack API base Url.
     *
     * @var string
     */
    protected $baseUrl;

    public function __construct()
    {
        $this->setKey();
        $this->setBaseUrl();
        $this->setRequestOptions();
    }

    /**
     * Get Base Url from Paystack config file.
     */
    public function setBaseUrl(): void
    {
        $this->baseUrl = Config::get('paystack.paymentUrl');
    }

    /**
     * Get secret key from Paystack config file.
     */
    public function setKey(): void
    {
        $this->secretKey = Config::get('paystack.secretKey');
    }

    /**
     * Set options for making the Client request.
     */
    private function setRequestOptions(): void
    {
        $authBearer   = 'Bearer '. $this->secretKey;
        $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'verify'   => false,
                'headers'  => [
                    'Authorization' => $authBearer,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json'
                ],
            ],
        );
    }

    /**
     * Set the HTTP response object.
     *
     * @param string $relativeUrl
     * @param string $method
     * @param array  $body
     *
     * @throws IsNull
     */
    private function setHttpResponse($relativeUrl, $method, $body = []): static
    {
        if (is_null($method)) {
            throw new IsNull();
        }

        $this->response = $this->client->{strtolower($method)}(
            $this->baseUrl . $relativeUrl,
            ["body" => json_encode($body)]
        );

        return $this;
    }

    /**
     * Get the whole response from a get operation.
     */
    private function getResponse(): mixed
    {
        // Convert the Stream to a string before decoding.
        $body    = (string) $this->response->getBody();
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON response: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Get the data response from a get operation.
     */
    private function getData(): mixed
    {
        return $this->getResponse()['data'];
    }

    /**
     * Hit the "charge" post endpoint.
     */
    public static function charge(array $data): mixed
    {
        return (new self)->setHttpResponse('/charge', 'POST', $data)->getResponse();
    }

    /**
     * Hit the "transaction/initialize" post endpoint.
     */
    public static function makePaymentRequest(array $data): mixed
    {
        return (new self)->setHttpResponse('/transaction/initialize', 'POST', $data)->getResponse();
    }

    /**
     * Hit the "charge_authorization" post endpoint.
     */
    public static function chargeAuthorization(array $data): mixed
    {
        return (new self)->setHttpResponse('/charge_authorization', 'POST', $data)->getResponse();
    }

    /**
     * Hit the "refund" post endpoint.
     */
    public static function refund(array $data): mixed
    {
        return (new self)->setHttpResponse('/refund', 'POST', $data)->getResponse();
    }

    /**
     * Hit the "check_authorization" post endpoint.
     */
    public static function checkAuthorization(array $data): mixed
    {
        return (new self)->setHttpResponse('/check_authorization', 'POST', $data)->getResponse();
    }

    /**
     * Hit the "deactivate_authorization" post endpoint.
     */
    public static function deactivateAuthorization(string $auth_code): mixed
    {
        $data = ['authorization_code' => $auth_code];
        return (new self)->setHttpResponse('/deactivate_authorization', 'POST', $data)->getResponse();
    }

    /**
     * Hit the "subscription" post endpoint.
     */
    public static function createSubscription(array $data): mixed
    {
        return (new self)->setHttpResponse('/subscription', 'POST', $data)->getResponse();
    }

    /**
     * Hit the "customer" post endpoint.
     */
    public static function createCustomer(array $data): mixed
    {
        return (new self)->setHttpResponse('/customer', 'POST', $data)->getResponse();
    }

    /**
     * Hit the "customer" get endpoint.
     */
    public static function fetchCustomer(string $customer_id, array $options = []): mixed
    {
        return (new self)->setHttpResponse("/customer/{$customer_id}", 'GET', $options)->getData();
    }

    /**
     * Hit the "subscription" get endpoint.
     */
    public static function customerSubscriptions(string $customer_id): mixed
    {
        $data = ['customer' => $customer_id];
        return (new self)->setHttpResponse('/subscription', 'GET', $data)->getData();
    }

    /**
     * Enable a subscription using the subscription code and token.
     */
    public static function enableSubscription(array $data): mixed
    {
        return (new self)->setHttpResponse('/subscription/enable', 'POST', $data)->getResponse();
    }

    /**
     * Disable a subscription using the subscription code and token.
     */
    public static function disableSubscription(array $data): mixed
    {
        return (new self)->setHttpResponse('/subscription/disable', 'POST', $data)->getResponse();
    }

    /**
     * Hit the "paymentrequest" post endpoint.
     */
    public static function createInvoice(array $data): mixed
    {
        return (new self)->setHttpResponse('/paymentrequest', 'POST', $data)->getResponse();
    }

    /**
     * Hit the "paymentrequest" get endpoint.
     */
    public static function fetchInvoices(array $data): mixed
    {
        return (new self)->setHttpResponse('/paymentrequest', 'GET', $data)->getData();
    }

    /**
     * Find an invoice by ID.
     */
    public static function findInvoice(string $invoice_id): mixed
    {
        return (new self)->setHttpResponse('/paymentrequest'. $invoice_id, 'GET', [])->getData();
    }

    /**
     * update an invoice.
     */
    public static function updateInvoice(string $invoice_id, array $data): mixed
    {
        return (new self)->setHttpResponse('/paymentrequest'. $invoice_id, 'PUT', $data)->getResponse();
    }

    /**
     * Verify an invoice.
     */
    public static function verifyInvoice(string $invoice_code): mixed
    {
        return (new self)->setHttpResponse('/paymentrequest/verify'. $invoice_code, 'GET', [])->getData();
    }

    /**
     * Notify on an invoice.
     */
    public static function notifyInvoice(string $invoice_id): mixed
    {
        return (new self)->setHttpResponse('/paymentrequest/notify'. $invoice_id, 'POST', [])->getResponse();
    }

    /**
     * Finalize an invoice.
     */
    public static function finalizeInvoice(string $invoice_id): mixed
    {
        return (new self)->setHttpResponse('/paymentrequest/finalize'. $invoice_id, 'POST', [])->getResponse();
    }

    /**
     * Archive an invoice.
     */
    public static function archiveInvoice(string $invoice_id): mixed
    {
        return (new self)->setHttpResponse('/paymentrequest/archive'. $invoice_id, 'POST', [])->getResponse();
    }

    /**
     * Create a subscription plan.
     */
    public static function createPlan(array $data): mixed
    {
        return (new self)->setHttpResponse('/plan', 'POST', $data)->getData();
    }
}