<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Exception thrown when a 403 Forbidden HTTP error occurs.
 */
class Forbidden_Http_Exception extends Http_Exception
{
    public function __construct(string $message = '', array $headers = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(403, $message, $headers, $code, $previous);
    }
}