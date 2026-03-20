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
namespace Open_Search\Helper\Iterators;

use Iterator;
use Open_Search\Client;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Search_Response_Iterator::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * @deprecated in 2.4.0 and will be removed in 3.0.0.
 */
class Search_Response_Iterator implements Iterator
{
    private int $current_key = 0;
    /**
     * @var array
     */
    private $current_scrolled_response;
    /**
     * @var string|null
     */
    private $scroll_id;
    /**
     * @var string duration
     */
    private $scroll_ttl;
    /**
     * Constructor
     *
     * @param array $params Associative array of parameters
     * @see   Client::search()
     */
    public function __construct(private readonly Client $client, private array $params)
    {
        if (isset($this->params['scroll'])) {
            $this->scroll_ttl = $this->params['scroll'];
        }
    }
    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->clear_scroll();
    }
    /**
     * Sets the time to live duration of a scroll window
     */
    public function set_scroll_timeout(string $time_to_live): Search_Response_Iterator
    {
        $this->scroll_ttl = $time_to_live;
        return $this;
    }
    /**
     * Clears the current scroll window if there is a scroll_id stored
     */
    private function clear_scroll(): void
    {
        if (!empty($this->scroll_id)) {
            $this->client->clear_scroll(['scroll_id' => $this->scroll_id, 'client' => ['ignore' => 404]]);
            $this->scroll_id = null;
        }
    }
    /**
     * Rewinds the iterator by performing the initial search.
     *
     * @see    Iterator::rewind()
     */
    public function rewind(): void
    {
        $this->clear_scroll();
        $this->current_key = 0;
        $this->current_scrolled_response = $this->client->search($this->params);
        $this->scroll_id = $this->current_scrolled_response['_scroll_id'];
    }
    /**
     * Fetches every "page" after the first one using the lastest "scroll_id"
     *
     * @see    Iterator::next()
     */
    public function next(): void
    {
        $this->current_scrolled_response = $this->client->scroll(['scroll' => $this->scroll_ttl, 'body' => ['scroll_id' => $this->scroll_id]]);
        $this->scroll_id = $this->current_scrolled_response['_scroll_id'];
        $this->current_key++;
    }
    /**
     * Returns a boolean value indicating if the current page is valid or not
     *
     * @see    Iterator::valid()
     */
    public function valid(): bool
    {
        return isset($this->current_scrolled_response['hits']['hits'][0]);
    }
    /**
     * Returns the current "page"
     *
     * @see    Iterator::current()
     */
    public function current(): array
    {
        return $this->current_scrolled_response;
    }
    /**
     * Returns the current "page number" of the current "page"
     *
     * @see    Iterator::key()
     */
    public function key(): int
    {
        return $this->current_key;
    }
}