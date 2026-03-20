<?php

declare (strict_types=1);
/**
 * Copyright OpenSearch Contributors
 * SPDX-License-Identifier: Apache-2.0
 *
 * OpenSearch PHP client
 *
 * @link      https://github.com/opensearch-project/opensearch-php/
 * @copyright Copyright (c) Elasticsearch B.V (https://www.elastic.co)
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License, Version 2.1
 *
 * Licensed to Elasticsearch B.V under one or more agreements.
 * Elasticsearch B.V licenses this file to you under the Apache 2.0 License or
 * the GNU Lesser General Public License, Version 2.1, at your option.
 * See the LICENSE file in the project root for more information.
 */
namespace Open_Search;

use Aws\Credentials\Credential_Provider;
use Aws\Credentials\Credentials;
use Aws\Credentials\Credentials_Interface;
use Guzzle_Http\Ring\Client\Curl_Handler;
use Guzzle_Http\Ring\Client\Curl_Multi_Handler;
use Guzzle_Http\Ring\Client\Middleware;
use Open_Search\Common\Exceptions\Authentication_Config_Exception;
use Open_Search\Common\Exceptions\InvalidArgumentException;
use Open_Search\Common\Exceptions\RuntimeException;
use Open_Search\Connection_Pool\Abstract_Connection_Pool;
use Open_Search\Connection_Pool\Selectors\Round_Robin_Selector;
use Open_Search\Connection_Pool\Selectors\Selector_Interface;
use Open_Search\Connection_Pool\Static_No_Ping_Connection_Pool;
use Open_Search\Connections\Connection_Factory;
use Open_Search\Connections\Connection_Factory_Interface;
use Open_Search\Connections\Connection_Interface;
use Open_Search\Handlers\Sig_V4handler;
use Open_Search\Namespaces\Namespace_Builder_Interface;
use Open_Search\Serializers\Serializer_Interface;
use Open_Search\Serializers\Smart_Serializer;
use Psr\Log\Logger_Interface;
use Psr\Log\Null_Logger;
use ReflectionClass;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Client_Builder::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * @deprecated in 2.4.0 and will be removed in 3.0.0.
 */
