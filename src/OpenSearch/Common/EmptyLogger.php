<?php

declare (strict_types=1);
/**
 * Copyright OpenSearch Contributors
 * SPDX-License-Identifier: Apache-2.0
 *
 * OpenSearch PHP client
 *
 * @link      https://github.com/opensearch-project/opensearch-php/
 * @copyright Copyright (c) Elasticsearch B.V (https://www.elastic.co)
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License, Version 2.1
 *
 * Licensed to Elasticsearch B.V under one or more agreements.
 * Elasticsearch B.V licenses this file to you under the Apache 2.0 License or
 * the GNU Lesser General Public License, Version 2.1, at your option.
 * See the LICENSE file in the project root for more information.
 */
namespace Open_Search\Common;

use Psr\Log\Abstract_Logger;
use Psr\Log\Logger_Interface;
// @phpstan-ignore classConstant.deprecatedClass
@trigger_error(Empty_Logger::class . ' is deprecated in 2.4.0 and will be removed in 3.0.0. Use Psr\Log\NullLogger instead', E_USER_DEPRECATED);
/**
 * Class EmptyLogger
 *
 * Logger that doesn't do anything.  Similar to Monolog's NullHandler,
 * but avoids the overhead of partially loading Monolog
 *
 * @deprecated in 2.4.0 and will be removed in 3.0.0. Use Psr\Log\NullLogger instead.
 */
class Empty_Logger extends Abstract_Logger implements Logger_Interface
{
    /**
     * {@inheritDoc}
     */
    public function log($level, $message, array $context = []): void
    {
    }
}