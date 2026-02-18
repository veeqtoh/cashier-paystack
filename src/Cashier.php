<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier;

use Veeqtoh\Cashier\Exceptions\CurrencySymbolNotFound;
use Veeqtoh\Cashier\Models\Subscription;
use Veeqtoh\Cashier\Models\SubscriptionItem;

class Cashier
{
    /**
     * Indicates if Cashier routes will be registered.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    /**
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;

    /**
     * The current currency.
     *
     * @var string
     */
    protected static $currency = 'ngn';

    /**
     * The current currency symbol.
     *
     * @var string
     */
    protected static $currencySymbol = '₦';

    /**
     * The default customer model class name.
     *
     * @var string
     */
    public static $customerModel = 'App\\Models\\User';

    /**
     * The subscription model class name.
     *
     * @var string
     */
    public static $subscriptionModel = Subscription::class;

    /**
     * The subscription item model class name.
     *
     * @var string
     */
    public static $subscriptionItemModel = SubscriptionItem::class;

    /**
     * Get the class name of the billable model.
     *
     * @return string
     */
    public static function paystackModel(): mixed
    {
        return getenv('PAYSTACK_MODEL') ?: config('paystack.model', 'App\\Models\\User');
    }

    /**
     * Set the currency to be used when billing models.
     */
    public static function useCurrency(string $currency, ?string $symbol = null): void
    {
        $currency         = strtolower($currency);
        static::$currency = $currency;

        static::useCurrencySymbol($symbol ?: static::guessCurrencySymbol($currency));
    }

    /**
     * Guess the currency symbol for the given currency.
     *
     * @throws CurrencySymbolNotFound
     */
    protected static function guessCurrencySymbol(string $currency): string
    {
        $currency = strtolower($currency);

        $currencySymbols = [
            'ngn' => '₦',
            'ghs' => 'GH₵',
            'eur' => '€',
            'gbp' => '£',
            'usd' => '$',
            'aud' => '$',
            'cad' => '$',
            'zar' => 'R',
            'kes' => 'KSh',
        ];

        if (array_key_exists($currency, $currencySymbols)) {
            return $currencySymbols[$currency];
        }

        throw new CurrencySymbolNotFound($currency);
    }

    /**
     * Get the currency currently in use.
     */
    public static function usesCurrency(): string
    {
        return strtoupper(static::$currency);
    }

    /**
     * Set the currency symbol to be used when formatting currency.
     */
    public static function useCurrencySymbol(string $symbol): void
    {
        static::$currencySymbol = $symbol;
    }

    /**
     * Get the currency symbol currently in use.
     */
    public static function usesCurrencySymbol(): string
    {
        return static::$currencySymbol;
    }

    /**
     * Set the custom currency formatter.
     */
    public static function formatCurrencyUsing(callable $callback): void
    {
        static::$formatCurrencyUsing = $callback;
    }

    /**
     * Format the given amount into a displayable currency.
     */
    public static function formatAmount(int $amount): string
    {
        // Check if a custom formatter is defined.
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount);
        }

        // Format the amount to 2 decimal places.
        $formattedAmount = number_format($amount / 100, 2);

        // Determine the sign and prepend the currency symbol.
        $currencySymbol = static::usesCurrencySymbol();

        if (str_starts_with($formattedAmount, '-')) {
            return '-' . $currencySymbol . ltrim($formattedAmount, '-');
        }

        return $currencySymbol . $formattedAmount;
    }
}