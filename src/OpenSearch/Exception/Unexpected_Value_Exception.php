<?php

declare (strict_types=1);
namespace Open_Search\Exception;

use Open_Search\Common\Exceptions\Open_Search_Exception;
/**
 * An exception thrown when invalid arguments are passed to a method.
 *
 * @phpstan-ignore class.implementsDeprecatedInterface
 */
class UnexpectedValueException extends \UnexpectedValueException implements Open_Search_Exception
{
}