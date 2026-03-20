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
namespace Open_Search\Connection_Pool;

use Open_Search\Common\Exceptions\Curl\Operation_Timeout_Exception;
use Open_Search\Common\Exceptions\No_Nodes_Available_Exception;
use Open_Search\Connection_Pool\Selectors\Selector_Interface;
use Open_Search\Connections\Connection;
use Open_Search\Connections\Connection_Factory_Interface;
use Open_Search\Connections\Connection_Interface;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Sniffing_Connection_Pool::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * @deprecated in 2.4.0 and will be removed in 3.0.0.
 *
 * @phpstan-ignore class.extendsDeprecatedClass
 */
class Sniffing_Connection_Pool extends Abstract_Connection_Pool
{
    private int $sniffing_interval;
    private float|int $next_sniff;
    /**
     * @param ConnectionInterface[] $connections
     * @param array<string, mixed>  $connectionPoolParams
     */
    public function __construct(array $connections, Selector_Interface $selector, Connection_Factory_Interface $factory, array $connection_pool_params)
    {
        parent::__construct($connections, $selector, $factory, $connection_pool_params);
        $this->set_connection_pool_params($connection_pool_params);
        $this->next_sniff = time() + $this->sniffing_interval;
    }
    public function next_connection(bool $force = false): Connection_Interface
    {
        $this->sniff($force);
        $size = count($this->connections);
        while ($size--) {
            /**
             * @var Connection $connection
             */
            $connection = $this->selector->select($this->connections);
            if ($connection->is_alive() === true || $connection->ping() === true) {
                return $connection;
            }
        }
        if ($force === true) {
            throw new No_Nodes_Available_Exception('No alive nodes found in your cluster');
        }
        return $this->next_connection(true);
    }
    public function schedule_check(): void
    {
        $this->next_sniff = -1;
    }
    private function sniff(bool $force = false): void
    {
        if ($force === false && $this->next_sniff > time()) {
            return;
        }
        $total = count($this->connections);
        while ($total--) {
            /**
             * @var Connection $connection
             */
            $connection = $this->selector->select($this->connections);
            if ($connection->is_alive() xor $force) {
                continue;
            }
            if ($this->sniff_connection($connection) === true) {
                return;
            }
        }
        if ($force === true) {
            return;
        }
        foreach ($this->seed_connections as $connection) {
            /**
             * @var Connection $connection
             */
            if ($this->sniff_connection($connection) === true) {
                return;
            }
        }
    }
    private function sniff_connection(Connection $connection): bool
    {
        try {
            $response = $connection->sniff();
        } catch (Operation_Timeout_Exception) {
            return false;
        }
        $nodes = $this->parse_cluster_state($response);
        if (count($nodes) === 0) {
            return false;
        }
        $this->connections = [];
        foreach ($nodes as $node) {
            $node_details = ['host' => $node['host'], 'port' => $node['port']];
            $this->connections[] = $this->connection_factory->create($node_details);
        }
        $this->next_sniff = time() + $this->sniffing_interval;
        return true;
    }
    /**
     * @return list<array{host: string, port: int}>
     */
    private function parse_cluster_state(array $node_info): array
    {
        $pattern = '/([^:]*):(\d+)/';
        $hosts = [];
        foreach ($node_info['nodes'] as $node) {
            if (!(isset($node['http']) === true)) {
                continue;
            }
            if (!(isset($node['http']['publish_address']) === true)) {
                continue;
            }
            if (preg_match($pattern, $node['http']['publish_address'], $match) !== 1) {
                continue;
            }
            $hosts[] = ['host' => $match[1], 'port' => (int) $match[2]];
        }
        return $hosts;
    }
    /**
     * @param array<string, mixed> $connectionPoolParams
     */
    private function set_connection_pool_params(array $connection_pool_params): void
    {
        $this->sniffing_interval = (int) ($connection_pool_params['sniffingInterval'] ?? 300);
    }
}