<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Interface for HTTP error exceptions.
 */
interface Http_Exception_Interface extends Open_Search_Exception_Interface
{
    /**
     * Returns the status code.
     */
    public function get_status_code(): int;
    /**
     * Returns response headers.
     */
    public function get_headers(): array;
}