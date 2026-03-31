<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Contracts;

use JsonSerializable;

/**
 * Contract for action response payloads.
 *
 * @api
 */
interface ActionPayloadInterface extends JsonSerializable
{
    /**
     * Returns the HTTP status code represented by the payload.
     *
     * @return int The HTTP status code.
     */
    public function getStatusCode(): int;
}
