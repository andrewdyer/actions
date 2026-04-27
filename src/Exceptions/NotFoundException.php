<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Exceptions;

use AndrewDyer\Actions\Contracts\NotFoundExceptionInterface;
use Exception;

/**
 * Concrete exception representing a "resource not found" condition.
 *
 * Domain-specific exceptions should extend this class to signal that a requested
 * resource does not exist, allowing the action layer to automatically return a
 * 404 JSON response without coupling to HTTP concerns.
 *
 * @api
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
