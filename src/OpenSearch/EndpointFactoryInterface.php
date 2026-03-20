<?php

declare (strict_types=1);
namespace Open_Search;

use Open_Search\Endpoints\Abstract_Endpoint;
/**
 * A factory for creating endpoints.
 */
interface Endpoint_Factory_Interface
{
    /**
     * Gets an endpoint.
     *
     * @phpstan-template T of AbstractEndpoint
     * @phpstan-param class-string<T> $class
     * @phpstan-return T
     */
    public function get_endpoint(string $class): Abstract_Endpoint;
}