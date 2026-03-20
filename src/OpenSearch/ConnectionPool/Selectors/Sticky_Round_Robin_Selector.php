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
namespace Open_Search\Connection_Pool\Selectors;

use Open_Search\Connections\Connection_Interface;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Sticky_Round_Robin_Selector::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * @deprecated in 2.4.0 and will be removed in 3.0.0.
 */
class Sticky_Round_Robin_Selector implements Selector_Interface
{
    private int $current = 0;
    private int $current_counter = 0;
    /**
     * Use current connection unless it is dead, otherwise round-robin
     *
     * @param ConnectionInterface[] $connections Array of connections to choose from
     */
    public function select(array $connections): Connection_Interface
    {
        /**
         * @var ConnectionInterface[] $connections
        */
        if ($connections[$this->current]->is_alive()) {
            return $connections[$this->current];
        }
        $this->current_counter += 1;
        $this->current = $this->current_counter % count($connections);
        return $connections[$this->current];
    }
}