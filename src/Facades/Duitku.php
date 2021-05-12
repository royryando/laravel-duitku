<?php

namespace Royryando\Duitku\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array paymentMethods(int $amount)
 * @method static array createInvoice(string $orderId, int $amount, string $method, string $productDetails,string $customerName, string $customerEmail, int $expiry, array $options = [])
 * @method static array checkInvoiceStatus(string $orderId)
 */
class Duitku extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'duitku';
    }
}
