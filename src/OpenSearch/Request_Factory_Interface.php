<?php

declare (strict_types=1);
namespace Open_Search;

use Psr\Http\Message\Request_Interface;
interface Request_Factory_Interface
{
    /**
     * Create a new request.
     *
     * @param array<string, mixed> $params
     * @param string|array<string, mixed>|null $body
     * @param array<string, string> $headers
     */
    public function create_request(string $method, string $uri, array $params = [], string|array|null $body = null, array $headers = []): Request_Interface;
}