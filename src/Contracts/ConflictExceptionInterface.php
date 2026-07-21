<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Contracts;

use Throwable;

/**
 * Identifies an exception caused by a conflict with the current resource state.
 *
 * @api
 */
interface ConflictExceptionInterface extends Throwable
{
}
