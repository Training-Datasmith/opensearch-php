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
namespace Open_Search\Connections;

use Open_Search\Transport;
// @phpstan-ignore classConstant.deprecatedInterface
@trigger_error(Connection_Interface::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * @deprecated in 2.4.0 and will be removed in 3.0.0.
 */
interface Connection_Interface
{
    /**
     * Get the transport schema for this connection
     */
    public function get_transport_schema(): string;
    /**
     * Get the hostname for this connection
     */
    public function get_host(): string;
    /**
     * Get the port for this connection
     *
     * @return int
     */
    public function get_port();
    /**
     * Get the username:password string for this connection, null if not set
     */
    public function get_user_pass(): ?string;
    /**
     * Get the URL path suffix, null if not set
     */
    public function get_path(): ?string;
    /**
     * Check to see if this instance is marked as 'alive'
     */
    public function is_alive(): bool;
    /**
     * Mark this instance as 'alive'
     */
    public function mark_alive(): void;
    /**
     * Mark this instance as 'dead'
     */
    public function mark_dead(): void;
    /**
     * Return an associative array of information about the last request
     */
    public function get_last_request_info(): array;
    /**
     * @param array<string, mixed>|null $params
     * @param  mixed $body
     * @return mixed
     */
    public function perform_request(string $method, string $uri, ?array $params = [], $body = null, array $options = [], ?Transport $transport = null);
}