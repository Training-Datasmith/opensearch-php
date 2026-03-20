<?php

declare (strict_types=1);
namespace Open_Search;

use Guzzle_Http\Psr7\Http_Factory;
use Open_Search\Aws\Signing_Client_Factory;
use Open_Search\Http_Client\Guzzle_Http_Client_Factory;
use Open_Search\Serializers\Smart_Serializer;
use Psr\Log\Logger_Interface;
/**
 * Creates an OpenSearch client using Guzzle.
 */
class Guzzle_Client_Factory implements Client_Factory_Interface
{
    public function __construct(protected int $max_retries = 0, protected ?Logger_Interface $logger = null, protected ?Signing_Client_Factory $aws_signing_http_client_factory = null)
    {
    }
    /**
     * @param array<string,mixed> $options
     *   The Guzzle client options.
     */
    public function create(array $options): Client
    {
        // Clean up the options array for the Guzzle HTTP Client.
        if (isset($options['auth_aws'])) {
            $aws_auth = $options['auth_aws'];
            unset($options['auth_aws']);
        }
        $http_client = (new Guzzle_Http_Client_Factory($this->max_retries, $this->logger))->create($options);
        if (isset($aws_auth)) {
            if (!isset($aws_auth['host'])) {
                // Get the host from the base URI.
                $aws_auth['host'] = parse_url((string) $options['base_uri'], PHP_URL_HOST);
            }
            $http_client = $this->get_signing_client_factory()->create($http_client, $aws_auth);
        }
        $http_factory = new Http_Factory();
        $serializer = new Smart_Serializer();
        $request_factory = new Request_Factory($http_factory, $http_factory, $http_factory, $serializer);
        $transport = (new Transport_Factory())->set_http_client($http_client)->set_request_factory($request_factory)->create();
        return new Client($transport, new Endpoint_Factory($serializer), []);
    }
    /**
     * Gets the AWS signing client factory.
     */
    protected function get_signing_client_factory(): Signing_Client_Factory
    {
        if ($this->aws_signing_http_client_factory === null) {
            $this->aws_signing_http_client_factory = new Signing_Client_Factory();
        }
        return $this->aws_signing_http_client_factory;
    }
}