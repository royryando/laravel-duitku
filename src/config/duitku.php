<?php

return [
    'merchant_code' => env('DUITKU_MERCHANT_CODE'),
    'api_key' => env('DUITKU_API_KEY'),
    'callback_url' => env('DUITKU_CALLBACL_URL'),
    'return_url' => env('DUITKU_RETURN_URL'),
    'env' => env('DUITKU_ENV', 'production'),
    'url' => [
        'base' => [
            'dev' => 'https://sandbox.duitku.com',
            'prod' => 'https://passport.duitku.com',
        ],
        'suffix' => [
            'inquiry' => '/webapi/api/merchant/v2/inquiry',
            'check' => '/webapi/api/merchant/transactionStatus',
            'method' => '/webapi/api/merchant/paymentmethod/getpaymentmethod',
        ],
    ],
    'timeout' => [
        'connect' => 30,
        'response' => 30,
    ]
];
