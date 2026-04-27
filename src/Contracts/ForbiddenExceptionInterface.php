<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Contracts;

use Throwable;

/**
 * Marker interface for exceptions that represent a "forbidden" condition.
 *
 * Actions implementing the ADR pattern may throw exceptions that implement this
 * interface to signal that the authenticated caller lacks the required permissions.
 * AbstractAction catches any ForbiddenExceptionInterface and returns a 403 JSON
 * response, without requiring knowledge of the HTTP layer in the throwing class.
 *
 * @api
 */
interface ForbiddenExceptionInterface extends Throwable
{
}
