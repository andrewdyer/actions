<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Contracts;

use Throwable;

/**
 * Marker interface for exceptions that represent a "bad request" condition.
 *
 * Actions implementing the ADR pattern may throw exceptions that implement this
 * interface to signal that the request is malformed or violates business rules.
 * AbstractAction catches any BadRequestExceptionInterface and returns a 400 JSON
 * response, without requiring knowledge of the HTTP layer in the throwing class.
 *
 * @api
 */
interface BadRequestExceptionInterface extends Throwable
{
}
