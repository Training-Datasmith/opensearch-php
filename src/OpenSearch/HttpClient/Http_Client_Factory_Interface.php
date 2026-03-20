<?php

declare (strict_types=1);
namespace Open_Search\Http_Client;

use Psr\Http\Client\Client_Interface;
/**
 * Interface for OpenSearch client factories.
 */
interface Http_Client_Factory_Interface
{
    /**
     * Build the OpenSearch client.
     *
     * @param array<string,mixed> $options
     */
    public function create(array $options): Client_Interface;
}