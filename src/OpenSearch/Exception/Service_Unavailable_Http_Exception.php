<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Exception thrown when a 503 Service Unavailable HTTP error occurs.
 */
class Service_Unavailable_Http_Exception extends Http_Exception
{
    public function __construct(string $message = '', array $headers = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(503, $message, $headers, $code, $previous);
    }
}