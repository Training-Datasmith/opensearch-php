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

use Open_Search\Endpoint_Factory_Interface;
use Open_Search\Endpoints\Abstract_Endpoint;
use Open_Search\Legacy_Endpoint_Factory;
use Open_Search\Legacy_Transport_Wrapper;
use Open_Search\Transport;
use Open_Search\Transport_Interface;
abstract class Abstract_Namespace
{
    /**
     * @var \OpenSearch\Transport
     *
     * @deprecated in 2.4.0 and will be removed in 3.0.0. Use $httpTransport property instead.
     */
    protected $transport;
    protected Transport_Interface $http_transport;
    protected Endpoint_Factory_Interface $endpoint_factory;
    /**
     * @var callable
     *
     * @deprecated in 2.4.0 and will be removed in 3.0.0. Use $endpointFactory property instead.
     */
    protected $endpoints;
    /**
     * @phpstan-ignore parameter.deprecatedClass
     */
    public function __construct(Transport_Interface|Transport $transport, callable|Endpoint_Factory_Interface $endpoint_factory)
    {
        if (!$transport instanceof Transport_Interface) {
            @trigger_error('Passing an instance of \OpenSearch\Transport to ' . __METHOD__ . '() is deprecated in 2.4.0 and will be removed in 3.0.0. Pass an instance of \OpenSearch\TransportInterface instead.', E_USER_DEPRECATED);
            // @phpstan-ignore property.deprecated
            $this->transport = $transport;
            // @phpstan-ignore new.deprecated
            $this->http_transport = new Legacy_Transport_Wrapper($transport);
        } else {
            $this->http_transport = $transport;
        }
        if (is_callable($endpoint_factory)) {
            @trigger_error('Passing a callable as $endpointFactory param to ' . __METHOD__ . '() is deprecated in 2.4.0 and will be removed in 3.0.0. Pass an instance of \OpenSearch\EndpointFactoryInterface instead.', E_USER_DEPRECATED);
            $endpoints = $endpoint_factory;
            // @phpstan-ignore new.deprecated
            $endpoint_factory = new Legacy_Endpoint_Factory($endpoint_factory);
        } else {
            $endpoints = function (string $c) use ($endpoint_factory): \Open_Search\Endpoints\Abstract_Endpoint {
                @trigger_error('The $endpoints property is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
                return $endpoint_factory->get_endpoint('OpenSearch\Endpoints\\' . $c);
            };
        }
        // @phpstan-ignore property.deprecated
        $this->endpoints = $endpoints;
        $this->endpoint_factory = $endpoint_factory;
    }
    /**
     * @return null|mixed
     */
    public function extract_argument(array &$params, string $arg)
    {
        if (array_key_exists($arg, $params) === true) {
            $val = $params[$arg];
            unset($params[$arg]);
            return $val;
        }
        return null;
    }
    protected function perform_request(Abstract_Endpoint $endpoint)
    {
        return $this->http_transport->send_request($endpoint->get_method(), $endpoint->get_uri(), $endpoint->get_params(), $endpoint->get_body(), $endpoint->get_options());
    }
}