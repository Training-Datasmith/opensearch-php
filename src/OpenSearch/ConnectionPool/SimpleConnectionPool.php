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

use Open_Search\Connections\Connection_Interface;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Simple_Connection_Pool::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * @deprecated in 2.4.0 and will be removed in 3.0.0.
 *
 * @phpstan-ignore class.extendsDeprecatedClass
 */
class Simple_Connection_Pool extends Abstract_Connection_Pool implements Connection_Pool_Interface
{
    public function next_connection(bool $force = false): Connection_Interface
    {
        return $this->selector->select($this->connections);
    }
    public function schedule_check(): void
    {
    }
}