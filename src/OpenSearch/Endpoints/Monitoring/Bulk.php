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
namespace Open_Search\Endpoints\Monitoring;

use Open_Search\Common\Exceptions\InvalidArgumentException;
use Open_Search\Endpoints\Abstract_Endpoint;
use Open_Search\Serializers\Serializer_Interface;
use Traversable;
class Bulk extends Abstract_Endpoint
{
    public function __construct(Serializer_Interface $serializer)
    {
        $this->serializer = $serializer;
    }
    public function get_uri(): string
    {
        return '/_monitoring/bulk';
    }
    public function get_param_whitelist(): array
    {
        return ['system_id', 'system_api_version', 'interval'];
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
        if (is_array($body) === true || $body instanceof Traversable) {
            foreach ($body as $item) {
                $this->body .= $this->serializer->serialize($item) . "\n";
            }
        } elseif (is_string($body)) {
            $this->body = $body;
            if (!str_ends_with($body, "\n")) {
                $this->body .= "\n";
            }
        } else {
            throw new InvalidArgumentException('Body must be an array, traversable object or string');
        }
        return $this;
    }
}