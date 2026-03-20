<?php

declare (strict_types=1);
namespace Open_Search;

use Open_Search\Endpoints\Abstract_Endpoint;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Legacy_Endpoint_Factory::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * Provides a endpoint factory using a legacy callable.
 *
 * @deprecated in 2.4.0 and will be removed in 3.0.0. Use PsrTransport instead.
 */
class Legacy_Endpoint_Factory implements Endpoint_Factory_Interface
{
    /**
     * The endpoints callable.
     *
     * @var callable
     */
    protected $endpoints;
    public function __construct(callable $endpoints)
    {
        $this->endpoints = $endpoints;
    }
    /**
     * {@inheritdoc}
     */
    public function get_endpoint(string $class): Abstract_Endpoint
    {
        // We need to strip the base namespace from the class name for BC.
        $class = str_replace('OpenSearch\Endpoints\\', '', $class);
        $endpoint_builder = $this->endpoints;
        return $endpoint_builder($class);
    }
}