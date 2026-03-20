<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Exception thrown when a 400 Bad Request HTTP error occurs.
 */
class Bad_Request_Http_Exception extends Http_Exception
{
    public function __construct(string $message = '', array $headers = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(400, $message, $headers, $code, $previous);
    }
}