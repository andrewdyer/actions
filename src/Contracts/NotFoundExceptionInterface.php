<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Contracts;

use Throwable;

/**
 * Marker interface for exceptions that represent a "resource not found" condition.
 *
 * Actions implementing the ADR pattern may throw exceptions that implement this
 * interface to signal that the requested resource does not exist. AbstractAction
 * catches any NotFoundExceptionInterface and returns a 404 JSON response, without
 * requiring knowledge of the HTTP layer in the throwing class.
 *
 * @api
 */
interface NotFoundExceptionInterface extends Throwable
{
}
