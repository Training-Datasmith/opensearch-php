<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Exception thrown when a 408 Request Timeout HTTP error occurs.
 */
class Request_Timeout_Http_Exception extends Http_Exception
{
    public function __construct(string $message = '', array $headers = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(408, $message, $headers, $code, $previous);
    }
}