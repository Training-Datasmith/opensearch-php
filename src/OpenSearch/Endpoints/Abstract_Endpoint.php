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
namespace Open_Search\Endpoints;

use function array_filter;
use Open_Search\Endpoint_Interface;
use Open_Search\Exception\UnexpectedValueException;
use Open_Search\Serializers\Serializer_Interface;
abstract class Abstract_Endpoint implements Endpoint_Interface
{
    /**
     * @var array
     */
    protected $params = [];
    /**
     * @var string|null
     */
    protected $index;
    /**
     * @var string|int|null
     */
    protected $id;
    /**
     * @var string|null
     */
    protected $method;
    /**
     * @var string|array|null
     */
    protected $body;
    private array $options = [];
    /**
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @return string[]
     */
    abstract public function get_param_whitelist(): array;
    abstract public function get_uri(): string;
    abstract public function get_method(): string;
    /**
     * Set the parameters for this endpoint
     *
     * @param mixed[] $params Array of parameters
     * @return $this
     */
    public function set_params(array $params): static
    {
        $this->extract_options($params);
        $this->check_user_params($params);
        $params = $this->convert_custom($params);
        $this->params = $this->convert_arrays_to_strings($params);
        $this->check_for_deprecations();
        return $this;
    }
    public function get_params(): array
    {
        return $this->params;
    }
    public function get_options(): array
    {
        return $this->options;
    }
    public function get_index(): ?string
    {
        return $this->index;
    }
    /**
     * @param string|string[]|null $index
     *
     * @return $this
     */
    public function set_index($index): static
    {
        if ($index === null) {
            return $this;
        }
        if (is_array($index) === true) {
            $index = array_filter($index);
            $index = array_map(trim(...), $index);
            $index = implode(',', $index);
        }
        $this->index = $index;
        return $this;
    }
    public function set_id(int|string|null $doc_id): static
    {
        if ($doc_id === null) {
            return $this;
        }
        if (is_int($doc_id)) {
            $doc_id = (string) $doc_id;
        }
        $this->id = $doc_id;
        return $this;
    }
    public function get_body(): string|array|null
    {
        return $this->body;
    }
    public function set_body(string|iterable|null $body): static
    {
        $this->body = $body;
        return $this;
    }
    protected function get_optional_uri(string $endpoint): string
    {
        $uri = [];
        $uri[] = $this->get_optional_index();
        $uri[] = $endpoint;
        $uri = array_filter($uri);
        return '/' . implode('/', $uri);
    }
    private function get_optional_index(): string
    {
        if (isset($this->index) === true) {
            return $this->index;
        }
        return '_all';
    }
    /**
     * @param array<string, mixed> $params
     *
     * @throws UnexpectedValueException
     */
    private function check_user_params(array $params): void
    {
        if (empty($params)) {
            return;
            //no params, just return.
        }
        $whitelist = array_merge($this->get_param_whitelist(), ['pretty', 'human', 'error_trace', 'source', 'filter_path', 'opaqueId']);
        $invalid = array_diff(array_keys($params), $whitelist);
        if (count($invalid) > 0) {
            sort($invalid);
            sort($whitelist);
            throw new UnexpectedValueException(sprintf((count($invalid) > 1 ? '"%s" are not valid parameters.' : '"%s" is not a valid parameter.') . ' Allowed parameters are "%s"', implode('", "', $invalid), implode('", "', $whitelist)));
        }
    }
    /**
     * @param array<string, mixed> $params Note: this is passed by-reference!
     */
    private function extract_options(array &$params): void
    {
        // Extract out client options, then start transforming
        if (isset($params['client']) === true) {
            // Check if the opaqueId is populated and add the header
            if (isset($params['client']['opaqueId']) === true) {
                if (isset($params['client']['headers']) === false) {
                    $params['client']['headers'] = [];
                }
                $params['client']['headers']['x-opaque-id'] = [trim((string) $params['client']['opaqueId'])];
                unset($params['client']['opaqueId']);
            }
            $this->options['client'] = $params['client'];
            unset($params['client']);
        }
        $ignore = $this->options['client']['ignore'] ?? null;
        if (isset($ignore) === true) {
            if (is_string($ignore)) {
                $this->options['client']['ignore'] = explode(',', $ignore);
            } elseif (is_array($ignore)) {
                $this->options['client']['ignore'] = $ignore;
            } else {
                $this->options['client']['ignore'] = [$ignore];
            }
        }
    }
    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function convert_custom(array $params): array
    {
        if (isset($params['custom']) === true) {
            foreach ($params['custom'] as $k => $v) {
                $params[$k] = $v;
            }
            unset($params['custom']);
        }
        return $params;
    }
    private function convert_arrays_to_strings(array $params): array
    {
        foreach ($params as $key => &$value) {
            if ($key === 'client') {
                continue;
            }
            if ($key == 'custom') {
                continue;
            }
            if (!(is_array($value) === true)) {
                continue;
            }
            if ($this->is_nested_array($value) === true) {
                continue;
            }
            $value = implode(',', $value);
        }
        return $params;
    }
    private function is_nested_array(array $a): bool
    {
        foreach ($a as $v) {
            if (is_array($v)) {
                return true;
            }
        }
        return false;
    }
    /**
     * This function returns all param deprecations also optional with a replacement field
     *
     * @return array<string, string|null>
     */
    protected function get_param_deprecation(): array
    {
        return [];
    }
    private function check_for_deprecations(): void
    {
        $deprecations = $this->get_param_deprecation();
        if ($deprecations === []) {
            return;
        }
        $keys = array_keys($this->params);
        foreach ($keys as $key) {
            if (array_key_exists($key, $deprecations)) {
                $val = $deprecations[$key];
                $msg = sprintf('The parameter "%s" is deprecated and will be removed without replacement in the next major version', $key);
                if ($val) {
                    $msg = sprintf('The parameter "%s" is deprecated and will be replaced with parameter "%s" in the next major version', $key, $val);
                }
                trigger_error($msg, E_USER_DEPRECATED);
            }
        }
    }
}