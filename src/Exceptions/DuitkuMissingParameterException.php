<?php

namespace Royryando\Duitku\Exceptions;

/**
 * Class DuitkuMissingParameterException
 * @package RoyRyando\Duitku\Exceptions
 */
class DuitkuMissingParameterException extends \Exception
{
    protected $message = 'Required parameter is missing.';
}
