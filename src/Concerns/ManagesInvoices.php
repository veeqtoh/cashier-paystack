<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Concerns;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Veeqtoh\Cashier\Classes\Invoice;
use Veeqtoh\Cashier\Exceptions\InvoiceNotFound;
use Veeqtoh\Cashier\Exceptions\NotPaystackCustomer;
use Veeqtoh\Cashier\Services\PaystackService;

/**
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait ManagesInvoices
{
    /**
     * Invoice the customer for the given amount.
     *
     * @throws NotPaystackCustomer
     * @throws InvalidArgumentException
     */
    public function tab(string $description, int $amount, array $options = []): mixed
    {
        if (! $this->paystack_id) {
            throw new NotPaystackCustomer(class_basename($this).' is not a Paystack customer. See the createAsPaystackCustomer method.');
        }

        if (! array_key_exists('due_date', $options)) {
            throw new InvalidArgumentException('No due date provided.');
        }

        $options = array_merge([
            'customer'    => $this->paystack_id,
            'amount'      => $amount,
            'currency'    => $this->preferredCurrency(),
            'description' => $description,
        ], $options);

        $options['due_date'] = Carbon::parse($options['due_date'])->format('c');

        return PaystackService::createInvoice($options);
    }

    /**
     * Invoice the billable entity outside of regular billing cycle.
     */
    public function invoiceFor(string $description, int $amount, array $options = []): mixed
    {
        return $this->tab($description, $amount, $options);
    }

    /**
     * Find an invoice by ID.
     */
    public function findInvoice(string $id): ?Invoice
    {
        try {
            $invoice = PaystackService::findInvoice($id);

            // Ensure the invoice belongs to this customer.
            if ($invoice['customer']['id'] != $this->paystack_id) {
                throw new InvoiceNotFound("Invoice {$id} does not belong to the customer with Paystack ID {$this->paystack_id}.");
            }

            return new Invoice($this, $invoice);
        } catch (InvoiceNotFound $e) {
            Log::warning($e->getMessage());
        } catch (Exception $e) {
            Log::error("An error occurred while finding invoice {$id}: {$e->getMessage()}", ['exception' => $e]);
        }

        return null;
    }

    /**
     * Find an invoice or fail.
     */
    public function findInvoiceOrFail(string $id): Invoice
    {
        $invoice = $this->findInvoice($id);

        if (is_null($invoice)) {
            throw new InvoiceNotFound("Invoice with ID {$id} not found.");
        }

        return $invoice;
    }

    /**
     * Create an invoice download Response.
     */
    public function downloadInvoice(string $id, array $data): Response
    {
        return $this->findInvoiceOrFail($id)->download($data);
    }

    /**
     * Get a collection of the entity's invoices.
     */
    public function invoices(array $options = []): Collection
    {
        if (!$this->hasPaystackId()) {
            throw new NotPaystackCustomer(class_basename($this).' is not a Paystack customer. See the createAsPaystackCustomer method.');
        }

        $invoices         = [];
        $parameters       = array_merge(['customer' => $this->paystack_id], $options);
        $paystackInvoices = PaystackService::fetchInvoices($parameters);

        // Here we will loop through the Paystack invoices and create our own custom Invoice
        // instances that have more helper methods and are generally more convenient to
        // work with than the plain Paystack objects are. Then, we'll return the array.

        if (!is_null($paystackInvoices && ! empty($paystackInvoices))) {
            foreach ($paystackInvoices as $invoice) {
                $invoices[] = new Invoice($this, $invoice);
            }
        }

        return new Collection($invoices);
    }

    /**
     * Get an array of the entity's invoices.
     */
    public function invoicesOnlyPending(array $parameters = []): Collection
    {
        $parameters['status'] = 'pending';

        return $this->invoices($parameters);
    }

     /**
     * Get an array of the entity's invoices.
     */
    public function invoicesOnlyPaid(array $parameters = []): Collection
    {
        $parameters['paid'] = true;

        return $this->invoices($parameters);
    }

    /**
     * Determine if the entity has a Paystack customer ID.
     */
    public function hasPaystackId(): bool
    {
        return ! is_null($this->paystack_id);
    }
}