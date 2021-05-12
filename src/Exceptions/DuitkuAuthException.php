<?php

namespace Royryando\Duitku\Exceptions;

/**
 * Class DuitkuAuthException
 * @package RoyRyando\Duitku\Exceptions
 */
class DuitkuAuthException extends \Exception
{
    protected $message = 'Authentication to Duitku failed, make sure you set the right Merchant Code and API Key.';
}
