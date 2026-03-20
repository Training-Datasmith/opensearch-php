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
namespace Open_Search\Endpoints\Searchable_Snapshots;

use Open_Search\Endpoints\Abstract_Endpoint;
use Open_Search\Exception\RuntimeException;
class Mount extends Abstract_Endpoint
{
    protected $repository;
    protected $snapshot;
    public function get_uri(): string
    {
        $repository = $this->repository ?? null;
        $snapshot = $this->snapshot ?? null;
        if (isset($repository) && isset($snapshot)) {
            return "/_snapshot/{$repository}/{$snapshot}/_mount";
        }
        throw new RuntimeException('Missing parameter for the endpoint searchable_snapshots.mount');
    }
    public function get_param_whitelist(): array
    {
        return ['master_timeout', 'wait_for_completion', 'cluster_manager_timeout'];
    }
    public function get_method(): string
    {
        return 'POST';
    }
    public function set_body($body): static
    {
        if (isset($body) !== true) {
            return $this;
        }
        $this->body = $body;
        return $this;
    }
    public function set_repository($repository): static
    {
        if (isset($repository) !== true) {
            return $this;
        }
        $this->repository = $repository;
        return $this;
    }
    public function set_snapshot($snapshot): static
    {
        if (isset($snapshot) !== true) {
            return $this;
        }
        $this->snapshot = $snapshot;
        return $this;
    }
    protected function get_param_deprecation(): array
    {
        return ['master_timeout' => 'cluster_manager_timeout'];
    }
}