class Client_Builder
{
    public const ALLOWED_METHODS_FROM_CONFIG = ['includePortInHostHeader'];
    private ?\Open_Search\Transport $transport = null;
    private ?Endpoint_Factory_Interface $endpoint_factory = null;
    /**
     * @var NamespaceBuilderInterface[]
     */
    private array $registered_namespaces_builders = [];
    private \Open_Search\Connections\Connection_Factory_Interface|\Open_Search\Connections\Connection_Factory|null $connection_factory = null;
    /**
     * @var callable|null
     */
    private $handler;
    private \Psr\Log\Logger_Interface|\Psr\Log\Null_Logger|null $logger = null;
    private \Psr\Log\Logger_Interface|\Psr\Log\Null_Logger|null $tracer = null;
    /**
     * @var string|AbstractConnectionPool
     */
    private $connection_pool = Static_No_Ping_Connection_Pool::class;
    /**
     * @var string|SerializerInterface|null
     */
    private $serializer = Smart_Serializer::class;
    /**
     * @var string|SelectorInterface|null
     */
    private $selector = Round_Robin_Selector::class;
    private array $connection_pool_args = ['randomizeHosts' => true];
    private ?array $hosts = null;
    private ?array $connection_params = null;
    private ?int $retries = null;
    /**
     * @var null|callable
     */
    private $sig_v4credential_provider;
    /**
     * @var null|string
     */
    private $sig_v4region;
    /**
     * @var null|string
     */
    private $sig_v4service;
    private bool $sniff_on_start = false;
    private ?array $ssl_cert = null;
    private ?array $ssl_key = null;
    /**
     * @var null|bool|string
     */
    private $ssl_verification;
    private bool $include_port_in_host_header = false;
    private ?string $basic_authentication = null;
    /**
     * Create an instance of ClientBuilder
     */
    public static function create(): Client_Builder
    {
        return new self();
    }
    /**
     * Can supply first param to Client::__construct() when invoking manually or with dependency injection
     */
    public function get_transport(): Transport
    {
        return $this->transport;
    }
    /**
     * Can supply second param to Client::__construct() when invoking manually or with dependency injection
     *
     * @deprecated in 2.4.0 and will be removed in 3.0.0. Use \OpenSearch\ClientBuilder::getEndpointFactory() instead.
     */
    public function get_endpoint(): callable
    {
        @trigger_error(__METHOD__ . '() is deprecated in 2.4.0 and will be removed in 3.0.0. Use \OpenSearch\ClientBuilder::getEndpointFactory() instead.', E_USER_DEPRECATED);
        return fn($c): \Open_Search\Endpoints\Abstract_Endpoint => $this->endpoint_factory->get_endpoint('OpenSearch\Endpoints\\' . $c);
    }
    /**
     * Can supply third param to Client::__construct() when invoking manually or with dependency injection
     *
     * @return NamespaceBuilderInterface[]
     */
    public function get_registered_namespaces_builders(): array
    {
        return $this->registered_namespaces_builders;
    }
    /**
     * Build a new client from the provided config.  Hash keys
     * should correspond to the method name e.g. ['connectionPool']
     * corresponds to setConnectionPool().
     *
     * Missing keys will use the default for that setting if applicable
     *
     * Unknown keys will throw an exception by default, but this can be silenced
     * by setting `quiet` to true
     *
     * @param  bool $quiet False if unknown settings throw exception, true to silently
     *                     ignore unknown settings
     * @throws Common\Exceptions\RuntimeException
     */
    public static function from_config(array $config, bool $quiet = false): Client
    {
        $builder = new self();
        foreach ($config as $key => $value) {
            $method = in_array($key, self::ALLOWED_METHODS_FROM_CONFIG, true) ? $key : "set{$key}";
            $reflection = new ReflectionClass($builder);
            if ($reflection->has_method($method)) {
                $func = $reflection->get_method($method);
                if ($func->get_number_of_parameters() > 1) {
                    $builder->{$method}(...$value);
                } else {
                    $builder->{$method}($value);
                }
                unset($config[$key]);
            }
        }
        if ($quiet === false && count($config) > 0) {
            $unknown = implode('', array_keys($config));
            throw new RuntimeException("Unknown parameters provided: {$unknown}");
        }
        return $builder->build();
    }
    /**
     * Get the default handler
     *
     * @throws \RuntimeException
     */
    public static function default_handler(array $multi_params = [], array $single_params = []): callable
    {
        $future = null;
        if (extension_loaded('curl')) {
            $config = array_merge(['mh' => curl_multi_init()], $multi_params);
            if (function_exists('curl_reset')) {
                $default = new Curl_Handler($single_params);
                $future = new Curl_Multi_Handler($config);
            } else {
                $default = new Curl_Multi_Handler($config);
            }
        } else {
            throw new \RuntimeException('OpenSearch-PHP requires cURL, or a custom HTTP handler.');
        }
        return $future ? Middleware::wrap_future($default, $future) : $default;
    }
    /**
     * Get the multi handler for async (CurlMultiHandler)
     *
     * @throws \RuntimeException
     */
    public static function multi_handler(array $params = []): Curl_Multi_Handler
    {
        if (function_exists('curl_multi_init')) {
            return new Curl_Multi_Handler(array_merge(['mh' => curl_multi_init()], $params));
        }
        throw new \RuntimeException('CurlMulti handler requires cURL.');
    }
    /**
     * Get the handler instance (CurlHandler)
     *
     * @throws \RuntimeException
     */
    public static function single_handler(): Curl_Handler
    {
        if (function_exists('curl_reset')) {
            return new Curl_Handler();
        }
        throw new \RuntimeException('CurlSingle handler requires cURL.');
    }
    /**
     * Set connection Factory
     */
    public function set_connection_factory(Connection_Factory_Interface $connection_factory): Client_Builder
    {
        $this->connection_factory = $connection_factory;
        return $this;
    }
    /**
     * Set the connection pool (default is StaticNoPingConnectionPool)
     *
     * @param  AbstractConnectionPool|string $connectionPool
     * @throws \InvalidArgumentException
     */
    public function set_connection_pool($connection_pool, array $args = []): Client_Builder
    {
        if (is_string($connection_pool)) {
            $this->connection_pool = $connection_pool;
            $this->connection_pool_args = $args;
        } elseif (is_object($connection_pool)) {
            $this->connection_pool = $connection_pool;
        } else {
            throw new InvalidArgumentException('Serializer must be a class path or instantiated object extending AbstractConnectionPool');
        }
        return $this;
    }
    /**
     * Set the endpoint
     *
     *
     * @deprecated in 2.4.0 and will be removed in 3.0.0. Use \OpenSearch\ClientBuilder::setEndpointFactory() instead.
     */
    public function set_endpoint(callable $endpoint): Client_Builder
    {
        @trigger_error(__METHOD__ . '() is deprecated in 2.4.0 and will be removed in 3.0.0. Use \OpenSearch\ClientBuilder::setEndpointFactory() instead.', E_USER_DEPRECATED);
        $this->endpoint_factory = new Legacy_Endpoint_Factory($endpoint);
        return $this;
    }
    public function set_endpoint_factory(Endpoint_Factory_Interface $endpoint_factory): Client_Builder
    {
        $this->endpoint_factory = $endpoint_factory;
        return $this;
    }
    /**
     * Register namespace
     */
    public function register_namespace(Namespace_Builder_Interface $namespace_builder): Client_Builder
    {
        $this->registered_namespaces_builders[] = $namespace_builder;
        return $this;
    }
    /**
     * Set the transport
     */
    public function set_transport(Transport $transport): Client_Builder
    {
        $this->transport = $transport;
        return $this;
    }
    /**
     * Set the HTTP handler (cURL is default)
     *
     * @param  mixed $handler
     */
    public function set_handler($handler): Client_Builder
    {
        $this->handler = $handler;
        return $this;
    }
    /**
     * Set the PSR-3 Logger
     */
    public function set_logger(Logger_Interface $logger): Client_Builder
    {
        $this->logger = $logger;
        return $this;
    }
    /**
     * Set the PSR-3 tracer
     */
    public function set_tracer(Logger_Interface $tracer): Client_Builder
    {
        $this->tracer = $tracer;
        return $this;
    }
    /**
     * Set the serializer
     *
     * @param \OpenSearch\Serializers\SerializerInterface|string $serializer
     */
    public function set_serializer($serializer): Client_Builder
    {
        $this->parse_string_or_object($serializer, $this->serializer, 'SerializerInterface');
        return $this;
    }
    /**
     * Set the hosts (nodes)
     */
    public function set_hosts(array $hosts): Client_Builder
    {
        $this->hosts = $hosts;
        return $this;
    }
    /**
     * Set Basic access authentication
     *
     * @see https://en.wikipedia.org/wiki/Basic_access_authentication
     *
     * @throws AuthenticationConfigException
     */
    public function set_basic_authentication(string $username, string $password): Client_Builder
    {
        $this->basic_authentication = $username . ':' . $password;
        return $this;
    }
    /**
     * Set connection parameters
     */
    public function set_connection_params(array $params): Client_Builder
    {
        $this->connection_params = $params;
        return $this;
    }
    /**
     * Set number or retries (default is equal to number of nodes)
     */
    public function set_retries(int $retries): Client_Builder
    {
        $this->retries = $retries;
        return $this;
    }
    /**
     * Set the selector algorithm
     *
     * @param \OpenSearch\ConnectionPool\Selectors\SelectorInterface|string $selector
     */
    public function set_selector($selector): Client_Builder
    {
        $this->parse_string_or_object($selector, $this->selector, 'SelectorInterface');
        return $this;
    }
    /**
     * Set the credential provider for SigV4 request signing. The value provider should be a
     * callable object that will return
     *
     * @param callable|bool|array|CredentialsInterface|null $credentialProvider
     */
    public function set_sig_v4credential_provider($credential_provider): Client_Builder
    {
        if ($credential_provider !== null && $credential_provider !== false) {
            $this->sig_v4credential_provider = $this->normalize_credential_provider($credential_provider);
        }
        return $this;
    }
    /**
     * Set the region for SigV4 signing.
     *
     * @param string|null $region
     */
    public function set_sig_v4region($region): Client_Builder
    {
        $this->sig_v4region = $region;
        return $this;
    }
    /**
     * Set the service for SigV4 signing.
     *
     * @param string|null $service
     */
    public function set_sig_v4service($service): Client_Builder
    {
        $this->sig_v4service = $service;
        return $this;
    }
    /**
     * Set sniff on start
     *
     * @param bool $sniffOnStart enable or disable sniff on start
     */
    public function set_sniff_on_start(bool $sniff_on_start): Client_Builder
    {
        $this->sniff_on_start = $sniff_on_start;
        return $this;
    }
    /**
     * Set SSL certificate
     *
     * @param string $cert The name of a file containing a PEM formatted certificate.
     * @param string $password if the certificate requires a password
     */
    public function set_ssl_cert(string $cert, ?string $password = null): Client_Builder
    {
        $this->ssl_cert = [$cert, $password];
        return $this;
    }
    /**
     * Set SSL key
     *
     * @param string $key The name of a file containing a private SSL key
     * @param string $password if the private key requires a password
     */
    public function set_ssl_key(string $key, ?string $password = null): Client_Builder
    {
        $this->ssl_key = [$key, $password];
        return $this;
    }
    /**
     * Set SSL verification
     *
     * @param bool|string $value
     */
    public function set_ssl_verification($value = true): Client_Builder
    {
        $this->ssl_verification = $value;
        return $this;
    }
    /**
     * Include the port in Host header
     *
     * @see https://github.com/elastic/elasticsearch-php/issues/993
     */
    public function include_port_in_host_header(bool $enable): Client_Builder
    {
        $this->include_port_in_host_header = $enable;
        return $this;
    }
    /**
     * Build and returns the Client object
     */
    public function build(): Client
    {
        $this->build_loggers();
        if (is_null($this->handler)) {
            $this->handler = Client_Builder::default_handler();
        }
        if (!is_null($this->sig_v4credential_provider)) {
            if (is_null($this->sig_v4region)) {
                throw new RuntimeException('A region must be supplied for SigV4 request signing.');
            }
            if (is_null($this->sig_v4service)) {
                $this->set_sig_v4service('es');
            }
            $this->handler = new Sig_V4handler($this->sig_v4region, $this->sig_v4service, $this->sig_v4credential_provider, $this->handler);
        }
        $ssl_options = null;
        if (isset($this->ssl_key)) {
            $ssl_options['ssl_key'] = $this->ssl_key;
        }
        if (isset($this->ssl_cert)) {
            $ssl_options['cert'] = $this->ssl_cert;
        }
        // Always emit a verify value so the underlying cURL handler never falls back to
        // whatever the system default happens to be. Peer verification is on by default;
        // callers must explicitly opt out via setSSLVerification(false).
        $ssl_options['verify'] = $this->ssl_verification ?? true;
        if (!is_null($ssl_options)) {
            $ssl_handler = fn(callable $handler, array $ssl_options) => function (array $request) use ($handler, $ssl_options) {
                // Add our custom headers
                foreach ($ssl_options as $key => $value) {
                    $request['client'][$key] = $value;
                }
                // Send the request using the handler and return the response.
                return $handler($request);
            };
            $this->handler = $ssl_handler($this->handler, $ssl_options);
        }
        if (is_null($this->serializer)) {
            $this->serializer = new Smart_Serializer();
        } elseif (is_string($this->serializer)) {
            $this->serializer = new $this->serializer();
        }
        $this->connection_params['client']['port_in_header'] = $this->include_port_in_host_header;
        if (!is_null($this->basic_authentication)) {
            if (isset($this->connection_params['client']['curl']) === false) {
                $this->connection_params['client']['curl'] = [];
            }
            $this->connection_params['client']['curl'] += [CURLOPT_HTTPAUTH => CURLAUTH_BASIC, CURLOPT_USERPWD => $this->basic_authentication];
        }
        if (is_null($this->connection_factory)) {
            // Make sure we are setting Content-Type and Accept (unless the user has explicitly
            // overridden it
            if (!isset($this->connection_params['client']['headers'])) {
                $this->connection_params['client']['headers'] = [];
            }
            if (!isset($this->connection_params['client']['headers']['Content-Type'])) {
                $this->connection_params['client']['headers']['Content-Type'] = ['application/json'];
            }
            if (!isset($this->connection_params['client']['headers']['Accept'])) {
                $this->connection_params['client']['headers']['Accept'] = ['application/json'];
            }
            $this->connection_factory = new Connection_Factory($this->handler, $this->connection_params, $this->serializer, $this->logger, $this->tracer);
        }
        if (is_null($this->hosts)) {
            $this->hosts = $this->get_default_host();
        }
        if (is_null($this->selector)) {
            $this->selector = new Round_Robin_Selector();
        } elseif (is_string($this->selector)) {
            $this->selector = new $this->selector();
        }
        $this->build_transport();
        if (is_null($this->endpoint_factory)) {
            $this->endpoint_factory = new Endpoint_Factory($this->serializer);
        }
        $registered_namespaces = [];
        foreach ($this->registered_namespaces_builders as $builder) {
            /**
             * @var NamespaceBuilderInterface $builder
             */
            $registered_namespaces[$builder->get_name()] = $builder->get_object($this->transport, $this->serializer);
        }
        return $this->instantiate($this->transport, $this->endpoint_factory, $registered_namespaces);
    }
    protected function instantiate(Transport $transport, Endpoint_Factory_Interface $endpoint_factory, array $registered_namespaces): Client
    {
        return new Client($transport, $endpoint_factory, $registered_namespaces);
    }
    private function build_loggers(): void
    {
        if (is_null($this->logger)) {
            $this->logger = new Null_Logger();
        }
        if (is_null($this->tracer)) {
            $this->tracer = new Null_Logger();
        }
    }
    private function build_transport(): void
    {
        $connections = $this->build_connections_from_hosts($this->hosts);
        if (is_string($this->connection_pool)) {
            $this->connection_pool = new $this->connection_pool($connections, $this->selector, $this->connection_factory, $this->connection_pool_args);
        }
        if (is_null($this->retries)) {
            $this->retries = count($connections);
        }
        if (is_null($this->transport)) {
            $this->transport = new Transport($this->retries, $this->connection_pool, $this->logger, $this->sniff_on_start);
        }
    }
    private function parse_string_or_object($arg, &$destination, string $interface): void
    {
        if (is_string($arg)) {
            $destination = new $arg();
        } elseif (is_object($arg)) {
            $destination = $arg;
        } else {
            throw new InvalidArgumentException("Serializer must be a class path or instantiated object implementing {$interface}");
        }
    }
    private function get_default_host(): array
    {
        return ['localhost:9200'];
    }
    /**
     * @return ConnectionInterface[]
     * @throws RuntimeException
     */
    private function build_connections_from_hosts(array $hosts): array
    {
        $connections = [];
        foreach ($hosts as $host) {
            if (is_string($host)) {
                $host = $this->prepend_missing_scheme($host);
                $host = $this->extract_uri_parts($host);
            } elseif (is_array($host)) {
                $host = $this->normalize_extended_host($host);
            } else {
                $this->logger->error('Could not parse host: ' . print_r($host, true));
                throw new RuntimeException('Could not parse host: ' . print_r($host, true));
            }
            $connections[] = $this->connection_factory->create($host);
        }
        return $connections;
    }
    /**
     * @throws RuntimeException
     */
    private function normalize_extended_host(array $host): array
    {
        if (isset($host['host']) === false) {
            $this->logger->error("Required 'host' was not defined in extended format: " . print_r($host, true));
            throw new RuntimeException("Required 'host' was not defined in extended format: " . print_r($host, true));
        }
        if (isset($host['scheme']) === false) {
            $host['scheme'] = 'http';
        }
        if (isset($host['port']) === false) {
            $host['port'] = 9200;
        }
        return $host;
    }
    /**
     * @throws InvalidArgumentException
     */
    private function extract_uri_parts(string $host): array
    {
        $parts = parse_url($host);
        if ($parts === false) {
            throw new InvalidArgumentException(sprintf('Could not parse URI: "%s"', $host));
        }
        if (isset($parts['port']) !== true) {
            $parts['port'] = 9200;
        }
        return $parts;
    }
    private function prepend_missing_scheme(string $host): string
    {
        if (!preg_match("/^https?:\\/\\//", $host)) {
            return 'http://' . $host;
        }
        return $host;
    }
    private function normalize_credential_provider($provider): ?callable
    {
        if ($provider === null || $provider === false) {
            return null;
        }
        if (is_callable($provider)) {
            return $provider;
        }
        Sig_V4handler::assert_dependencies_installed();
        if ($provider === true) {
            return Credential_Provider::default_provider();
        }
        if ($provider instanceof Credentials_Interface) {
            return Credential_Provider::from_credentials($provider);
        }
        if (is_array($provider) && isset($provider['key']) && isset($provider['secret'])) {
            return Credential_Provider::from_credentials(new Credentials($provider['key'], $provider['secret'], $provider['token'] ?? null, $provider['expires'] ?? null));
        }
        throw new InvalidArgumentException('Credentials must be an instance of Aws\Credentials\CredentialsInterface, an' . ' associative array that contains "key", "secret", and an optional "token" key-value pairs, a credentials' . ' provider function, or true.');
    }
}