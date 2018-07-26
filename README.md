# NPay-Laravel


> A Laravel 5.6  Package for integrating NPay seamlessly

## Installation

[PHP](https://php.net) 5.4+ and [Composer](https://getcomposer.org) are required.

To get the latest version of Npay-laravel, simply require it using composer

```bash
composer require numerics/npay
```

Or add the following line to the require block of your `composer.json` file.

```
"numerics/npay-laravel"
```

You'll then need to run `composer install` or `composer update` to download it and have the autoloader updated.



Once npay laravel is installed, you need to register the service provider. Open up `config/app.php` and add the following to the `providers` key.

> If you use **Laravel >= 5.5** you can skip this step and go to [**`configuration`**](https://github.com/PeterNdeke/npay-laravel#configuration)

* `Numerics\Npay\NpayServiceProvider::class`

Also, register the Facade like so:

```php
'aliases' => [
    ...
    'Npay' => Numerics\Npay\Facades\Npay::class,
    ...
]
```

## Configuration

You can publish the configuration file using this command:

```bash
php artisan vendor:publish --provider="Numerics\Npay\NpayServiceProvider"
```

A configuration-file named `npay.php` with some  defaults will be placed in your `config` directory:

```php
<?php

return [
     /**
     * Api secert public Key
     *
     */
    'publicKey'=>  getenv('NPAY_PUBLIC_KEY'),

    /**
     * Api Secret Key
     *
     */
    'secretKey' => getenv('NPAY_API_KEY'),

    
    /**
     * Npay Payment URL
     *
     */
    'paymentUrl' => getenv('NPAY_PAYMENT_URL'),

    /**
     * Optional email address of the merchant
     *
     */
    'merchantEmail' => getenv('MERCHANT_EMAIL'),

];
```


##General payment flow

Though there are multiple ways to pay an order, most payment gateways expect you to follow the following flow in your checkout process:

###1. The customer is redirected to the payment provider 
After the customer has gone through the checkout process and is ready to pay and click on the pay button, the customer must be redirected to site of the payment provider.

The redirection is accomplished by submitting a form with some hidden fields. The form must post to the site of the payment provider. The hidden fields minimally specify the amount that must be paid, the order id and a hash.

The hash is calculated using the hidden form fields and a non-public secret. The hash used by the payment provider to verify if the request is valid.


###2. The customer pays on the site of the payment provider
The customer arrived on the site of the payment provider and gets to choose a payment method. All steps necessary to pay the order are taken care of by the payment provider.

###3. The customer gets redirected back
After having paid the order the customer is redirected back. In the redirection request to the shop-site some values are returned. The values are usually the order id, a paymentresult and a hash.

The hash is calculated out of some of the fields returned and a secret non-public value. This hash is used to verify if the request is valid and comes from the payment provider. It is paramount that this hash is thoroughly checked.


## Usage

Open your .env file and add your Api key, merchant email and payment url like so:

```php
NPAY_PUBLIC_KEY=xxxxxxxxxxxxxx
NPAY_API_KEY=xxxxxxxxxxxxxxxxx
NPAY_PAYMENT_URL=https://bvnpay.ng
MERCHANT_EMAIL=merchant@gmail.com
```

Set up routes and controller methods like so:

Note: you have to provide your call back URL in this order yourdomain.com/payment/getTransaction, Because that is where we will redirect you after a successfull transaction
you can also provide it as one of the params in your form that will be sent to us



```php
// Laravel 5.1.17 and above
Route::post('/setTransaction', 'PaymentController@redirectToGateway')->name('setTransaction'); 
```

```php
Route::get('/payment/getTransaction', 'PaymentController@handleGatewayCallback');
```
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Numerics\Npay\Npay;

class PaymentController extends Controller
{

    /**
     * Redirect the User to Npay Payment Page
     * @return Url
     */
    public function redirectToGateway()
    {
        $npay = new Npay();
        return $npay->getAuthorizationUrl()->redirectNow();
    }

    /**
     * Obtain Npay payment information
     * @return void
     */
    public function handleGatewayCallback()
    {
        $paymentData = $_GET['ref'];
        $npay = new Npay();
        $paymentDetails = $npay->getPaymentData($paymentData);
        dd($paymentDetails);
        // Now you can do whatever you want with the details;
        // you can save it in your database
        // you can then redirect or do whatever you want
    }
}
```


A sample HTML form will look like so:

```html
<form method="POST" action="{{ route('setTransaction') }}" accept-charset="UTF-8" class="form-horizontal" role="form">
@csrf
        <div class="row" style="margin-bottom:40px;">
          <div class="col-md-8 col-md-offset-2">
            <p>
                <div>
                   Payment Details
                </div>
            </p>
            <input type="hidden" name="email" value="ndekepeter@gmail.com"> {{-- required --}}
            <input type="hidden" name="orderID" value="345">
            <input type="hidden" name="first_name" value="Ndeke Peter">
            <input type="hidden" name="amount" value="800"> {{-- required in kobo --}}
            <input type="hidden" name="quantity" value="3">
            <input type="hidden" name="metadata" value="{{ json_encode($array = ['key_name' => 'value',]) }}" > {{-- For other necessary things you want to add to your payload. it is optional though --}}
            <input type="hidden" name="reference" value="{{ Npay::genTranxRef() }}"> {{-- required --}}
            <input type="hidden" name="apiKey" value="{{ config('npay.secretKey') }}"> {{-- required --}}
            {{ csrf_field() }} {{-- works only when using laravel 5.1, 5.2 --}}
            <input type="hidden" name="callbackUrl" value="yourdomain.com/payment/getTransaction"> {{-- required --}}


            <p>
              <button class="btn btn-success btn-lg btn-block" type="submit" value="Pay Securely!">
              <i class="fa fa-plus-circle fa-lg"></i> Pay Securely!
              </button>
            </p>
          </div>
        </div>
</form>
```

When clicking the submit button the customer gets redirected to the Npay site.

so hopefully, customer pay the order and will be redirected back to the specified call back URL with the payment status

we must validate request coming to our payment gateway
