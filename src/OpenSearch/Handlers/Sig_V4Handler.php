<?php

declare (strict_types=1);
namespace Open_Search\Handlers;

use Aws\Credentials\Credential_Provider;
use Aws\Signature\Signature_V4;
use Guzzle_Http\Psr7\Request;
use Guzzle_Http\Psr7\Uri;
use Guzzle_Http\Psr7\Utils;
use Open_Search\Client_Builder;
use Psr\Http\Message\Request_Interface;
use RuntimeException;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Sig_V4handler::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0.', E_USER_DEPRECATED);
/**
 * @phpstan-type RingPhpRequest array{http_method: string, scheme: string, uri: string, query_string?: string, version?: string, headers: array<string, list<string>>, body: string|resource|null, client?: array<string, mixed>}
 *
 * @deprecated in 2.4.0 and will be removed in 3.0.0. Use \OpenSearch\Aws\SigV4RequestFactory instead.
 */
class Sig_V4handler
{
    /**
     * @var SignatureV4
     */
    private $signer;
    /**
     * @var callable
     */
    private $credential_provider;
    /**
     * @var callable
     */
    private $wrapped_handler;
    /**
     * A handler that applies an AWS V4 signature before dispatching requests.
     *
     * @param string        $region                 The region of your Amazon
     *                                              OpenSearch Service domain
     * @param string        $service                The Service of your Amazon
     *                                              OpenSearch Service domain
     * @param callable|null $credentialProvider     A callable that returns a
     *                                              promise that is fulfilled
     *                                              with an instance of
     *                                              Aws\Credentials\Credentials
     * @param callable|null $wrappedHandler         A RingPHP handler
     */
    public function __construct(string $region, string $service, ?callable $credential_provider = null, ?callable $wrapped_handler = null)
    {
        self::assert_dependencies_installed();
        $this->signer = new Signature_V4($service, $region);
        $this->wrapped_handler = $wrapped_handler ?: Client_Builder::default_handler();
        $this->credential_provider = $credential_provider ?: Credential_Provider::default_provider();
    }
    /**
     * @phpstan-param RingPhpRequest $request
     */
    public function __invoke(array $request): mixed
    {
        $creds = call_user_func($this->credential_provider)->wait();
        $psr7Request = $this->create_psr7request($request);
        $psr7Request = $psr7Request->with_header('x-amz-content-sha256', Utils::hash($psr7Request->get_body(), 'sha256'));
        $signed_request = $this->signer->sign_request($psr7Request, $creds);
        return call_user_func($this->wrapped_handler, $this->create_ring_request($signed_request, $request));
    }
    public static function assert_dependencies_installed(): void
    {
        if (!class_exists(Signature_V4::class)) {
            throw new RuntimeException('The AWS SDK for PHP must be installed in order to use the SigV4 signing handler');
        }
    }
    /**
     * @phpstan-param RingPhpRequest $ringPhpRequest
     */
    private function create_psr7request(array $ring_php_request): Request
    {
        // fix for uppercase 'Host' array key in elasticsearch-php 5.3.1 and backward compatible
        // https://github.com/aws/aws-sdk-php/issues/1225
        $host_key = isset($ring_php_request['headers']['Host']) ? 'Host' : 'host';
        // Amazon ES/OS listens on standard ports (443 for HTTPS, 80 for HTTP).
        // Consequently, the port should be stripped from the host header.
        $parsed_url = parse_url($ring_php_request['headers'][$host_key][0]);
        if (isset($parsed_url['host'])) {
            $ring_php_request['headers'][$host_key][0] = $parsed_url['host'];
        }
        // Create a PSR-7 URI from the array passed to the handler
        $uri = (new Uri($ring_php_request['uri']))->with_scheme($ring_php_request['scheme'])->with_host($ring_php_request['headers'][$host_key][0]);
        if (isset($ring_php_request['query_string'])) {
            $uri = $uri->with_query($ring_php_request['query_string']);
        }
        // Create a PSR-7 request from the array passed to the handler
        return new Request($ring_php_request['http_method'], $uri, $ring_php_request['headers'], $ring_php_request['body']);
    }
    /**
     * @phpstan-param RingPhpRequest $originalRequest
     *
     * @phpstan-return RingPhpRequest
     */
    private function create_ring_request(Request_Interface $request, array $original_request): array
    {
        $uri = $request->get_uri();
        $body = (string) $request->get_body();
        // RingPHP currently expects empty message bodies to be null:
        // https://github.com/guzzle/RingPHP/blob/4c8fe4c48a0fb7cc5e41ef529e43fecd6da4d539/src/Client/CurlFactory.php#L202
        if (empty($body)) {
            $body = null;
        }
        // Reset the explicit port in the URL
        $client = $original_request['client'];
        unset($client['curl'][CURLOPT_PORT]);
        $ring_request = ['http_method' => $request->get_method(), 'scheme' => $uri->get_scheme(), 'uri' => $uri->get_path(), 'body' => $body, 'headers' => $request->get_headers(), 'client' => $client];
        if ($uri->get_query()) {
            $ring_request['query_string'] = $uri->get_query();
        }
        return $ring_request;
    }
}