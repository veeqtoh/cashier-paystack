
# Laravel Cashier - Paystack Edition

<p align="center">
<a href="https://packagist.org/packages/veeqtoh/cashier-paystack"><img src="https://img.shields.io/packagist/v/veeqtoh/cashier-paystack?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://packagist.org/packages/veeqtoh/cashier-paystack"><img src="https://img.shields.io/packagist/dt/veeqtoh/cashier-paystack?style=flat-square" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/veeqtoh/cashier-paystack"><img src="https://img.shields.io/packagist/php-v/veeqtoh/cashier-paystack?style=flat-square" alt="PHP from Packagist"></a>
<a href="https://github.com/veeqtoh/cashier-paystack/blob/master/LICENSE"><img src="https://img.shields.io/github/license/veeqtoh/cashier-paystack?style=flat-square" alt="GitHub license"></a>
</p>

## Table of Contents
- [Overview](#overview)
- [Installation](#installation)
    - [Requirements](#requirements)
    - [Install the Package](#install-the-package)
    - [Publish the Config and Migrations](#publish-the-config-and-migrations)
    - [Migrate the Database](#migrate-the-database)
    - [Configuration](#configuration)
- [Usage](#usage)
    - [Billable Model](#billable-model)
    - [Currency Configuration](#currency-configuration)
    - [Subscriptions](#subscriptions)
        - [Creating Subscriptions](#creating-subscriptions)
        - [Additional User Details](#additional-user-details)
        - [Checking Subscription Status](#checking-subscription-status)
        - [Cancelled Subscription Status](#cancelled-subscription-status)
        - [Cancelling Subscription](#cancelling-subscription)
        - [Resuming Subscription](#resuming-subscription)
        - [Subscription Trials](#subscription-trials)
        - [Without Billing Up Front](#without-billing-up-front)
    - [Customers](#customers)
        - [Creating Customers](#creating-customers)
    - [Payment Methods](#payment-methods)
        - [Retrieving Authenticated Payment Methods](#retrieving-authenticated-payment-methods)
        - [Deleting Payment Methods](#deleting-payment-methods)
    - [Handling Paystack Webhooks](#handling-paystack-webhooks)
        - [Webhooks & CSRF Protection](#webhooks-&-csrf-protection)
        - [Defining Webhook Event Handlers](#defining-webhook-event-handlers)
    - [Single Charges](#single-charges)
        - [Simple Charge](#simple-charge)
        - [Charge With Invoice](#charge-with-invoice)
        - [Refunding Charges](#refunding-charges)
    - [Invoices](#invoices)
        - [Generating Invoice PDFs](#generating-invoice-pdfs)
- [Testing](#testing)
- [Security](#security)
- [Contribution](#contribution)
- [Changelog](#changelog)
- [Upgrading](#upgrading)
- [License](#license)

## Overview
This library is a modern & complete re-write of the abandoned [webong's cashier-paystack library](https://github.com/webong/cashier-paystack) which provides an expressive, fluent interface to Paystack's subscription billing services for Laravel > 10.x apps.

## Installation
### Requirements
The package has been developed and tested to work with the following minimum requirements:

- PHP 8.x
- Laravel 10.x

### Install the Package
You can install the package via Composer:
```bash
composer require veeqtoh/cashier-paystack
```

### Publish the Config and Migrations
You can then publish the package's config file and database migrations by using the following command:
```bash
php artisan vendor:publish --provider="Veeqtoh\Cashier\Providers\CashierServiceProvider"
```

### Migrate the Database
Before using Cashier, we'll also need to prepare the database. We need to add several columns to your  users table and create a new subscriptions table to hold all of our customer's subscriptions: Run the following command.
```bash
php artisan migrate
```

### Configuration
Upon [publishing the configuration and migration files](#install-the-package), a configuration-file named `paystack`.php with some sensible defaults will be placed in your config directory. Update them or set their corresponding values in your `.env` file as follows.
```php
PAYSTACK_MODEL=App\Models\User
PAYSTACK_PUBLIC_KEY=pk_test_your_public_key
PAYSTACK_SECRET_KEY=sk_test_your_secret_key
PAYSTACK_PAYMENT_URL=https://api.paystack.co
MERCHANT_EMAIL=your_merchant_email
```

## Usage
### Billable Model
Next, add the Billable trait to your model definition. This trait provides various methods to allow you to perform common billing tasks, such as creating subscriptions, applying coupons, and updating credit card information:
```php
use Veeqtoh\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

### Currency Configuration
The default Cashier currency is Nigeria Naira (NGN). You can change the default currency by calling the Cashier::useCurrency method from within the boot method of one of your service providers. The useCurrency method accepts two string parameters: the currency and the currency's symbol:
```php
use Veeqtoh\Cashier\Cashier;

Cashier::useCurrency('ngn', '₦');
Cashier::useCurrency('ghs', 'GH₵');
```

## Subscriptions
#### Creating Subscriptions
To create a subscription, first retrieve an instance of your billable model, which typically will be an instance of `App\User`. Once you have retrieved the model instance, you may use the  `newSubscription` method to create the model's subscription:
```php
$user = User::find(1);
$plan_code = // Paystack plan code  e.g PLN_gx2wn530m0i3w3m
$plan_name = // Paystack plan name e.g default, main, yakata
$auth_token = // Paystack card auth token for customer

//Example usages.
// 1. Accepts an card authorization authtoken for the customer.
$response = $user->newSubscription($plan_code, $plan_name)->create($auth_token);

// 2. The customer's most recent authorization would be used to charge subscription.
$response = $user->newSubscription($plan_code, $plan_name)->create();

// 3. Initialize a new charge for a subscription.
// Paystack accepts charges in Kobo (e.g., 10000 = ₦100.00)
$response = $user->newSubscription($plan_code, $plan_name)->charge(10000);
return redirect($response['data']['authorization_url']);
```

The first argument passed to the `newSubscription` method is the specific Paystack Paystack code the user is subscribing to. This value should correspond to the Paystack's code identifier in Paystack. The second argument should be the name of the subscription. If your application only offers a single subscription, you might call this main or primary.

The `create` method, which accepts a Paystack authorization token, will begin the subscription as well as update your database with the customer/user ID and other relevant billing information.

The `charge` method accepts an amount (in the lowest denominator of the currency, e.g., Kobo for Naira) and initializes a transaction which returns a response containing an authorization url for payment and an access code. 

#### Additional User Details
If you would like to specify additional customer details, you may do so by passing them as the second argument to the `create` method:
```php
$user->newSubscription('PLN_cgumntiwkkda3cw', 'main')
    ->create(
        $auth_token,
        ['data' => 'More Customer Data'],
        ['data' => 'More Subscription Data']
    );
```

To learn more about the additional fields supported by Paystack, check out paystack's documentation on customer creation or the corresponding Paystack documentation.

#### Checking Subscription Status
Once a user is subscribed to your application, you may easily check their subscription status using a variety of convenient methods. First, the `subscribed` method returns `true` if the user has an active subscription, even if the subscription is currently within its trial period:
```php
// Paystack plan name e.g default, main, yakata.
if ($user->subscribed('main')) {
    //
}
```

The `subscribed` method also makes a great candidate for a route middleware, allowing you to filter access to routes and controllers based on the user's subscription status:
```php
public function handle($request, Closure $next)
{
    if ($request->user() && ! $request->user()->subscribed('main')) {
        // This user is not a paying customer...
        return redirect('billing');
    }

    return $next($request);
}
```

If you would like to determine if a user is still within their trial period, you may use the `onTrial` method. This method can be useful for displaying a warning to the user that they are still on their trial period:
```php
if ($user->subscription('main')->onTrial()) {
    //
}
```

The `subscribedToPaystack` method may be used to determine if the user is subscribed to a given Paystack based on a given Paystack Paystack code. In this example, we will determine if the user's main subscription is actively subscribed to the monthly Paystack:
```php
$plan_code = // Paystack Paystack Code  e.g PLN_gx2wn530m0i3w3m
$plan_name = // Paystack plan name e.g default, main, yakata

if ($user->subscribedToPlan($plan_code, $plan_name)) {
    //
}
```

#### Cancelled Subscription Status
To determine if the user was once an active subscriber, but has cancelled their subscription, you may use the `cancelled` method:
```php
if ($user->subscription('main')->cancelled()) {
    //
}
```

You may also determine if a user has cancelled their subscription, but are still on their "grace period" until the subscription fully expires. For example, if a user cancels a subscription on March 5th that was originally scheduled to expire on March 10th, the user is on their "grace period" until March 10th. Note that the subscribed method still returns true during this time:
```php
if ($user->subscription('main')->onGracePeriod()) {
    //
}
```

#### Cancelling Subscriptions
To cancel a subscription, call the cancel method on the user's subscription:
```php
$user->subscription('main')->cancel();
```

When a subscription is cancelled, Cashier will automatically set the `ends_at` column in your database. This column is used to know when the subscribed method should begin returning false. For example, if a customer cancels a subscription on March 1st, but the subscription was not scheduled to end until March 5th, the subscribed method will continue to return true until March 5th.

You may determine if a user has cancelled their subscription but are still on their "grace period" using the  onGracePeriod method:
```php
if ($user->subscription('main')->onGracePeriod()) {
    //
}
```

If you wish to cancel a subscription immediately, call the `cancelNow` method on the user's subscription:
```php
$user->subscription('main')->cancelNow();
```

#### Resuming Subscriptions
If a user has cancelled their subscription and you wish to resume it, use the `resume` method. The user must still be on their grace period in order to resume a subscription:
```php
$user->subscription('main')->resume();
```

If the user cancels a subscription and then resumes that subscription before the subscription has fully expired, they will not be billed immediately. Instead, their subscription will be re-activated, and they will be billed on the original billing cycle.

#### Subscription Trials
##### With Billing Up Front
If you would like to offer trial periods to your customers while still collecting payment method information up front, you should use the `trialDays` method when creating your subscriptions:
```php
$user = User::find(1);

$user->newSubscription('PLN_gx2wn530m0i3w3m', 'main')
    ->trialDays(10)
    ->create($auth_token);
```

This method will set the trial period ending date on the subscription record within the database, as well as instruct Paystack to not begin billing the customer until after this date.

If the customer's subscription is not cancelled before the trial ending date they will be charged as soon as the trial expires, so you should be sure to notify your users of their trial ending date.

You may determine if the user is within their trial period using either the onTrial method of the user instance, or the onTrial method of the subscription instance. The two examples below are identical:
```php
if ($user->onTrial('main')) {
    //
}

if ($user->subscription('main')->onTrial()) {
    //
}
```

#### Without Billing Up Front
If you would like to offer trial periods without collecting the user's payment method information up front, you may set the `trial_ends_at` column on the user record to your desired trial ending date. This is typically done during user registration:
```php
$user = User::create([
    // Populate other user properties...
    'trial_ends_at' => now()->addDays(10),
]);
```
Be sure to add a date mutator for `trial_ends_at` to your model definition.

Cashier refers to this type of trial as a "generic trial", since it is not attached to any existing subscription. The onTrial method on the User instance will return true if the current date is not past the value of trial_ends_at:
```php
if ($user->onTrial()) {
    // User is within their trial period...
}
```
You may also use the `onGenericTrial` method if you wish to know specifically that the user is within their "generic" trial period and has not created an actual subscription yet:
```php
if ($user->onGenericTrial()) {
    // User is within their "generic" trial period...
}
```
Once you are ready to create an actual subscription for the user, you may use the newSubscription method as usual:
```php
$user      = User::find(1);
$plan_code = // Paystack Paystack Code  e.g PLN_gx2wn530m0i3w3m.

// With Paystack card auth token for customer.
$user->newSubscription($plan_code, 'main')->create($auth_token);

// Or
$user->newSubscription($plan_code, 'main')->create();
```

### Customers
#### Creating Customers
Occasionally, you may wish to create a Paystack customer without beginning a subscription. I would recommend you even do this at sign up. You may accomplish this using the `createAsPaystackCustomer` method:
```php
$user->createAsPaystackCustomer();
```

By default, the package passes values set on the `email`, `first_name`, `last_name` and `phone` columns if they are set on the model class that uses the `Billable` trait (most likely user).

The columns can be overridden to pass your custom columns for each of those fields by simply defining a method on the model as follows;

```php
// If the first name column is called `f_name` on your model.
public function paystackCustomerFirstName(): string
{
    return explode(' ', $this->f_name) ?? '';
}

// If you have a full name column on your model instead.
public function paystackCustomerFirstName(): string
{
    return explode(' ', $this->full_name)[0] ?? '';
}

// If you have a full name column on your model relation such as `profile` instead.
public function paystackCustomerFirstName(): string
{
    return explode(' ', $this->profile->full_name)[0] ?? '';
}
```

Other columns can also be overwritten by defining and implementing any of the following methods;

```php
// Last name.
paystackCustomerLastName()

// Phone.
paystackCustomerPhone()

//Email.
paystackCustomerEmail()

Once the customer has been created in Paystack, you may begin a subscription at a later date.

### Payment Methods
#### Retrieving Authenticated Payment Methods
The cards method on the billable model instance returns a collection of `Veeqtoh\Cashier\Card` instances:
```php
$cards = $user->cards();
```
#### Deleting Payment Methods
To delete a card, you should first retrieve the customer's authentications with the card method. Then, you may call the delete method on the instance you wish to delete:
```php
foreach ($user->cards() as $card) {
    $card->delete();
}
```
To delete all card payment authentication for a customer
```php
$user->deleteCards();
```

### Handling Paystack Webhooks
Paystack can notify your application of a variety of events via webhooks. By default, a route that points to Cashier's webhook controller is automatically registered by the Cashier service provider. This controller will handle all incoming webhook requests.

By default, this controller will automatically handle cancelling subscriptions that have too many failed charges (as defined by your paystack settings), charge success, transfer success or fail, invoice updates and subscription changes; however, as we'll soon discover, you can extend this controller to handle any webhook event you like.

To ensure your application can handle Paystack webhooks, be sure to configure the webhook URL in your Paystack dashboard settings. By default, Cashier's webhook controller responds to the /paystack/webhook URL path. The full list of all webhooks supported by Paystack are listed [here](https://paystack.com/docs/payments/webhooks/)

_Make sure you protect incoming requests with Cashier's included webhook signature verification middleware._

#### Webhooks & CSRF Protection
Since Paystack webhooks need to bypass Laravel's [CSRF protection](https://laravel.com/docs/11.x/csrf), you should ensure that Laravel does not attempt to validate the CSRF token for incoming Stripe webhooks. To accomplish this, you should exclude `paystack/*` from CSRF protection in your application's `bootstrap/app.php` file:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'paystack/*',
    ]);
});
```

### Defining Webhook Event Handlers
Cashier automatically handles subscription cancellations for failed charges and other common Paystack webhook events. However, if you have additional webhook events you would like to handle, you may do so by listening to the following events that are dispatched by Cashier:
- `Veeqtoh\Cashier\Events\WebhookReceived`
- `Veeqtoh\Cashier\Events\WebhookHandled`

Both events contain the full payload of the Paystack webhook. For example, if you wish to handle the `invoice.payment_failed` webhook, you may register a [listener](https://laravel.com/docs/11.x/events#defining-listeners) that will handle the event:
```php
<?php

namespace App\Listeners;

use Veeqtoh\Cashier\Events\WebhookReceived;

class PaystackEventListener
{
    /**
     * Handle received Paystack webhooks.
     */
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['event'] === 'invoice.payment_failed') {
            // Handle the incoming event...
        }
    }
}
```

Another approach to handling additional Paystack webhook events is to extend the Webhook controller. Your method names should correspond to Cashier's expected convention, specifically, methods should be prefixed with handle and the "camel case" name of the Paystack webhook event you wish to handle.
```php
<?php

namespace App\Http\Controllers;

use Veeqtoh\Cashier\Http\Controllers\WebhookController as CashierController;

class WebhookController extends CashierController
{
    /**
     * Handle invoice payment succeeded.
     *
     * @param  array  $payload
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoiceUpdate($payload)
    {
        // Handle The Event
    }
}
```

Next, define a route to your Cashier controller within your routes/web.php file to overwrite the default webhook route that came out-of-the-box with this package:
```php

use App\Http\Controllers\WebhookController

Route::post('paystack/webhook', 'WebhookController@handleWebhook')->name('webhook');
```

### Single Charges
#### Simple Charge
When using Paystack, the `charge` method accepts the amount you would like to charge in the lowest denominator of the currency used by your application.

If you would like to make a "one off" charge against a subscribed customer's credit card, you may use the  charge method on a billable model instance.

```php
// Paystack Accepts Charges In Kobo for Naira...
$paystackCharge = $user->charge(10000);
```
The `charge` method accepts an array as its second argument, allowing you to pass any options you wish to the underlying Paystack charge creation. Consult the Paystack documentation regarding the options available to you when creating charges:
```php
$user->charge(100, ['more_option' => $value]);
```

The charge method will throw an exception if the charge fails. If the charge is successful, the full Paystack response will be returned from the method:
```php
try {
    // Paystack Accepts Charges In Kobo for Naira...
    $response = $user->charge(10000);
} catch (Exception $e) {
    //
}
```

#### Charge With Invoice
Sometimes you may need to make a one-time charge but also generate an invoice for the charge so that you may offer a PDF receipt to your customer. The invoiceFor method lets you do just that. For example, let's invoice the customer ₦2,000.00 for a "One Time Fee":
```php
// Paystack Accepts Charges In Kobo for Naira...
$user->invoiceFor('One Time Fee', 200000);
```

The invoice will be charged immediately against the user's credit card. The invoiceFor method also accepts an array as its third argument. This array contains the billing options for the invoice item. The fourth argument accepted by the method is also an array. This final argument accepts the billing options for the invoice itself:
```php
$user->invoiceFor('Stickers', 50000, [
    'line_items' => [ ],
    'tax'        => [{"name":"VAT", "amount":2000}]
]);
```

To learn more about the additional fields supported by Paystack, check out paystack's documentation on customer creation or the corresponding Paystack documentation.

#### Refunding Charges
If you need to refund a Paystack charge, you may use the refund method. This method accepts the Paystack charge ID as its only argument:
```php
$paystackCharge = $user->charge(100);

$user->refund($paystackCharge->reference);
```

### Invoices
You may easily retrieve an array of a billable model's invoices using the invoices method:
```php
$invoices = $user->invoices();

// Include only pending invoices in the results...
$invoices = $user->invoicesOnlyPending();

// Include only paid invoices in the results...
$invoices = $user->invoicesOnlyPaid();
```

When listing the invoices for the customer, you may use the invoice's helper methods to display the relevant invoice information. For example, you may wish to list every invoice in a table, allowing the user to easily download any of them:
```html
<table>
    @foreach ($invoices as $invoice)
        <tr>
            <td>{{ $invoice->date()->toFormattedDateString() }}</td>
            <td>{{ $invoice->total() }}</td>
            <td><a href="/user/invoice/{{ $invoice->id }}">Download</a></td>
        </tr>
    @endforeach
</table>
```

#### Generating Invoice PDFs
From within a route or controller, use the `downloadInvoice` method to generate a PDF download of the invoice. This method will automatically generate the proper HTTP response to send the download to the browser:
```php
use Illuminate\Http\Request;

Route::get('user/invoice/{invoice}', function (Request $request, $invoiceId) {
    return $request->user()->downloadInvoice($invoiceId, [
        'vendor'  => 'Your Company',
        'product' => 'Your Product',
    ]);
});
```
