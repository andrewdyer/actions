<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Payloads;

use JsonSerializable;

/**
 * Contract for action response payloads.
 *
 * Role: Payload. Couples serialized data with their HTTP status codes.
 *
 * @api
 */
interface ActionPayloadInterface extends JsonSerializable
{
    /**
     * Returns the HTTP status code represented by the payload.
     *
     * @return int
     */
    public function getStatusCode(): int;
}
