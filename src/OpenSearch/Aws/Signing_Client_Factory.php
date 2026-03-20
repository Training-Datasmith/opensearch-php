<?php

declare (strict_types=1);
namespace Open_Search\Aws;

use Aws\Credentials\Credential_Provider;
use Aws\Credentials\Credentials;
use Aws\Exception\Credentials_Exception;
use Aws\Signature\Signature_Interface;
use Aws\Signature\Signature_V4;
use Psr\Http\Client\Client_Interface;
use Psr\Log\Logger_Interface;
/**
 * A factory for creating an HTTP Client that signs requests using AWS credentials.
 */
class Signing_Client_Factory
{
    /**
     * The allowed AWS services.
     */
    public const ALLOWED_SERVICES = ['es', 'aoss'];
    public function __construct(protected ?Signature_Interface $signer = null, protected ?Credential_Provider $provider = null, protected ?Logger_Interface $logger = null)
    {
    }
    /**
     * Creates a new signing client.
     *
     * @param ClientInterface $innerClient
     *   The decorated inner HTTP client.
     * @param array<string,string> $options
     *   The AWS auth options.
     */
    public function create(Client_Interface $inner_client, array $options): Client_Interface
    {
        if (!isset($options['host'])) {
            throw new \InvalidArgumentException('The host option is required.');
        }
        // Get the credentials.
        $provider = $this->get_credential_provider($options);
        $promise = $provider();
        try {
            $credentials = $promise->wait();
        } catch (Credentials_Exception $e) {
            $this->logger?->error('Failed to get AWS credentials: @message', ['@message' => $e->get_message()]);
            $credentials = new Credentials('', '');
        }
        // Get the signer.
        $signer = $this->get_signer($options);
        return new Signing_Client_Decorator($inner_client, $credentials, $signer, ['host' => $options['host']]);
    }
    /**
     * Gets the credential provider.
     *
     * @param array<string,mixed> $options
     *   The options array.
     */
    protected function get_credential_provider(array $options): Credential_Provider|\Closure|null|callable
    {
        // Check for a provided credential provider.
        if ($this->provider) {
            return $this->provider;
        }
        // Check for provided access key and secret.
        if (isset($options['credentials'])) {
            return Credential_Provider::from_credentials(new Credentials($options['credentials']['access_key'] ?? '', $options['credentials']['secret_key'] ?? '', $options['credentials']['session_token'] ?? null));
        }
        // Fallback to the default provider.
        return Credential_Provider::default_provider();
    }
    /**
     * Gets the request signer.
     *
     * @param array<string,string> $options
     *   The options.
     */
    protected function get_signer(array $options): Signature_Interface
    {
        if ($this->signer) {
            return $this->signer;
        }
        if (!isset($options['region'])) {
            throw new \InvalidArgumentException('The region option is required.');
        }
        $service = $options['service'] ?? 'es';
        if (!in_array($service, self::ALLOWED_SERVICES, true)) {
            throw new \InvalidArgumentException('The service option must be either "es" or "aoss".');
        }
        return new Signature_V4($service, $options['region'], $options);
    }
}