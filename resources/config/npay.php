<?php

/*
 * This file is for npay laravel integartion for developers.
 *
 * (c)peterNdeke <ndekepeter2015@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

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

