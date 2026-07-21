<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Exceptions;

use AndrewDyer\Actions\Contracts\ConflictExceptionInterface;
use Exception;

/**
 * Concrete exception representing a resource conflict.
 *
 * Domain-specific exceptions may extend this class to automatically return a
 * 409 JSON response from an action.
 *
 * @api
 */
class ConflictException extends Exception implements ConflictExceptionInterface
{
}
