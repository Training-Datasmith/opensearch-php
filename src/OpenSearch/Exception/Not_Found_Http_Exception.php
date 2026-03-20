<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Exception thrown when a 404 Not Found HTTP error occurs.
 */
class Not_Found_Http_Exception extends Http_Exception
{
    public function __construct(string $message = '', array $headers = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(404, $message, $headers, $code, $previous);
    }
}