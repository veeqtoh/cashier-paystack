<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Classes;

use Carbon\Carbon;
use DateTimeZone;
use Dompdf\Dompdf;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Veeqtoh\Cashier\Cashier;
use Veeqtoh\Cashier\Services\PaystackService;

class Invoice
{
    /**
     * Create a new invoice instance.
     *
     * @param  Model $owner   The model instance.
     * @param  mixed $invoice The Paystack invoice instance.
     *
     * @return void
     */
    public function __construct(protected Model $owner, protected mixed $invoice)
    {
        //
    }

    /**
     * Get a Carbon date for the invoice.
     */
    public function date(DateTimeZone|string|null $timezone = null): Carbon
    {
        $carbon = Carbon::instance($this->invoice['created_at']);

        return $timezone ? $carbon->setTimezone($timezone) : $carbon;
    }

    /**
     * Get the total amount that was paid (or will be paid).
     */
    public function total(): mixed
    {
        return $this->formatAmount($this->rawTotal());
    }

    /**
     * Get the raw total amount that was paid (or will be paid).
     */
    public function rawTotal(): mixed
    {
        return max(0, $this->invoice['amount']);
    }

    /**
     * Get the total of the invoice (before discounts).
     */
    public function subtotal(): string
    {
        if ($this->hasStartingBalance()) {
            return $this->startingBalance();
        }

        return $this->formatAmount(
            max(0, $this->invoice['amount'] - ($this->invoice['discount']['amount'] ?? 0))
        );
    }

    /**
     * Determine if the account had a starting balance.
     */
    public function hasStartingBalance(): bool
    {
        return $this->rawStartingBalance() > 0;
    }

    /**
     * Get the starting balance for the invoice.
     */
    public function startingBalance(): mixed
    {
        return $this->formatAmount($this->rawStartingBalance());
    }

    /**
     * Determine if the invoice has a discount.
     */
    public function hasDiscount(): bool
    {
        return isset($this->invoice['discount']);
    }

    /**
     * Get the discount amount.
     */
    public function discount(): mixed
    {
        return $this->formatAmount($this->invoice['discount']['amount']);
    }

    /**
     * Determine if the discount is a percentage.
     */
    public function discountIsPercentage(): bool
    {
        return $this->hasDiscount() && $this->invoice['discount']['type'] == 'percentage' ;
    }

    /**
     * Get the discount percentage for the invoice.
     */
    public function percentOff(): mixed
    {
        if ($this->discountIsPercentage()) {
            return $this->invoice['discount']['amount'];
        }

        return 0;
    }

    /**
     * Get the discount amount for the invoice.
     */
    public function amountOff(): mixed
    {
        if (isset($this->invoice['discount']['amount_off'])) {
            return $this->formatAmount($this->invoice['discount']['amount_off']);
        }

        return $this->formatAmount(0);
    }

    /**
     * Get the raw invoice balance amount.
     */
    public function rawStartingBalance(): mixed
    {
        $totalItemAmount = 0;
        foreach ($this->invoice['line_items'] as $item) {
            $totalItemAmount += $item['amount'];
        }

        return $totalItemAmount;
    }

    /**
     * Get the items applied to the invoice.
     */
    public function invoiceItems(): array
    {
        $items = [];

        foreach ($this->invoice['line_items'] as $item) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Format the given amount into a string based on the user's preferences.
     */
    protected function formatAmount(int $amount): mixed
    {
        return Cashier::formatAmount($amount);
    }

    /**
     * Update instance for the invoice.
     */
    public function update(array $data): mixed
    {
        $data['customer'] = $this->owner->paystack_id;

        return PaystackService::updateInvoice($this->invoice['id'], $data);

    }

    /**
     * Get the status for this invoice instance.
     */
    public function status(): mixed
    {
        return $this->invoice['status'];
    }

    /**
     * Verify this invoice instance.
     */
    public function verify(): mixed
    {
        return PaystackService::verifyInvoice($this->invoice['request_code']);
    }

    /**
     * Notify the customer for this invoice instance.
     */
    public function notify(): mixed
    {
        return PaystackService::notifyInvoice($this->invoice['id']);
    }

    /**
     * Finalize this draft invoice instance.
     */
    public function finalize(): mixed
    {
        if ($this->status() === 'draft') {
            return PaystackService::finalizeInvoice($this->invoice['id']);
        }

        return $this->notify();
    }

    /**
     * Archive this invoice instance.
     */
    public function archive(): mixed
    {
        return PaystackService::archiveInvoice($this->invoice['id']);
    }

    /**
     * Get the View instance for the invoice.
     */
    public function view(array $data): ViewContract
    {
        return View::make('cashier::receipt', array_merge(
            $data, ['invoice' => $this, 'owner' => $this->owner, 'user' => $this->owner]
        ));
    }

    /**
     * Capture the invoice as a PDF and return the raw bytes.
     */
    public function pdf(array $data): ?string
    {
        if (! defined('DOMPDF_ENABLE_AUTOLOAD')) {
            define('DOMPDF_ENABLE_AUTOLOAD', false);
        }

        if (file_exists($configPath = base_path().'/vendor/dompdf/dompdf/dompdf_config.inc.php')) {
            require_once $configPath;
        }

        $dompdf = new Dompdf;
        $dompdf->loadHtml($this->view($data)->render());
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Create an invoice download response.
     */
    public function download(array $data): Response
    {
        $filename = $data['product'].'_'.$this->date()->month.'_'.$this->date()->year.'.pdf';

        return new Response($this->pdf($data), 200, [
            'Content-Type'              => 'application/pdf',
            'Content-Description'       => 'File Transfer',
            'Content-Disposition'       => 'attachment; filename="'.$filename.'"',
            'Content-Transfer-Encoding' => 'binary',
        ]);
    }

    /**
     * Get the Paystack invoice instance.
     */
    public function asPaystackInvoice(): mixed
    {
        return $this->invoice;
    }

    /**
     * Dynamically get values from the Paystack invoice.
     */
    public function __get(string $key): mixed
    {
        return $this->invoice[$key];
    }
}