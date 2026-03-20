<?php

declare (strict_types=1);
namespace Open_Search\Exception;

class Not_Acceptable_Http_Exception extends Http_Exception
{
    public function __construct(string $message = '', array $headers = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(406, $message, $headers, $code, $previous);
    }
}