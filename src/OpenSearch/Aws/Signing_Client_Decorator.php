<?php

declare (strict_types=1);
namespace Open_Search\Aws;

use Aws\Credentials\Credentials_Interface;
use Aws\Signature\Signature_Interface;
use Psr\Http\Client\Client_Interface;
use Psr\Http\Message\Request_Interface;
use Psr\Http\Message\Response_Interface;
/**
 * A decorator client that signs requests using the provided AWS credentials and signer.
 */
class Signing_Client_Decorator implements Client_Interface
{
    /**
     * @param ClientInterface $inner The client to decorate.
     * @param CredentialsInterface $credentials The AWS credentials to use for signing requests.
     * @param SignatureInterface $signer The AWS signer to use for signing requests.
     * @param array $headers Additional headers to add to the request. `Host` is required.
     */
    public function __construct(protected Client_Interface $inner, protected Credentials_Interface $credentials, protected Signature_Interface $signer, protected array $headers = [])
    {
    }
    public function send_request(Request_Interface $request): Response_Interface
    {
        foreach ($this->headers as $name => $value) {
            $request = $request->with_header($name, $value);
        }
        if (empty($request->get_header_line('Host'))) {
            throw new \RuntimeException('Missing Host header.');
        }
        $request = $request->with_header('x-amz-content-sha256', hash('sha256', (string) $request->get_body()));
        $request = $this->signer->sign_request($request, $this->credentials);
        return $this->inner->send_request($request);
    }
}