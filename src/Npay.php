<?php

/*
 * This file is for npay laravel integartion for developers.
 *
 * (c)peterNdeke <ndekepeter2015@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Numerics\Npay;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Numerics\Npay\Exceptions\NullException;
use Numerics\Npay\Exceptions\VerificationFailedException;

class Npay
{
    /**
     * Transaction Verification Successful
     */
    const VS = 'Transaction Successfull';

    /**
     *  Invalid Transaction reference
     */
    const ITF = "Invalid Transaction";

    /**
     * Secret API Key from Npay
     * @var string
     */
    protected $apiKey;

    /**
     * an Instance of Http Guzzle Client
     * @var Client
     */
    protected $client;

    /**
     *  Response from requests made to Npay
     * @var mixed
     */
    protected $response;

    /**
     * Npay base  API Url
     * @var string
     */
    protected $baseUrl;

    /**
     * relative  Url - Npay Payment Pay Login
     * @var string
     */
    protected $relativeUrl;

    public function __construct()
    {
        $this->setKey();
        $this->setBaseUrl();
        $this->setRequestOptions();
    }

    /**
     * Get Base Url from npay config file
     */
    public function setBaseUrl()
    {
        $this->baseUrl = Config::get('npay.paymentUrl');
    }

    /**
     * Get api secret key from npay config file
     */
    public function setKey()
    {
        $this->secretKey = Config::get('npay.publicKey');
    }

    /**
     * Set options for making the Client request
     */
    private function setRequestOptions()
    {
        $authBearer = 'Bearer '. $this->secretKey;

        $this->client = new Client(
            [
                'base_uri' => $this->baseUrl,
                'headers' => [
                    'Authorization' => $authBearer,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json'
                ]
            ]
        );
    }

    /**
     * Initiate a payment request to npay
     * @return npay
     */
    public function makePaymentRequest()
    {
        $data = [
            "amount" => intval(request()->amount),
            "reference" => request()->reference,
            "email" => request()->email,
            "apiKey" => request()->apiKey,
            "plan" => request()->plan,
            "first_name" => request()->first_name,
            "last_name" => request()->last_name,
            "callbackUrl" => request()->callbackUrl,
           
            'metadata' => request()->metadata 
        ];

        // Remove the fields which were not sent (value would be null)
        array_filter($data);

        $this->setHttpResponse('/api/initialize', 'POST', $data);

        return $this;
    }


    /**
     * @param string $relativeUrl
     * @param string $method
     * @param array $body
     * @return npay
     * @throws NullException
     */
    private function setHttpResponse($relativeUrl, $method, $body = [])
    {
        if (is_null($method)) {
            throw new NullException("Empty method not allowed");
        }

        $this->response = $this->client->{strtolower($method)}(
            $this->baseUrl . $relativeUrl,
            ["body" => json_encode($body)]
        );

        return $this;
    }

    /**
     * Get the authorization url from the callback response
     * @return npay
     */
    public function getAuthorizationUrl()
    {
        $this->makePaymentRequest();

        $this->url = $this->getResponse()['data']['authorization_url'];
        

        return $this;
    }
    public function getReference()
     {
        $reference = $this->getResponse()['data']['reference'];
        return $reference;
     }

    /**
     * Hit npay Gateway to Verify that the transaction is valid
     */
    private function verifyTransactionAtGateway($reference)
    {
       // $transactionRef = request()->query('trxref');
        // $transactionRef = 'aj81nR3mEFbWP0uQPWzW4oJqdWc6T4';

        $relativeUrl = "/api/verifyTransaction/{$reference}";

        $this->response = $this->client->get($this->baseUrl . $relativeUrl, []);
    }

    /**
     * True or false condition whether the transaction is verified
     * @return boolean
     */
    public function isTransactionVerificationValid($reference)
    {
        $this->verifyTransactionAtGateway($reference);

        $result = $this->getResponse()['message'];

        switch ($result) {
            case self::VS:
                $validate = true;
                break;
            case self::ITF:
                $validate = false;
                break;
            default:
                $validate = false;
                break;
        }

        return $validate;
    }

    /**
     * Get Payment details if the transaction was verified successfully
     * @return json
     * @throws VerificationFailedException
     */
    public function getPaymentData($reference)
    {
        if ($this->isTransactionVerificationValid($reference)) {
            return $this->getResponse();
        } else {
           // return $this->getResponse();
            throw new VerificationFailedException("Invalid Transaction Reference");
        }
    }

    /**
     * Fluent method to redirect to npay Payment Page
     */
    public function redirectNow()
    {
        return redirect($this->url);
    }

    /**
     * Get Access code from transaction callback respose
     * @return string
     */
    public function getAccessCode()
    {
        return $this->getResponse()['data']['access_code'];
    }

    /**
     * Generate a Unique Transaction Reference
     * @return string
     */
    public function genTranxRef()
    {
        return GenTransRef::getToken();
    }

    /**
     * Get all the customers that have made transactions on your platform
     * @return array
     */
    public function getAllCustomers()
    {
        $this->setRequestOptions();

        return $this->setHttpResponse("/customer", 'GET', [])->getData();
    }

    /**
     * Get all the plans that you have on npay
     * @return array
     */
    public function getAllPlans()
    {
        $this->setRequestOptions();

        return $this->setHttpResponse("/plan", 'GET', [])->getData();
    }

    /**
     * Get all the transactions that have happened overtime
     * @return array
     */
    public function getAllTransactions()
    {
        $this->setRequestOptions();

        return $this->setHttpResponse("/transaction", 'GET', [])->getData();
    }

    /**
     * Get the whole response from a get operation
     * @return array
     */
    private function getResponse()
    {
        return json_decode($this->response->getBody(), true);
    }

    /**
     * Get the data response from a get operation
     * @return array
     */
    private function getData()
    {
        return $this->getResponse()['data'];
    }

    /**
     * Create a plan
     */
    public function createPlan()
    {
        $data = [
            "name" => request()->name,
            "description" => request()->desc,
            "amount" => intval(request()->amount),
            "interval" => request()->interval,
            "send_invoices" => request()->send_invoices,
            "send_sms" => request()->send_sms,
            "currency" => request()->currency,
        ];

        $this->setRequestOptions();

        $this->setHttpResponse("/plan", 'POST', $data);

    }

    /**
     * Fetch any plan based on its plan id or code
     * @param $plan_code
     * @return array
     */
    public function fetchPlan($plan_code)
    {
        $this->setRequestOptions();
        return $this->setHttpResponse('/plan/' . $plan_code, 'GET', [])->getResponse();
    }

    /**
     * Update any plan's details based on its id or code
     * @param $plan_code
     * @return array
     */
    public function updatePlan($plan_code)
    {
        $data = [
            "name" => request()->name,
            "description" => request()->desc,
            "amount" => intval(request()->amount),
            "interval" => request()->interval,
            "send_invoices" => request()->send_invoices,
            "send_sms" => request()->send_sms,
            "currency" => request()->currency,
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/plan/' . $plan_code, 'PUT', $data)->getResponse();
    }

    /**
     * Create a customer
     */
    public function createCustomer()
    {
        $data = [
            "email" => request()->email,
            "first_name" => request()->fname,
            "last_name" => request()->lname,
            "phone" => request()->phone,
            "metadata" => request()->additional_info /* key => value pairs array */

        ];

        $this->setRequestOptions();
        $this->setHttpResponse('/customer', 'POST', $data);
    }

    /**
     * Fetch a customer based on id or code
     * @param $customer_id
     * @return array
     */
    public function fetchCustomer($customer_id)
    {
        $this->setRequestOptions();
        return $this->setHttpResponse('/customer/'. $customer_id, 'GET', [])->getResponse();
    }

    /**
     * Update a customer's details based on their id or code
     * @param $customer_id
     * @return array
     */
    public function updateCustomer($customer_id)
    {
        $data = [
            "email" => request()->email,
            "first_name" => request()->fname,
            "last_name" => request()->lname,
            "phone" => request()->phone,
            "metadata" => request()->additional_info /* key => value pairs array */

        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/customer/'. $customer_id, 'PUT', $data)->getResponse();
    }

    /**
     * Export transactions in .CSV
     * @return array
     */
    public function exportTransactions()
    {
        $data = [
            "from" => request()->from,
            "to" => request()->to,
            'settled' => request()->settled
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/transaction/export', 'GET', $data)->getResponse();
    }

    /**
     * Create a subscription to a plan from a customer.
     */
    public function createSubscription()
    {
        $data = [
            "customer" => request()->customer, //Customer email or code
            "plan" => request()->plan,
            "authorization" => request()->authorization_code
        ];

        $this->setRequestOptions();
        $this->setHttpResponse('/subscription', 'POST', $data);
    }

    /**
     * Enable a subscription using the subscription code and token
     * @return array
     */
    public function enableSubscription()
    {
        $data = [
            "code" => request()->code,
            "token" => request()->token,
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/subscription/enable', 'POST', $data)->getResponse();
    }

    /**
     * Disable a subscription using the subscription code and token
     * @return array
     */
    public function disableSubscription()
    {
        $data = [
            "code" => request()->code,
            "token" => request()->token,
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/subscription/disable', 'POST', $data)->getResponse();
    }

    /**
     * Fetch details about a certain subscription
     * @param mixed $subscription_id
     * @return array
     */
    public function fetchSubscription($subscription_id)
    {
        $this->setRequestOptions();
        return $this->setHttpResponse('/subscription/'.$subscription_id, 'GET', [])->getResponse();
    }

    /**
     * Create pages you can share with users using the returned slug
     */
    public function createPage()
    {
        $data = [
            "name" => request()->name,
            "description" => request()->description,
            "amount" => request()->amount
        ];

        $this->setRequestOptions();
        $this->setHttpResponse('/page', 'POST', $data);
    }

    /**
     * Fetches all the pages the merchant has
     * @return array
     */
    public function getAllPages()
    {
        $this->setRequestOptions();
        return $this->setHttpResponse('/page', 'GET', [])->getResponse();
    }

    /**
     * Fetch details about a certain page using its id or slug
     * @param mixed $page_id
     * @return array
     */
    public function fetchPage($page_id)
    {
        $this->setRequestOptions();
        return $this->setHttpResponse('/page/'.$page_id, 'GET', [])->getResponse();
    }

    /**
     * Update the details about a particular page
     * @param $page_id
     * @return array
     */
    public function updatePage($page_id)
    {
        $data = [
            "name" => request()->name,
            "description" => request()->description,
            "amount" => request()->amount
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/page/'.$page_id, 'PUT', $data)->getResponse();
    }
}
