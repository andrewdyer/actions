<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Exceptions;

use AndrewDyer\Actions\Contracts\ForbiddenExceptionInterface;
use Exception;

/**
 * Concrete exception representing a "forbidden" condition.
 *
 * Domain-specific exceptions should extend this class to signal that the authenticated
 * caller lacks the required permissions, allowing the action layer to automatically
 * return a 403 JSON response without coupling to HTTP concerns.
 *
 * @api
 */
class ForbiddenException extends Exception implements ForbiddenExceptionInterface
{
}
