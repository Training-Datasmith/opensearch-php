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
namespace Open_Search\Connections;

use Exception;
use Guzzle_Http\Ring\Core;
use Guzzle_Http\Ring\Exception\Connect_Exception;
use Guzzle_Http\Ring\Exception\Ring_Exception;
use Open_Search\Client;
use Open_Search\Common\Exceptions\Bad_Request400exception;
use Open_Search\Common\Exceptions\Conflict409Exception;
use Open_Search\Common\Exceptions\Curl\Could_Not_Connect_To_Host;
use Open_Search\Common\Exceptions\Curl\Could_Not_Resolve_Host_Exception;
use Open_Search\Common\Exceptions\Curl\Operation_Timeout_Exception;
use Open_Search\Common\Exceptions\Forbidden403Exception;
use Open_Search\Common\Exceptions\Max_Retries_Exception;
use Open_Search\Common\Exceptions\Missing404Exception;
use Open_Search\Common\Exceptions\No_Documents_To_Get_Exception;
use Open_Search\Common\Exceptions\No_Shard_Available_Exception;
use Open_Search\Common\Exceptions\Open_Search_Exception;
use Open_Search\Common\Exceptions\Request_Timeout408exception;
use Open_Search\Common\Exceptions\Routing_Missing_Exception;
use Open_Search\Common\Exceptions\Script_Lang_Not_Supported_Exception;
use Open_Search\Common\Exceptions\Server_Error_Response_Exception;
use Open_Search\Common\Exceptions\Transport_Exception;
use Open_Search\Common\Exceptions\Unauthorized401Exception;
use Open_Search\Transport;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Connection::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * @deprecated in 2.4.0 and will be removed in 3.0.0.
 */
