<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public Key From Paystack Dashboard.
    |--------------------------------------------------------------------------
    */
    'publicKey' => env('PAYSTACK_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Secret Key From Paystack Dashboard.
    |--------------------------------------------------------------------------
    */
    'secretKey' => env('PAYSTACK_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Paystack payment URL.
    |--------------------------------------------------------------------------
    */
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),

    /*
    |--------------------------------------------------------------------------
    | Optional email address of the merchant.
    |--------------------------------------------------------------------------
    */
    'merchantEmail' => env('MERCHANT_EMAIL', ''),

    /*
    |--------------------------------------------------------------------------
    | User model for customers in your app.
    |--------------------------------------------------------------------------
    | If unsure, set as 'App\Models\User'.
    |
    */
    'model' => env('PAYSTACK_MODEL', 'App\Models\User'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Path
    |--------------------------------------------------------------------------
    |
    | This is the base URI path where Cashier's views, such as the payment
    | verification screen, will be available from. You're free to tweak
    | this path according to your preferences and application design.
    |
    */

    'path' => env('CASHIER_PATH', 'paystack'),
];