<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Contracts;

use Throwable;

/**
 * Marker interface for exceptions that represent an "unauthenticated" condition.
 *
 * Actions implementing the ADR pattern may throw exceptions that implement this
 * interface to signal that the request lacks valid authentication credentials.
 * AbstractAction catches any UnauthenticatedExceptionInterface and returns a 401
 * JSON response, without requiring knowledge of the HTTP layer in the throwing class.
 *
 * @api
 */
interface UnauthenticatedExceptionInterface extends Throwable
{
}
