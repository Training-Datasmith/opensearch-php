<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Factory for creating HTTP exceptions.
 */
class Http_Exception_Factory
{
    public static function create(int $status_code, string|array|null $data, array $headers = [], int $code = 0, ?\Throwable $previous = null): Http_Exception_Interface
    {
        $error_message = Error_Message_Extractor::extract_error_message($data);
        return match ($status_code) {
            400 => self::create_bad_request_exception($error_message, $previous, $code, $headers),
            401 => new Unauthorized_Http_Exception($error_message, $headers, $code, $previous),
            403 => new Forbidden_Http_Exception($error_message, $headers, $code, $previous),
            404 => new Not_Found_Http_Exception($error_message, $headers, $code, $previous),
            406 => new Not_Acceptable_Http_Exception($error_message, $headers, $code, $previous),
            409 => new Conflict_Http_Exception($error_message, $headers, $code, $previous),
            429 => new Too_Many_Requests_Http_Exception($error_message, $headers, $code, $previous),
            500 => self::create_internal_server_error_exception($error_message, $previous, $code, $headers),
            503 => new Service_Unavailable_Http_Exception($error_message, $headers, $code, $previous),
            default => new Http_Exception($status_code, $error_message, $headers, $code, $previous),
        };
    }
    private static function create_bad_request_exception(string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = []): Http_Exception_Interface
    {
        if (str_contains($message, 'script_lang not supported')) {
            return new Script_Lang_Not_Supported_Exception($message);
        }
        return new Bad_Request_Http_Exception($message, $headers, $code, $previous);
    }
    /**
     * Create an InternalServerErrorHttpException from the given parameters.
     */
    private static function create_internal_server_error_exception(string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = []): Http_Exception_Interface
    {
        if (str_contains($message, 'RoutingMissingException')) {
            return new Routing_Missing_Exception($message);
        }
        if (preg_match('/ActionRequestValidationException.+ no documents to get/', $message) === 1) {
            return new No_Documents_To_Get_Exception($message);
        }
        if (str_contains($message, 'NoShardAvailableActionException')) {
            return new No_Shard_Available_Exception($message);
        }
        return new Internal_Server_Error_Http_Exception($message, $headers, $code, $previous);
    }
}