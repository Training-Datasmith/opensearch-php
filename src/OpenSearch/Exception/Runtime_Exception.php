<?php

declare (strict_types=1);
namespace Open_Search\Exception;

use Open_Search\Common\Exceptions\Open_Search_Exception;
/**
 * An exception thrown when a runtime error occurs.
 *
 * @phpstan-ignore class.implementsDeprecatedInterface
 */
class RuntimeException extends \RuntimeException implements Open_Search_Exception
{
}