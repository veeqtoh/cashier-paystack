<?php

use Veeqtoh\Cashier\Cashier;
use Veeqtoh\Cashier\Exceptions\CurrencySymbolNotFound;

beforeEach(function () {
    // Reset to default currency before each test.
    Cashier::useCurrency('ngn', '₦');
});

test('it sets and retrieves the current currency', function () {
    Cashier::useCurrency('usd', '$');
    expect(Cashier::usesCurrency())->toBe('USD');
    expect(Cashier::usesCurrencySymbol())->toBe('$');
});

test('it guesses correct currency symbols', function () {
    Cashier::useCurrency('zar');
    expect(Cashier::usesCurrencySymbol())->toBe('R');

    Cashier::useCurrency('kes');
    expect(Cashier::usesCurrencySymbol())->toBe('KSh');

    Cashier::useCurrency('ngn');
    expect(Cashier::usesCurrencySymbol())->toBe('₦');

    Cashier::useCurrency('usd');
    expect(Cashier::usesCurrencySymbol())->toBe('$');
});

test('it throws an exception for unknown currency', function () {
    Cashier::useCurrency('xyz');
})->throws(CurrencySymbolNotFound::class);

test('it formats amounts correctly with default formatter', function () {
    expect(Cashier::formatAmount(12345))->toBe('₦123.45');
    expect(Cashier::formatAmount(-12345))->toBe('-₦123.45');
});

test('it allows custom currency formatter', function () {
    Cashier::formatCurrencyUsing(function ($amount) {
        return 'CUSTOM ' . number_format($amount / 100, 2);
    });

    expect(Cashier::formatAmount(12345))->toBe('CUSTOM 123.45');
    expect(Cashier::formatAmount(-12345))->toBe('CUSTOM -123.45');
});

test('it resets currency symbol correctly', function () {
    Cashier::useCurrency('zar');
    expect(Cashier::usesCurrency())->toBe('ZAR');
    expect(Cashier::usesCurrencySymbol())->toBe('R');
});
