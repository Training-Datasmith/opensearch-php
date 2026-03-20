<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Exception thrown when a 500 Internal Server Error HTTP error occurs.
 */
class Internal_Server_Error_Http_Exception extends Http_Exception
{
    public function __construct(string $message = '', array $headers = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(500, $message, $headers, $code, $previous);
    }
}