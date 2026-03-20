<?php

declare (strict_types=1);
namespace Open_Search;

/**
 * Creates an OpenSearch client.
 */
interface Client_Factory_Interface
{
    /**
     * Creates a new OpenSearch client.
     *
     * @param array<string,mixed> $options
     *   The options to use when creating the client. The options are specific to the HTTP client implementation.
     */
    public function create(array $options): Client;
}