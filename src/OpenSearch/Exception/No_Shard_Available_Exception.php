<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Exception thrown when a 500 Internal Server Error HTTP error occurs for No Shard Available.
 */
class No_Shard_Available_Exception extends Internal_Server_Error_Http_Exception
{
}