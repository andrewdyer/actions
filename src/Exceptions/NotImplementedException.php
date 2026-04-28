<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Exceptions;

use AndrewDyer\Actions\Contracts\NotImplementedExceptionInterface;
use Exception;

/**
 * Concrete exception representing a "not implemented" condition.
 *
 * Domain-specific exceptions should extend this class to signal that the requested
 * functionality has not yet been implemented, allowing the action layer to automatically
 * return a 501 JSON response without coupling to HTTP concerns.
 *
 * @api
 */
class NotImplementedException extends Exception implements NotImplementedExceptionInterface
{
}
