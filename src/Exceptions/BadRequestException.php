<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Exceptions;

use AndrewDyer\Actions\Contracts\BadRequestExceptionInterface;
use Exception;

/**
 * Concrete exception representing a "bad request" condition.
 *
 * Domain-specific exceptions should extend this class to signal that a request
 * is malformed or violates business rules, allowing the action layer to automatically
 * return a 400 JSON response without coupling to HTTP concerns.
 *
 * @api
 */
class BadRequestException extends Exception implements BadRequestExceptionInterface
{
}
