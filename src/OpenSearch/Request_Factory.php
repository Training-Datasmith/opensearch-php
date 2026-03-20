<?php

declare (strict_types=1);
namespace Open_Search;

use Open_Search\Serializers\Serializer_Interface;
use Psr\Http\Message\Request_Factory_Interface as PsrRequestFactoryInterface;
use Psr\Http\Message\Request_Interface;
use Psr\Http\Message\Stream_Factory_Interface;
use Psr\Http\Message\Uri_Factory_Interface;
/**
 * Request factory that uses PSR-7, PSR-17 and PSR-18 interfaces.
 */
final class Request_Factory implements Request_Factory_Interface
{
    public function __construct(protected Psr_Request_Factory_Interface $psr_request_factory, protected Stream_Factory_Interface $stream_factory, protected Uri_Factory_Interface $uri_factory, protected Serializer_Interface $serializer)
    {
    }
    /**
     * {@inheritdoc}
     */
    public function create_request(string $method, string $uri, array $params = [], string|array|null $body = null, array $headers = []): Request_Interface
    {
        $uri = $this->uri_factory->create_uri($uri);
        $uri = $uri->with_query($this->create_query($params));
        $request = $this->psr_request_factory->create_request($method, $uri);
        if ($body !== null) {
            $body_json = $this->serializer->serialize($body);
            $body_stream = $this->stream_factory->create_stream($body_json);
            $request = $request->with_body($body_stream);
        }
        foreach ($headers as $name => $value) {
            $request = $request->with_header($name, $value);
        }
        return $request;
    }
    /**
     * Create a query string from an array of parameters.
     */
    private function create_query(array $params): string
    {
        return http_build_query(array_map(function ($value) {
            // Ensure boolean values are serialized as strings.
            if ($value === true) {
                return 'true';
            }
            if ($value === false) {
                return 'false';
            }
            return $value;
        }, $params));
    }
}