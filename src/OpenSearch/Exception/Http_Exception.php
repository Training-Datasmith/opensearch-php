<?php

declare (strict_types=1);
namespace Open_Search\Exception;

use Open_Search\Common\Exceptions\Open_Search_Exception;
/**
 * Exception thrown when an HTTP error occurs.
 *
 * @phpstan-consistent-constructor
 * @phpstan-ignore class.implementsDeprecatedInterface
 */
class Http_Exception extends \RuntimeException implements Http_Exception_Interface, Open_Search_Exception
{
    public function __construct(protected readonly int $status_code, string $message = '', protected readonly array $headers = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    public function get_status_code(): int
    {
        return $this->status_code;
    }
    public function get_headers(): array
    {
        return $this->headers;
    }
}