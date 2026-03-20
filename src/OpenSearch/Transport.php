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

use Guzzle_Http\Ring\Future\Future_Array_Interface;
use Open_Search\Common\Exceptions;
use Open_Search\Connection_Pool\Abstract_Connection_Pool;
use Open_Search\Connections\Connection_Interface;
use Psr\Log\Logger_Interface;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Transport::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * @deprecated in 2.4.0 and will be removed in 3.0.0.
 */
class Transport
{
    /**
     * @var AbstractConnectionPool
     */
    public $connection_pool;
    /**
     * @var int
     */
    public $retry_attempts = 0;
    /**
     * @var ConnectionInterface
     */
    public $last_connection;
    /**
     * @var int
     */
    public $retries;
    /**
     * Transport class is responsible for dispatching requests to the
     * underlying cluster connections
     *
     * @param \Psr\Log\LoggerInterface              $log            Monolog logger object
     */
    public function __construct(int $retries, Abstract_Connection_Pool $connection_pool, private readonly Logger_Interface $log, bool $sniff_on_start = false)
    {
        $this->connection_pool = $connection_pool;
        $this->retries = $retries;
        if ($sniff_on_start === true) {
            $this->log->notice('Sniff on Start.');
            $this->connection_pool->schedule_check();
        }
    }
    /**
     * Returns a single connection from the connection pool
     * Potentially performs a sniffing step before returning
     */
    public function get_connection(): Connection_Interface
    {
        return $this->connection_pool->next_connection();
    }
    /**
     * Perform a request to the Cluster
     *
     * @param string     $method  HTTP method to use
     * @param string     $uri     HTTP URI to send request to
     * @param array<string, mixed> $params  Optional query parameters
     * @param mixed|null $body    Optional query body
     *
     * @throws Common\Exceptions\NoNodesAvailableException|\Exception
     */
    public function perform_request(string $method, string $uri, array $params = [], $body = null, array $options = []): Future_Array_Interface
    {
        try {
            $connection = $this->get_connection();
        } catch (Exceptions\No_Nodes_Available_Exception $exception) {
            $this->log->critical('No alive nodes found in cluster');
            throw $exception;
        }
        $response = [];
        $this->last_connection = $connection;
        $future = $connection->perform_request($method, $uri, $params, $body, $options, $this);
        $future->promise()->then(
            //onSuccess
            function ($response): void {
                $this->retry_attempts = 0;
                // Note, this could be a 4xx or 5xx error
            },
            //onFailure
            function ($response): void {
                $code = $response->get_code();
                // Ignore 400 level errors, as that means the server responded just fine
                if ($code < 400 || $code >= 500) {
                    // Otherwise schedule a check
                    $this->connection_pool->schedule_check();
                }
            }
        );
        return $future;
    }
    /**
     * @param FutureArrayInterface $result  Response of a request (promise)
     * @param array                $options Options for transport
     *
     * @return callable|array
     */
    public function result_or_future(Future_Array_Interface $result, array $options = [])
    {
        $async = $options['client']['future'] ?? null;
        if (is_null($async) || $async === false) {
            do {
                $result = $result->wait();
            } while ($result instanceof Future_Array_Interface);
        }
        return $result;
    }
    public function should_retry(array $request): bool
    {
        if ($this->retry_attempts < $this->retries) {
            $this->retry_attempts += 1;
            return true;
        }
        return false;
    }
    /**
     * Returns the last used connection so that it may be inspected.  Mainly
     * for debugging/testing purposes.
     */
    public function get_last_connection(): Connection_Interface
    {
        return $this->last_connection;
    }
}