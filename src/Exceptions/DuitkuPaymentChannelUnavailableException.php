<?php

namespace Royryando\Duitku\Exceptions;

/**
 * Class DuitkuPaymentChannelUnavailableException
 * @package RoyRyando\Duitku\Exceptions
 */
class DuitkuPaymentChannelUnavailableException extends \Exception
{
    protected $message = 'The selected payment channel is not available.';

    public function __construct(string $paymentChannel)
    {
        parent::__construct("The selected payment channel '$paymentChannel' is not available.",
            $this->getCode(), $this->getPrevious());
    }
}
