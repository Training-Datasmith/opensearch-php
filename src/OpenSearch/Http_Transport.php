<?php

declare (strict_types=1);
namespace Open_Search;

use Open_Search\Exception\Http_Exception_Factory;
use Open_Search\Serializers\Serializer_Interface;
use Psr\Http\Client\Client_Interface;
use Psr\Http\Message\Request_Interface;
/**
 * Transport that uses PSR-7, PSR-17 and PSR-18 interfaces.
 */
final class Http_Transport implements Transport_Interface
{
    public function __construct(protected Client_Interface $client, protected Request_Factory_Interface $request_factory, protected Serializer_Interface $serializer)
    {
    }
    /**
     * Create a new request.
     */
    public function create_request(string $method, string $uri, array $params = [], mixed $body = null, array $headers = []): Request_Interface
    {
        return $this->request_factory->create_request($method, $uri, $params, $body, $headers);
    }
    /**
     * {@inheritdoc}
     */
    public function send_request(string $method, string $uri, array $params = [], mixed $body = null, array $headers = []): iterable|string|null
    {
        // @todo Remove support for legacy options in 3.0.0.
        // @phpstan-ignore isset.offset
        if (isset($headers['client']['headers'])) {
            $headers = array_merge($headers, $headers['client']['headers']);
        }
        unset($headers['client']);
        $request = $this->create_request($method, $uri, $params, $body, $headers);
        $response = $this->client->send_request($request);
        $status_code = $response->get_status_code();
        $response_body = $response->get_body()->get_contents();
        $response_headers = $response->get_headers();
        $data = $this->serializer->deserialize($response_body, $response_headers);
        // Status code >= 200 < 300 is a success.
        // Status code >= 300 < 400 is a redirect and should be handled by the client.
        if ($status_code >= 400) {
            // Throw an HTTP exception.
            throw Http_Exception_Factory::create($status_code, $data);
        }
        return $data;
    }
}