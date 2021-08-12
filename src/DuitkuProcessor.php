<?php

namespace Royryando\Duitku;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Royryando\Duitku\Enums\DuitkuDefaultCode;
use Royryando\Duitku\Exceptions\DuitkuAuthException;
use Royryando\Duitku\Exceptions\DuitkuPaymentChannelUnavailableException;
use Royryando\Duitku\Exceptions\DuitkuMissingParameterException;
use Royryando\Duitku\Exceptions\DuitkuTransactionNotFoundException;

class DuitkuProcessor
{
    private $env;
    private $merchantCode;
    private $apiKey;
    private $client;
    private $baseUrl;
    private $inquiryUrl;
    private $checkUrl;
    private $methodUrl;
    private $callbackUrl;
    private $returnUrl;

    public function __construct() {
        $this->merchantCode = config('duitku.merchant_code');
        $this->apiKey = config('duitku.api_key');
        $this->env = config('duitku.env');
        if ($this->env == 'production') {
            $this->baseUrl = config('duitku.url.base.prod');
        } else {
            $this->baseUrl = config('duitku.url.base.dev');
        }
        $this->inquiryUrl = config('duitku.url.suffix.inquiry');
        $this->checkUrl = config('duitku.url.suffix.check');
        $this->methodUrl = config('duitku.url.suffix.method');
        $this->callbackUrl = config('duitku.callback_url');
        $this->returnUrl = config('duitku.return_url');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => config('duitku.timeout.response'),
            'connect_timeout' => config('duitku.timeout.connect')
        ]);
    }

    /**
     * Get all active payment methods
     * @param int $amount
     * @return array of (code, name, image, fee)
     * @throws DuitkuAuthException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function paymentMethods(int $amount): array
    {
        $datetime = date('Y-m-d H:i:s');
        $signature = hash(
            'sha256',
            $this->merchantCode . $amount . $datetime . $this->apiKey
        );

        $options = [
            'json' => [
                'merchantCode' => (string)$this->merchantCode,
                'amount' => (int)$amount,
                'datetime' => (string)$datetime,
                'signature' => (string)$signature
            ]
        ];

        try {
            $response = $this->client->post($this->methodUrl, $options);
            $response = json_decode($response->getBody(), true);
            if (isset($response['responseCode']) && $response['responseCode'] == DuitkuDefaultCode::SUCCESS) {
                // SUCCESS
                $methods = $response['paymentFee'];
                $returnMethod = [];
                foreach ($methods as $method) {
                    array_push($returnMethod, [
                        'code' => $method['paymentMethod'] ?? null,
                        'name' => $method['paymentName'] ?? null,
                        'image' => $method['paymentImage'] ?? null,
                        'fee' => (int)($method['totalFee'] ?? 0)
                    ]);
                }
                return $returnMethod;
            }
            return [];
        } catch (RequestException $ex) {
            if (str_contains($ex->getMessage(), 'Invalid Signature')) {
                throw new DuitkuAuthException();
            }
            throw $ex;
        }
    }

    /**
     * Create invoice
     * @param string $orderId
     * @param int $amount
     * @param string $method
     * @param string $productDetails
     * @param string $customerName
     * @param string $customerEmail
     * @param int $expiry
     * @param array $optionsParam
     * @return array
     * @throws DuitkuAuthException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws DuitkuMissingParameterException
     * @throws DuitkuPaymentChannelUnavailableException
     */
    public function createInvoice(
        string $orderId,
        int $amount,
        string $method,
        string $productDetails,
        string $customerName,
        string $customerEmail,
        int $expiry,
        array $optionsParam = []
    ): array
    {
        if (
            empty($orderId) || empty($amount) || empty($method) || empty($productDetails) || empty($customerName) ||
            empty($customerEmail) || empty($expiry)
        ) {
            throw new DuitkuMissingParameterException();
        }
        $signature = md5("{$this->merchantCode}{$orderId}{$amount}{$this->apiKey}");
        $json = [
            "merchantCode" => $this->merchantCode,
            "paymentAmount" => $amount,
            "merchantOrderId" => $orderId,
            "productDetails" => $productDetails,
            "email" => $customerEmail,
            "customerVaName" => $customerName,
            "paymentMethod" => $method,
            "returnUrl" => $this->returnUrl,
            "callbackUrl" => $this->callbackUrl,
            "signature" => $signature,
            "expiryPeriod" => $expiry
        ];
        if (isset($optionsParam['additionalParam'])) {
            $json['additionalParam'] = $optionsParam['additionalParam'];
        }
        if (isset($optionsParam['phoneNumber'])) {
            $json['phoneNumber'] = $optionsParam['phoneNumber'];
        }
        if (isset($optionsParam['merchantUserInfo'])) {
            $json['merchantUserInfo'] = $optionsParam['merchantUserInfo'];
        }
        $options = [
            'json' => $json,
        ];

        try {
            $response = $this->client->post($this->inquiryUrl, $options);
            $response = json_decode($response->getBody(), true);
            if (isset($response['statusCode']) && $response['statusCode'] == DuitkuDefaultCode::SUCCESS) {
                // SUCCESS
                return [
                    'success' => true,
                    'reference' => $response['reference'] ?? null,
                    'payment_url' => $response['paymentUrl'] ?? null,
                    'va_number' => $response['vaNumber'] ?? null,
                    'amount' => (int)($response['amount'] ?? 0),
                    'message' => $response['statusMessage'] ?? null
                ];
            }
            return [
                'success' => false,
                'message' => $response['statusMessage']
            ];
        } catch (RequestException $ex) {
            if (str_contains($ex->getMessage(), 'Invalid Signature')) {
                throw new DuitkuAuthException();
            }
            if (str_contains($ex->getMessage(), 'Payment channel not available')) {
                throw new DuitkuPaymentChannelUnavailableException($method);
            }
            throw $ex;
        }
    }

    public function configs() {
        return [
            $this->env,
            $this->merchantCode,
            $this->apiKey,
            $this->client,
            $this->inquiryUrl,
            $this->checkUrl,
            $this->methodUrl,
            $this->callbackUrl,
            $this->returnUrl,
        ];
    }

    /**
     * Check invoice status
     * @param string $orderId
     * @return array
     * @throws DuitkuAuthException
     * @throws DuitkuTransactionNotFoundException
     */
    public function checkInvoiceStatus(string $orderId): array
    {
        $signature = md5("{$this->merchantCode}{$orderId}{$this->apiKey}");
        $options = [
            'json' => [
                'merchantCode' => (string)$this->merchantCode,
                'merchantOrderId' => $orderId,
                'signature' => $signature
            ]
        ];

        try {
            $response = $this->client->post($this->checkUrl, $options);
            $response = json_decode($response->getBody(), true);

            return [
                'reference' => $response['reference'] ?? null,
                'amount' => (int)($response['amount'] ?? 0),
                'message' => $response['statusMessage'] ?? null,
                'code' => $response['statusCode'] ?? null,
            ];
        } catch (RequestException $ex) {
            if (str_contains($ex->getMessage(), 'Invalid Signature')) {
                throw new DuitkuAuthException();
            }
            if (str_contains($ex->getMessage(), 'Transaction not found')) {
                throw new DuitkuTransactionNotFoundException($orderId);
            }
            throw $ex;
        }
    }

}
