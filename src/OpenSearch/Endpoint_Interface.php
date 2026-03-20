<?php

declare (strict_types=1);
namespace Open_Search;

/**
 * Provides and interface for endpoints.
 */
interface Endpoint_Interface
{
    /**
     * Get the whitelist of allowed parameters.
     *
     * @return string[]
     */
    public function get_param_whitelist(): array;
    /**
     * Get the URI.
     */
    public function get_uri(): string;
    /**
     * Get the HTTP method.
     */
    public function get_method(): string;
    /**
     * Set the query string parameters.
     */
    public function set_params(array $params): static;
    /**
     * Get the query string parameters.
     */
    public function get_params(): array;
    /**
     * Get the options.
     *
     * @return array<string, mixed>
     */
    public function get_options(): array;
    /**
     * Get the index.
     */
    public function get_index(): ?string;
    /**
     * Set the index.
     *
     * @param string|string[]|null $index
     *
     * @return $this
     */
    public function set_index(string|array|null $index): static;
    /**
     * Get the document ID.
     *
     *
     * @return $this
     */
    public function set_id(int|string|null $doc_id): static;
    /**
     * Get the body of the request.
     */
    public function get_body(): string|array|null;
    /**
     * Set the body of the request.
     *
     * @param string|iterable<string,mixed>|null $body
     */
    public function set_body(string|iterable|null $body): static;
}