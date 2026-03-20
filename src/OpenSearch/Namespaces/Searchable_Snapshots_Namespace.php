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
namespace Open_Search\Namespaces;

/**
 * Class SearchableSnapshotsNamespace
 *
 * @deprecated in 2.4.0 and will be removed in 3.0.0.
 */
class Searchable_Snapshots_Namespace extends Abstract_Namespace
{
    /**
     * $params['index']              = (list) A comma-separated list of index names
     * $params['ignore_unavailable'] = (boolean) Whether specified concrete indices should be ignored when unavailable (missing or closed)
     * $params['allow_no_indices']   = (boolean) Whether to ignore if a wildcard indices expression resolves into no concrete indices. (This includes `_all` string or when no indices have been specified)
     * $params['expand_wildcards']   = (enum) Whether to expand wildcard expression to concrete indices that are open, closed or both. (Options = open,closed,none,all) (Default = open)
     *
     * @param array $params Associative array of parameters
     * @return array
     *
     * @note This API is EXPERIMENTAL and may be changed or removed completely in a future release
     *
     */
    public function clear_cache(array $params = [])
    {
        $index = $this->extract_argument($params, 'index');
        $endpoint_builder = $this->endpoints;
        $endpoint = $endpoint_builder('SearchableSnapshots\ClearCache');
        $endpoint->set_params($params);
        $endpoint->set_index($index);
        return $this->perform_request($endpoint);
    }
    /**
     * $params['repository']          = (string) The name of the repository containing the snapshot of the index to mount
     * $params['snapshot']            = (string) The name of the snapshot of the index to mount
     * $params['cluster_manager_timeout']      = (time) Explicit operation timeout for connection to cluster_manager node
     * $params['wait_for_completion'] = (boolean) Should this request wait until the operation has completed before returning (Default = false)
     * $params['body']                = (array) The restore configuration for mounting the snapshot as searchable (Required)
     *
     * @param array $params Associative array of parameters
     * @return array
     *
     * @note This API is EXPERIMENTAL and may be changed or removed completely in a future release
     *
     */
    public function mount(array $params = [])
    {
        $repository = $this->extract_argument($params, 'repository');
        $snapshot = $this->extract_argument($params, 'snapshot');
        $body = $this->extract_argument($params, 'body');
        $endpoint_builder = $this->endpoints;
        $endpoint = $endpoint_builder('SearchableSnapshots\Mount');
        $endpoint->set_params($params);
        $endpoint->set_repository($repository);
        $endpoint->set_snapshot($snapshot);
        $endpoint->set_body($body);
        return $this->perform_request($endpoint);
    }
    /**
     * $params['repository'] = (string) The repository for which to get the stats for
     *
     * @param array $params Associative array of parameters
     * @return array
     *
     * @note This API is EXPERIMENTAL and may be changed or removed completely in a future release
     *
     */
    public function repository_stats(array $params = [])
    {
        $repository = $this->extract_argument($params, 'repository');
        $endpoint_builder = $this->endpoints;
        $endpoint = $endpoint_builder('SearchableSnapshots\RepositoryStats');
        $endpoint->set_params($params);
        $endpoint->set_repository($repository);
        return $this->perform_request($endpoint);
    }
    /**
     * $params['index'] = (list) A comma-separated list of index names
     *
     * @param array $params Associative array of parameters
     * @return array
     *
     * @note This API is EXPERIMENTAL and may be changed or removed completely in a future release
     *
     */
    public function stats(array $params = [])
    {
        $index = $this->extract_argument($params, 'index');
        $endpoint_builder = $this->endpoints;
        $endpoint = $endpoint_builder('SearchableSnapshots\Stats');
        $endpoint->set_params($params);
        $endpoint->set_index($index);
        return $this->perform_request($endpoint);
    }
}