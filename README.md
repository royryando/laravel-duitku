# Laravel Duitku

A simple Duitku payment gateway library for Laravel.

## Requirments
- PHP ≥ 5.5
- Laravel ≥ 5.1

## Installation
- Install through composer

      composer require royryando/laravel-duitku

- Add the duitku service provider in config/app.php: (Laravel 5.5+ uses Package Auto-Discovery, so doesn't require you to manually add the ServiceProvider.)
  ```php
  'providers' => [
    Royryando\Duitku\DuitkuServiceProvider::class
  ];
  ```
  
## Configure
- Add required variable to `.env`
  ```dotenv
  DUITKU_MERCHANT_CODE=
  DUITKU_API_KEY=
  DUITKU_CALLBACL_URL=https://example.com/callback/payment
  DUITKU_RETURN_URL=https://example.com/callback/return
  DUITKU_ENV=dev/production
  ```

## Usage

### Get All Available Payment Method
Ref: [https://docs.duitku.com/api/id/#payment-method](https://docs.duitku.com/api/id/#payment-method)

Call paymentMethods function from Duitku facade with the integer parameter is amount

`Duitku::paymentMethods(100000)`

The return is an array of array, example:
```php
[
    ...
    [
        'code' => 'M1',
        'name' => 'Bank Mandiri',
        'image' => 'https://example.com/image.jpg',
        'fee' => 0
    ],
    ...
]
```

### Create Invoice
Ref: [https://docs.duitku.com/api/id/#request-transaction](https://docs.duitku.com/api/id/#request-transaction)

Create invoice or inquiry by calling createInvoice from Duitku facade with these parameter:

Order Id, amount, payment method, product name, customer name, cutomer email, expiry in minute
  
`Duitku::createInvoice('ORDER_ID', 100000, 'M1', 'Product Name', 'John Doe', 'john@example.com', 120);`

The return if success:
```php
[
  'success' => true,
  'reference' => 'D7999PJ38HNY7TSKHSGX',
  'payment_url' => 'https://url.to.payment.example.com/',
  'va_number' => '0000123123123',
  'amount' => 100000,
  'message' => 'SUCCESS' // message from Duitku
]
```

The return if not success:
```php
[
  'success' => false,
  'message' => 'The selected payment channel not available' // message from Duitku
]
```

### Check Invoice Status
Ref: [https://docs.duitku.com/api/id/#check-transaction](https://docs.duitku.com/api/id/#check-transaction)

Check invoice or inquiry status by calling
  
`Duitku::checkInvoiceStatus('order ID')`
  
The return is an array, example:
  
```php
[
  'reference' => 'D7999PJ38HNY7TSKHSGX', // reference code from Duitku
  'amount' => 100000,
  'message' => 'SUCCESS',
  'code' => '00', // 00=>Success, 01=>Pending, 02=>Failed/Expired
]
```

### Handle Callback
Ref: [https://docs.duitku.com/api/id/#callback](https://docs.duitku.com/api/id/#callback)

- Create a new controller and extend `Royryando\Duitku\Http\Controllers\DuitkuBaseController`

  ```php
  use Royryando\Duitku\Http\Controllers\DuitkuBaseController;
  
  class DuitkuController extends DuitkuBaseController
  {
      //
  }
  ```  

  This controller will handle all callback requests from Duitku and store the success/failed payment function

- Inside the controller, override `onPaymentSuccess` function. This function will triggered if receiving a successful transaction callback
  ```php
  ...
      protected function onPaymentSuccess(
          string $orderId, string $productDetail, int $amount, string $paymentCode,
          string $shopeeUserHash, string $reference, string $additionalParam
      ): void
      {
          // Your code here
      }
  ...
  ```

- Inside the controller, override `onPaymentFailed` function. This function will triggered if receiving a failed status from callback

  ```php
  ...
      protected function onPaymentFailed(
          string $orderId, string $productDetail, int $amount, string $paymentCode,
          string $shopeeUserHash, string $reference, string $additionalParam
      ): void
      {
          // Your code here
      }
  ...
  ```

- Add route in your application route web.php with the function of paymentCallback

  ```php
  Route::post('callback/payment', [\App\Http\Controllers\DuitkuController::class, 'paymentCallback']);
  ```
  
- Exclude the callback route from CSRF verification
  
  Edit `App\Http\Middleware\VerifyCsrfToken.php`

  ```php
  protected $except = [
      'callback/payment',
  ];
  ```
  
## TODO
- Add tests
- Add support for Return Callback
- Add support for Disbursement API
