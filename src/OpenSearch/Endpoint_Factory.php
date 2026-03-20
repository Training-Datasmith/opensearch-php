<?php

declare (strict_types=1);
namespace Open_Search;

use Open_Search\Endpoints\Abstract_Endpoint;
use Open_Search\Serializers\Serializer_Interface;
use Open_Search\Serializers\Smart_Serializer;
use ReflectionClass;
/**
 * A factory for creating endpoints.
 */
class Endpoint_Factory implements Endpoint_Factory_Interface
{
    public function __construct(private ?Serializer_Interface $serializer = null)
    {
    }
    /**
     * {@inheritdoc}
     */
    public function get_endpoint(string $class): Abstract_Endpoint
    {
        return $this->create_endpoint($class);
    }
    private function get_serializer(): Serializer_Interface
    {
        if ($this->serializer === null) {
            $this->serializer = new Smart_Serializer();
        }
        return $this->serializer;
    }
    /**
     * Creates an endpoint.
     *
     * @phpstan-template T of AbstractEndpoint
     * @phpstan-param class-string<T> $class
     * @phpstan-return T
     * @throws \ReflectionException
     */
    private function create_endpoint(string $class): Abstract_Endpoint
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->get_constructor();
        if ($constructor && $constructor->get_parameters()) {
            return new $class($this->get_serializer());
        }
        return new $class();
    }
}