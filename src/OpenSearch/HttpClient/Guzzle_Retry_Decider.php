<?php

declare (strict_types=1);
namespace Open_Search\Http_Client;

use Guzzle_Http\Exception\Connect_Exception;
use Psr\Http\Message\Request_Interface;
use Psr\Http\Message\Response_Interface;
use Psr\Log\Logger_Interface;
/**
 * Retry decider for Guzzle HTTP Client.
 */
class Guzzle_Retry_Decider
{
    public function __construct(protected ?int $max_retries = 0, protected ?Logger_Interface $logger = null)
    {
    }
    public function __invoke(int $retries, ?Request_Interface $request, ?Response_Interface $response, $exception): bool
    {
        if ($retries >= $this->max_retries) {
            return false;
        }
        // Increment $retries after comparison for human display in log
        // message.
        if ($exception instanceof Connect_Exception) {
            $this->logger?->warning('Retrying request {retries} of {maxRetries}: {exception}', ['retries' => $retries + 1, 'maxRetries' => $this->max_retries, 'exception' => $exception->get_message()]);
            return true;
        }
        if ($response && $response->get_status_code() >= 500) {
            $this->logger?->warning('Retrying request {retries} of {maxRetries}: Status code {status}', ['retries' => $retries + 1, 'maxRetries' => $this->max_retries, 'status' => $response->get_status_code()]);
            return true;
        }
        // We only retry if there is a 500 or a ConnectException.
        return false;
    }
}