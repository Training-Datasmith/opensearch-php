<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Exception thrown when a 401 Unauthorized HTTP error occurs.
 */
class Unauthorized_Http_Exception extends Http_Exception
{
    public function __construct(string $message = '', array $headers = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(401, $message, $headers, $code, $previous);
    }
}