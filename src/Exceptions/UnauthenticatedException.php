<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Exceptions;

use AndrewDyer\Actions\Contracts\UnauthenticatedExceptionInterface;
use Exception;

/**
 * Concrete exception representing an "unauthenticated" condition.
 *
 * Domain-specific exceptions should extend this class to signal that a request
 * lacks valid authentication credentials, allowing the action layer to automatically
 * return a 401 JSON response without coupling to HTTP concerns.
 *
 * @api
 */
class UnauthenticatedException extends Exception implements UnauthenticatedExceptionInterface
{
}
