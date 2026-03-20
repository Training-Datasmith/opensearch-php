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

use Guzzle_Http\Ring\Future\Future_Array_Interface;
use Open_Search\Common\Exceptions\Missing404Exception;
use Open_Search\Common\Exceptions\Routing_Missing_Exception;
use Open_Search\Endpoints\Abstract_Endpoint;
use Open_Search\Exception\Not_Found_Http_Exception;
use Open_Search\Transport;
use Open_Search\Transport_Interface;
abstract class Boolean_Request_Wrapper
{
    /**
     * Send a request with a boolean response.
     *
     * @return bool
     *   Returns FALSE for a 404 error, otherwise TRUE.
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public static function send_request(Abstract_Endpoint $endpoint, Transport_Interface $transport): bool
    {
        try {
            $transport->send_request($endpoint->get_method(), $endpoint->get_uri(), $endpoint->get_params(), $endpoint->get_body(), $endpoint->get_options());
        } catch (Not_Found_Http_Exception|Routing_Missing_Exception) {
            // Return false for 404 errors.
            return false;
        }
        return true;
    }
    /**
     * Perform Request
     *
     * @throws Missing404Exception
     * @throws RoutingMissingException
     *
     * @deprecated in 2.4.0 and will be removed in 3.0.0. Use \OpenSearch\Namespaces\BooleanRequestWrapper::sendRequest() instead.
     */
    public static function perform_request(Abstract_Endpoint $endpoint, Transport $transport)
    {
        @trigger_error(__METHOD__ . '() is deprecated in 2.4.0 and will be removed in 3.0.0. Use \OpenSearch\Namespaces\BooleanRequestWrapper::sendRequest() instead.');
        try {
            $response = $transport->perform_request($endpoint->get_method(), $endpoint->get_uri(), $endpoint->get_params(), $endpoint->get_body(), $endpoint->get_options());
            $response = $transport->result_or_future($response, $endpoint->get_options());
            if ($response instanceof Future_Array_Interface) {
                // async mode, can't easily resolve this...punt to user
                return $response;
            }
            if ($response['status'] === 200) {
                return true;
            }
            return false;
        } catch (Missing404Exception|Routing_Missing_Exception) {
            return false;
        }
    }
}