class Connection implements Connection_Interface
{
    /**
     * @var callable
     */
    protected $handler;
    protected string $transport_schema = 'http';
    // TODO depreciate this default
    /**
     * @var string
     */
    protected $host;
    /**
     * @var string|null
     */
    protected $path;
    /**
     * @var int
     */
    protected $port;
    protected array $connection_params;
    /**
     * @var array<string, list<string>>
     */
    protected $headers = [];
    /**
     * @var bool
     */
    protected $is_alive = false;
    private int $ping_timeout = 1;
    //TODO expose this
    /**
     * @var int
     */
    private $last_ping = 0;
    private int $failed_pings = 0;
    /**
     * @var mixed[]
     */
    private array $last_request = [];
    private ?string $os_version = null;
    /**
     * @param array{host: string, port?: int, scheme?: string, user?: string, pass?: string, path?: string} $hostDetails
     * @param array{client?: array{headers?: array<string, list<string>>, curl?: array<int, mixed>}} $connectionParams
     */
    public function __construct(callable $handler, array $host_details, array $connection_params, protected \Open_Search\Serializers\Serializer_Interface $serializer, protected \Psr\Log\Logger_Interface $log, protected \Psr\Log\Logger_Interface $trace)
    {
        if (isset($host_details['port']) !== true) {
            $host_details['port'] = 9200;
        }
        if (isset($host_details['scheme'])) {
            $this->transport_schema = $host_details['scheme'];
        }
        // Only Set the Basic if API Key is not set and setBasicAuthentication was not called prior
        if (isset($connection_params['client']['headers']['Authorization']) === false && isset($connection_params['client']['curl'][CURLOPT_HTTPAUTH]) === false && isset($host_details['user']) && isset($host_details['pass'])) {
            $connection_params['client']['curl'][CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $connection_params['client']['curl'][CURLOPT_USERPWD] = $host_details['user'] . ':' . $host_details['pass'];
        }
        $connection_params['client']['curl'][CURLOPT_PORT] = $host_details['port'];
        if (isset($connection_params['client']['headers'])) {
            $this->headers = $connection_params['client']['headers'];
            unset($connection_params['client']['headers']);
        }
        // Add the User-Agent using the format: <client-repo-name>/<client-version> (metadata-values)
        $this->headers['User-Agent'] = [sprintf('opensearch-php/%s (%s %s; PHP %s)', Client::VERSION, PHP_OS, $this->get_os_version(), PHP_VERSION)];
        $host = $host_details['host'];
        $path = null;
        if (isset($host_details['path']) === true) {
            $path = $host_details['path'];
        }
        $port = $host_details['port'];
        $this->host = $host;
        $this->path = $path;
        $this->port = $port;
        $this->connection_params = $connection_params;
        $this->handler = $this->wrap_handler($handler);
    }
    /**
     * @param  null|array<string, mixed> $params
     * @param  mixed     $body
     * @return mixed
     */
    public function perform_request(string $method, string $uri, ?array $params = [], $body = null, array $options = [], ?Transport $transport = null)
    {
        if ($body !== null) {
            $body = $this->serializer->serialize($body);
        }
        $headers = $this->headers;
        if (isset($options['client']['headers']) && is_array($options['client']['headers'])) {
            $headers = array_merge($this->headers, $options['client']['headers']);
        }
        $host = $this->host;
        if (isset($this->connection_params['client']['port_in_header']) && $this->connection_params['client']['port_in_header']) {
            $host .= ':' . $this->port;
        }
        $request = ['http_method' => $method, 'scheme' => $this->transport_schema, 'uri' => $this->get_uri($uri, $params), 'body' => $body, 'headers' => array_merge(['Host' => [$host]], $headers)];
        $request = array_replace_recursive($request, $this->connection_params, $options);
        // RingPHP does not like if client is empty
        if (empty($request['client'])) {
            unset($request['client']);
        }
        $handler = $this->handler;
        return $handler($request, $this, $transport, $options);
    }
    public function get_transport_schema(): string
    {
        return $this->transport_schema;
    }
    public function get_last_request_info(): array
    {
        $info = $this->last_request;
        // Redact credentials stored by the constructor so that callers logging or
        // displaying debug info do not inadvertently expose passwords.
        if (isset($info['request']['client']['curl'][CURLOPT_USERPWD])) {
            $info['request']['client']['curl'][CURLOPT_USERPWD] = '[REDACTED]';
        }
        return $info;
    }
    private function wrap_handler(callable $handler): callable
    {
        return function (array $request, Connection $connection, ?Transport $transport, $options) use ($handler) {
            $this->last_request = [];
            $this->last_request['request'] = $request;
            // Send the request using the wrapped handler.
            $response = Core::proxy($handler($request), function (array $response) use ($connection, $transport, $request, $options) {
                $this->last_request['response'] = $response;
                if (isset($response['error']) === true) {
                    if ($response['error'] instanceof Connect_Exception || $response['error'] instanceof Ring_Exception) {
                        $this->log->warning('Curl exception encountered.');
                        $exception = $this->get_curl_retry_exception($request, $response);
                        $this->log_request_fail($request, $response, $exception);
                        $node = $connection->get_host();
                        $this->log->warning("Marking node {$node} dead.");
                        $connection->mark_dead();
                        // If the transport has not been set, we are inside a Ping or Sniff,
                        // so we don't want to retrigger retries anyway.
                        //
                        // TODO this could be handled better, but we are limited because connectionpools do not
                        // have access to Transport.  Architecturally, all of this needs to be refactored
                        if (isset($transport) === true) {
                            $transport->connection_pool->schedule_check();
                            $never_retry = $request['client']['never_retry'] ?? false;
                            $should_retry = $transport->should_retry($request);
                            $should_retry_text = $should_retry ? 'true' : 'false';
                            $this->log->warning("Retries left? {$should_retry_text}");
                            if ($should_retry && !$never_retry) {
                                return $transport->perform_request($request['http_method'], $request['uri'], [], $request['body'], $options);
                            }
                        }
                        $this->log->warning("Out of retries, throwing exception from {$node}");
                        // Only throw if we run out of retries
                        throw $exception;
                    }
                    // Something went seriously wrong, bail
                    $exception = new Transport_Exception($response['error']->get_message());
                    $this->log_request_fail($request, $response, $exception);
                    throw $exception;
                }
                $connection->mark_alive();
                if (isset($response['headers']['Warning'])) {
                    $this->log_warning($request, $response);
                }
                if (isset($response['body']) === true) {
                    $response['body'] = stream_get_contents($response['body']);
                    $this->last_request['response']['body'] = $response['body'];
                }
                if ($response['status'] >= 400 && $response['status'] < 500) {
                    $ignore = $request['client']['ignore'] ?? [];
                    // Skip 404 if succeeded true in the body (e.g. clear_scroll)
                    $body = $response['body'] ?? '';
                    if (str_contains($body, '"succeeded":true')) {
                        $ignore[] = 404;
                    }
                    $this->process4xx_error($request, $response, $ignore);
                } elseif ($response['status'] >= 500) {
                    $ignore = $request['client']['ignore'] ?? [];
                    $this->process5xx_error($request, $response, $ignore);
                }
                // No error, deserialize
                $response['body'] = $this->serializer->deserialize($response['body'], $response['transfer_stats']);
                $this->log_request_success($request, $response);
                return isset($request['client']['verbose']) && $request['client']['verbose'] === true ? $response : $response['body'];
            });
            return $response;
        };
    }
    /**
     * @param array<string, string|int|bool>|null $params
     */
    private function get_uri(string $uri, ?array $params): string
    {
        if (isset($params) === true && !empty($params)) {
            $params = array_map(function ($value): int|string {
                if ($value === true) {
                    return 'true';
                }
                if ($value === false) {
                    return 'false';
                }
                return $value;
            }, $params);
            $uri .= '?' . http_build_query($params);
        }
        if ($this->path !== null) {
            return $this->path . $uri;
        }
        return $uri;
    }
    /**
     * @return array<string, list<string>>
     */
    public function get_headers(): array
    {
        return $this->headers;
    }
    public function log_warning(array $request, array $response): void
    {
        $this->log->warning('Deprecation', $response['headers']['Warning']);
    }
    /**
     * Log a successful request
     */
    public function log_request_success(array $request, array $response): void
    {
        $port = $request['client']['curl'][CURLOPT_PORT] ?? $response['transfer_stats']['primary_port'] ?? '';
        $uri = $this->add_port_in_url($response['effective_url'], (int) $port);
        $this->log->debug('Request Body', [$request['body']]);
        $this->log->info('Request Success:', ['method' => $request['http_method'], 'uri' => $uri, 'port' => $port, 'headers' => $request['headers'], 'HTTP code' => $response['status'], 'duration' => $response['transfer_stats']['total_time']]);
        $this->log->debug('Response', [$response['body']]);
        // Build the curl command for Trace.
        $curl_command = $this->build_curl_command($request['http_method'], $uri, $request['body']);
        $this->trace->info($curl_command);
        $this->trace->debug('Response:', ['response' => $response['body'], 'method' => $request['http_method'], 'uri' => $uri, 'port' => $port, 'HTTP code' => $response['status'], 'duration' => $response['transfer_stats']['total_time']]);
    }
    /**
     * Log a failed request
     *
     *
     */
    public function log_request_fail(array $request, array $response, \Throwable $exception): void
    {
        $port = $request['client']['curl'][CURLOPT_PORT] ?? $response['transfer_stats']['primary_port'] ?? '';
        $uri = $this->add_port_in_url($response['effective_url'], (int) $port);
        $this->log->debug('Request Body', [$request['body']]);
        $this->log->warning('Request Failure:', ['method' => $request['http_method'], 'uri' => $uri, 'port' => $port, 'headers' => $request['headers'], 'HTTP code' => $response['status'], 'duration' => $response['transfer_stats']['total_time'], 'error' => $exception->get_message()]);
        $this->log->warning('Response', [$response['body']]);
        // Build the curl command for Trace.
        $curl_command = $this->build_curl_command($request['http_method'], $uri, $request['body']);
        $this->trace->info($curl_command);
        $this->trace->debug('Response:', ['response' => $response, 'method' => $request['http_method'], 'uri' => $uri, 'port' => $port, 'HTTP code' => $response['status'], 'duration' => $response['transfer_stats']['total_time']]);
    }
    public function ping(): bool
    {
        $options = ['client' => ['timeout' => $this->ping_timeout, 'never_retry' => true, 'verbose' => true]];
        try {
            $response = $this->perform_request('HEAD', '/', null, null, $options);
            $response = $response->wait();
        } catch (Transport_Exception) {
            $this->mark_dead();
            return false;
        }
        if ($response['status'] === 200) {
            $this->mark_alive();
            return true;
        }
        $this->mark_dead();
        return false;
    }
    /**
     * @return array|\GuzzleHttp\Ring\Future\FutureArray
     */
    public function sniff()
    {
        $options = ['client' => ['timeout' => $this->ping_timeout, 'never_retry' => true]];
        return $this->perform_request('GET', '/_nodes/', null, null, $options);
    }
    public function is_alive(): bool
    {
        return $this->is_alive;
    }
    public function mark_alive(): void
    {
        $this->failed_pings = 0;
        $this->is_alive = true;
        $this->last_ping = time();
    }
    public function mark_dead(): void
    {
        $this->is_alive = false;
        $this->failed_pings += 1;
        $this->last_ping = time();
    }
    public function get_last_ping(): int
    {
        return $this->last_ping;
    }
    public function get_ping_failures(): int
    {
        return $this->failed_pings;
    }
    public function get_host(): string
    {
        return $this->host;
    }
    public function get_user_pass(): ?string
    {
        return $this->connection_params['client']['curl'][CURLOPT_USERPWD] ?? null;
    }
    public function get_path(): ?string
    {
        return $this->path;
    }
    /**
     * @return int
     */
    public function get_port()
    {
        return $this->port;
    }
    protected function get_curl_retry_exception(array $request, array $response): Open_Search_Exception
    {
        $exception = null;
        $message = $response['error']->get_message();
        $exception = new Max_Retries_Exception($message);
        $exception = match ($response['curl']['errno']) {
            6 => new Could_Not_Resolve_Host_Exception($message, 0, $exception),
            7 => new Could_Not_Connect_To_Host($message, 0, $exception),
            28 => new Operation_Timeout_Exception($message, 0, $exception),
            default => $exception,
        };
        return $exception;
    }
    /**
     * Get the OS version using php_uname if available
     * otherwise it returns an empty string
     *
     * @see https://github.com/elastic/elasticsearch-php/issues/922
     */
    private function get_os_version(): string
    {
        if ($this->os_version === null) {
            $this->os_version = str_contains(strtolower(ini_get('disable_functions')), 'php_uname') ? '' : php_uname('r');
        }
        return $this->os_version;
    }
    /**
     * Add the port value in the URL if not present
     */
    private function add_port_in_url(string $uri, int $port): string
    {
        if (str_contains(substr($uri, 7), ':')) {
            return $uri;
        }
        return preg_replace('#([^/])/([^/])#', sprintf('$1:%s/$2', $port), $uri, 1);
    }
    /**
     * Construct a string cURL command
     */
    private function build_curl_command(string $method, string $url, ?string $body): string
    {
        if (!str_contains($url, '?')) {
            $url .= '?pretty=true';
        } else {
            str_replace('?', '?pretty=true', $url);
        }
        $curl_command = 'curl -X' . strtoupper($method);
        $curl_command .= " '" . $url . "'";
        if (isset($body) === true && $body !== '') {
            $curl_command .= " -d '" . $body . "'";
        }
        return $curl_command;
    }
    /**
     * @throws OpenSearchException
     */
    private function process4xx_error(array $request, array $response, array $ignore): void
    {
        $status_code = $response['status'];
        /**
         * @var \Exception $exception
         */
        $exception = $this->try_deserialize400error($response);
        if (array_search($response['status'], $ignore) !== false) {
            return;
        }
        $response_body = $this->convert_body_to_string($response['body'], $status_code, $exception);
        if ($status_code === 401) {
            $exception = new Unauthorized401Exception($response_body);
        } elseif ($status_code === 403) {
            $exception = new Forbidden403Exception($response_body);
        } elseif ($status_code === 404) {
            $exception = new Missing404Exception($response_body);
        } elseif ($status_code === 409) {
            $exception = new Conflict409Exception($response_body, $status_code);
        } elseif ($status_code === 400 && str_contains($response_body, 'script_lang not supported')) {
            $exception = new Script_Lang_Not_Supported_Exception($response_body);
        } elseif ($status_code === 408) {
            $exception = new Request_Timeout408exception($response_body);
        } else {
            $exception = new Bad_Request400exception($response_body);
        }
        $this->log_request_fail($request, $response, $exception);
        throw $exception;
    }
    /**
     * @throws OpenSearchException
     */
    private function process5xx_error(array $request, array $response, array $ignore): void
    {
        $status_code = (int) $response['status'];
        $response_body = $response['body'];
        /**
         * @var \Exception $exception
         */
        $exception = $this->try_deserialize500error($response);
        $exception_text = "[{$status_code} Server Exception] " . $exception->get_message();
        $this->log->error($exception_text);
        $this->log->error($exception->get_trace_as_string());
        if (array_search($status_code, $ignore) !== false) {
            return;
        }
        if ($status_code === 500 && str_contains((string) $response_body, 'RoutingMissingException')) {
            $exception = new Routing_Missing_Exception($exception->get_message(), [], 0, $exception);
        } elseif ($status_code === 500 && preg_match('/ActionRequestValidationException.+ no documents to get/', (string) $response_body) === 1) {
            $exception = new No_Documents_To_Get_Exception($exception->get_message(), [], 0, $exception);
        } elseif ($status_code === 500 && str_contains((string) $response_body, 'NoShardAvailableActionException')) {
            $exception = new No_Shard_Available_Exception($exception->get_message(), [], 0, $exception);
        } else {
            $exception = new Server_Error_Response_Exception($this->convert_body_to_string($response_body, $status_code, $exception));
        }
        $this->log_request_fail($request, $response, $exception);
        throw $exception;
    }
    private function convert_body_to_string($body, int $status_code, Exception $exception): string
    {
        if (empty($body)) {
            return sprintf('Unknown %d error from OpenSearch %s', $status_code, $exception->get_message());
        }
        // if body is not string, we convert it so it can be used as Exception message
        if (!is_string($body)) {
            return json_encode($body);
        }
        return $body;
    }
    private function try_deserialize400error(array $response): Open_Search_Exception
    {
        return $this->try_deserialize_error($response, Bad_Request400exception::class);
    }
    private function try_deserialize500error(array $response): Open_Search_Exception
    {
        return $this->try_deserialize_error($response, Server_Error_Response_Exception::class);
    }
    private function try_deserialize_error(array $response, string $error_class): Open_Search_Exception
    {
        $error = $this->serializer->deserialize($response['body'], $response['transfer_stats']);
        if (is_array($error) === true) {
            if (isset($error['error']) === false) {
                // <2.0 "i just blew up" nonstructured exception
                // $error is an array but we don't know the format, reuse the response body instead
                // added json_encode to convert into a string
                return new $error_class(json_encode($response['body']), (int) $response['status']);
            }
            // 2.0 structured exceptions
            if (is_array($error['error']) && array_key_exists('reason', $error['error']) === true) {
                // Try to use root cause first (only grabs the first root cause)
                $info = $error['error']['root_cause'][0] ?? $error['error'];
                $cause = $info['reason'];
                $type = $info['type'];
                // added json_encode to convert into a string
                $original = new $error_class(json_encode($response['body']), $response['status']);
                return new $error_class("{$type}: {$cause}", (int) $response['status'], $original);
            }
            // <2.0 semi-structured exceptions
            // added json_encode to convert into a string
            $original = new $error_class(json_encode($response['body']), $response['status']);
            $error_encoded = $error['error'];
            if (is_array($error_encoded)) {
                $error_encoded = json_encode($error_encoded);
            }
            return new $error_class($error_encoded, (int) $response['status'], $original);
        }
        // if responseBody is not string, we convert it so it can be used as Exception message
        $response_body = $response['body'];
        if (!is_string($response_body)) {
            $response_body = json_encode($response_body);
        }
        // <2.0 "i just blew up" nonstructured exception
        return new $error_class($response_body);
    }
}