<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Exception thrown when a 429 Too Many Requests HTTP error occurs.
 */
class Too_Many_Requests_Http_Exception extends Http_Exception
{
    public function __construct(string $message = '', array $headers = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(429, $message, $headers, $code, $previous);
    }
}