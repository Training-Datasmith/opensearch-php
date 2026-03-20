<?php

declare (strict_types=1);
/**
 *  Copyright OpenSearch Contributors
 *   SPDX-License-Identifier: Apache-2.0
 *
 *   The OpenSearch Contributors require contributions made to
 *   this file be licensed under the Apache-2.0 license or a
 *   compatible open source license.
 */
namespace Open_Search\Endpoints\Ml;

use Open_Search\Endpoints\Abstract_Endpoint;
class Get_Connectors extends Abstract_Endpoint
{
    /**
     * @return string[]
     */
    public function get_param_whitelist(): array
    {
        return [];
    }
    public function get_uri(): string
    {
        return '/_plugins/_ml/connectors/_search';
    }
    public function get_method(): string
    {
        return 'POST';
    }
}