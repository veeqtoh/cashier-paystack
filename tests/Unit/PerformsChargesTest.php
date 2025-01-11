<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Tests\Unit;

use Mockery;
use Veeqtoh\Cashier\Exceptions\IncompletePayment;
use Veeqtoh\Cashier\Services\PaystackService;
use Veeqtoh\Cashier\Tests\Unit\TestCase;

class PerformsChargesTest extends TestCase
{
    protected $paystackServiceMock;
    protected $mockModel;

    // protected function setUp(): void
    // {
    //     parent::setUp();

    //     // Mock HTTP requests to Paystack
    //     \Illuminate\Support\Facades\Http::fake([
    //         'https://api.paystack.co/transaction/initialize' => \Illuminate\Support\Facades\Http::response([
    //             'status' => true,
    //             'data' => [
    //                 'reference' => 'mocked_reference',
    //                 'authorization_url' => 'https://paystack.mock/authorization',
    //             ],
    //         ]),
    //         'https://api.paystack.co/transaction/refund' => \Illuminate\Support\Facades\Http::response([
    //             'status' => true,
    //             'data' => ['transaction' => 'mocked_refund'],
    //         ]),
    //     ]);

    //     // Mock PaystackService
    //     $this->paystackServiceMock = Mockery::mock(PaystackService::class);
    //     $this->app->instance(PaystackService::class, $this->paystackServiceMock);

    //     // Use a mock model with the PerformsCharges trait
    //     $this->mockModel = new class {
    //         use \Veeqtoh\Cashier\Concerns\PerformsCharges;

    //         public $email = 'customer@example.com';

    //         public function preferredCurrency(): string
    //         {
    //             return 'ngn';
    //         }
    //     };
    // }

    // public function testChargeCustomerSuccessfully()
    // {
    //     // Mock PaystackService's response
    //     $response = [
    //         'status' => true,
    //         'data' => ['reference' => 'test_reference'],
    //     ];

    //     $this->paystackServiceMock
    //         ->shouldReceive('makePaymentRequest')
    //         ->once()
    //         ->andReturn($response);

    //     $result = $this->mockModel->charge(10000); // Charge 100.00 NGN

    //     $this->assertSame($response, $result);
    // }

    // public function testChargeFailsAndThrowsException()
    // {
    //     $this->expectException(IncompletePayment::class);

    //     // Mock a failed response from PaystackService
    //     $response = [
    //         'status' => false,
    //         'message' => 'Insufficient funds',
    //     ];

    //     $this->paystackServiceMock
    //         ->shouldReceive('makePaymentRequest')
    //         ->once()
    //         ->andReturn($response);

    //     $this->mockModel->charge(10000); // This should throw IncompletePayment
    // }

    // public function testRefundTransactionSuccessfully()
    // {
    //     // Mock PaystackService's refund response
    //     $response = [
    //         'status' => true,
    //         'data' => ['transaction' => 'refund_test'],
    //     ];

    //     $this->paystackServiceMock
    //         ->shouldReceive('refund')
    //         ->once()
    //         ->andReturn($response);

    //     $result = $this->mockModel->refund('transaction_reference');

    //     $this->assertSame($response, $result);
    // }

    // protected function tearDown(): void
    // {
    //     Mockery::close();
    //     parent::tearDown();
    // }
}
