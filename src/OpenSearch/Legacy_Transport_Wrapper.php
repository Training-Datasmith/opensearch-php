<?php

declare (strict_types=1);
namespace Open_Search;

// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Legacy_Transport_Wrapper::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * Transport that wraps the legacy transport.
 *
 * @deprecated in 2.4.0 and will be removed in 3.0.0. Use PsrTransport instead.
 */
class Legacy_Transport_Wrapper implements Transport_Interface
{
    public function __construct(protected Transport $transport)
    {
    }
    /**
     * {@inheritdoc}
     */
    public function send_request(string $method, string $uri, array $params = [], mixed $body = null, array $headers = []): iterable|string|null
    {
        // Provide legacy support for options.
        $options = $headers;
        $promise = $this->transport->perform_request($method, $uri, $params, $body, $options);
        return $this->transport->result_or_future($promise, $options);
    }
}