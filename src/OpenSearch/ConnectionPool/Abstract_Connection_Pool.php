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

use Open_Search\Common\Exceptions\InvalidArgumentException;
use Open_Search\Connection_Pool\Selectors\Selector_Interface;
use Open_Search\Connections\Connection_Factory_Interface;
use Open_Search\Connections\Connection_Interface;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Abstract_Connection_Pool::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * @deprecated in 2.4.0 and will be removed in 3.0.0.
 */
abstract class Abstract_Connection_Pool implements Connection_Pool_Interface
{
    /**
     * Array of connections
     *
     * @var ConnectionInterface[]
     */
    protected array $connections;
    /**
     * Array of initial seed connections
     *
     * @var ConnectionInterface[]
     */
    protected array $seed_connections;
    /**
     * @var array<string, mixed>
     */
    protected array $connection_pool_params;
    /**
     * Constructor
     *
     * @param ConnectionInterface[]      $connections          The Connections to choose from
     * @param SelectorInterface          $selector             A Selector instance to perform the selection logic for the available connections
     * @param ConnectionFactoryInterface $connectionFactory ConnectionFactory instance
     * @param array<string, mixed>       $connectionPoolParams
     */
    public function __construct(array $connections, protected \Open_Search\Connection_Pool\Selectors\Selector_Interface $selector, protected \Open_Search\Connections\Connection_Factory_Interface $connection_factory, array $connection_pool_params)
    {
        $param_list = ['connections', 'selector', 'connectionPoolParams'];
        foreach ($param_list as $param) {
            if (isset(${$param}) === false) {
                throw new InvalidArgumentException('`' . $param . '` parameter must not be null');
            }
        }
        if (isset($connection_pool_params['randomizeHosts']) === true && $connection_pool_params['randomizeHosts'] === true) {
            shuffle($connections);
        }
        $this->connections = $connections;
        $this->seed_connections = $connections;
        $this->connection_pool_params = $connection_pool_params;
    }
    abstract public function next_connection(bool $force = false): Connection_Interface;
    abstract public function schedule_check(): void;
}