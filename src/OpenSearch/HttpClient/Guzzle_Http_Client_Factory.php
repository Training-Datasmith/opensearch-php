<?php

declare (strict_types=1);
namespace Open_Search\Http_Client;

use Guzzle_Http\Client as GuzzleClient;
use Guzzle_Http\Handler_Stack;
use Guzzle_Http\Middleware;
use Open_Search\Client;
use Psr\Log\Logger_Interface;
/**
 * Builds an OpenSearch client using Guzzle.
 */
class Guzzle_Http_Client_Factory implements Http_Client_Factory_Interface
{
    public function __construct(protected int $max_retries = 0, protected ?Logger_Interface $logger = null)
    {
    }
    /**
     * {@inheritdoc}
     */
    public function create(array $options): Guzzle_Client
    {
        if (!isset($options['base_uri'])) {
            throw new \InvalidArgumentException('The base_uri option is required.');
        }
        $middlewares = [];
        if (isset($options['middleware'])) {
            $middlewares = $options['middleware'];
            unset($options['middleware']);
            if (!is_array($middlewares)) {
                $middlewares = [$middlewares];
            }
        }
        // Set default configuration.
        $defaults = ['headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'User-Agent' => sprintf('opensearch-php/%s (%s; PHP %s)', Client::VERSION, PHP_OS, PHP_VERSION)]];
        // Merge the default options with the provided options.
        $config = array_merge_recursive($defaults, $options);
        $stack = Handler_Stack::create();
        // Handle retries if max_retries is set.
        if ($this->max_retries > 0) {
            $decider = new Guzzle_Retry_Decider($this->max_retries, $this->logger);
            $stack->push(Middleware::retry($decider(...)));
        }
        // Attach any middlewares that look valid.
        foreach ($middlewares as $name => $middleware) {
            if (is_callable($middleware)) {
                // If a name was specified in the options array, use it.
                if (is_int($name) || is_numeric($name)) {
                    $name = '';
                }
                $stack->push($middleware, $name);
            }
        }
        $config['handler'] = $stack;
        return new Guzzle_Client($config);
    }
}