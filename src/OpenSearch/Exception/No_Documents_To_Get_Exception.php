<?php

declare (strict_types=1);
namespace Open_Search\Exception;

/**
 * Exception thrown when a 500 Internal Server Error HTTP error occurs for No Documents To Get.
 */
class No_Documents_To_Get_Exception extends Internal_Server_Error_Http_Exception
{
}