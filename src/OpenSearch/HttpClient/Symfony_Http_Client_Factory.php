<?php

declare (strict_types=1);
namespace Open_Search\Http_Client;

use Open_Search\Client;
use Psr\Log\Logger_Interface;
use Symfony\Component\Http_Client\Http_Client;
use Symfony\Component\Http_Client\Psr18Client;
use Symfony\Component\Http_Client\Retryable_Http_Client;
/**
 * Builds an OpenSearch client using Symfony.
 */
class Symfony_Http_Client_Factory implements Http_Client_Factory_Interface
{
    public function __construct(protected int $max_retries = 0, protected ?Logger_Interface $logger = null)
    {
    }
    /**
     * {@inheritdoc}
     */
    public function create(array $options): Psr18Client
    {
        if (!isset($options['base_uri'])) {
            throw new \InvalidArgumentException('The base_uri option is required.');
        }
        // Set default configuration.
        $defaults = ['headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'User-Agent' => sprintf('opensearch-php/%s (%s; PHP %s)', Client::VERSION, PHP_OS, PHP_VERSION)]];
        $options = array_merge_recursive($defaults, $options);
        $symfony_client = Http_Client::create()->with_options($options);
        if ($this->max_retries > 0) {
            $symfony_client = new Retryable_Http_Client($symfony_client, null, $this->max_retries, $this->logger);
        }
        return new Psr18Client($symfony_client);
    }
}