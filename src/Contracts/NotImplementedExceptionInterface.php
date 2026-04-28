<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Contracts;

use Throwable;

/**
 * Marker interface for exceptions that represent a "not implemented" condition.
 *
 * Actions implementing the ADR pattern may throw exceptions that implement this
 * interface to signal that the requested functionality has not yet been implemented.
 * AbstractAction catches any NotImplementedExceptionInterface and returns a 501
 * JSON response, without requiring knowledge of the HTTP layer in the throwing class.
 *
 * @api
 */
interface NotImplementedExceptionInterface extends Throwable
{
}
