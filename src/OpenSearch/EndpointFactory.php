<?php

declare(strict_types=1);

namespace OpenSearch;

use OpenSearch\Endpoints\AbstractEndpoint;
use OpenSearch\Serializers\SerializerInterface;
use OpenSearch\Serializers\SmartSerializer;
use ReflectionClass;

/**
 * A factory for creating endpoints.
 */
class EndpointFactory implements EndpointFactoryInterface
{
    public function __construct(private ?SerializerInterface $serializer = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(string $class): AbstractEndpoint
    {
        return $this->createEndpoint($class);
    }

    private function getSerializer(): SerializerInterface
    {
        if ($this->serializer === null) {
            $this->serializer = new SmartSerializer();
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
    private function createEndpoint(string $class): AbstractEndpoint
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor && $constructor->getParameters()) {
            return new $class($this->getSerializer());
        }
        return new $class();
    }

}
