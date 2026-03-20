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
namespace Open_Search\Endpoints\Async_Search;

use Open_Search\Endpoints\Abstract_Endpoint;
use Open_Search\Exception\RuntimeException;
class Get extends Abstract_Endpoint
{
    public function get_uri(): string
    {
        $id = $this->id ?? null;
        if (isset($id)) {
            return "/_async_search/{$id}";
        }
        throw new RuntimeException('Missing parameter for the endpoint async_search.get');
    }
    public function get_param_whitelist(): array
    {
        return ['wait_for_completion_timeout', 'keep_alive', 'typed_keys'];
    }
    public function get_method(): string
    {
        return 'GET';
    }
}