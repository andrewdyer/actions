<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Contracts;

use JsonSerializable;

/**
 * Contract for payloads describing action errors.
 *
 * @api
 */
interface ActionErrorInterface extends JsonSerializable
{
    /**
     * Returns the machine-readable error identifier.
     *
     * @return string The error type string.
     */
    public function getType(): string;

    /**
     * Returns the optional human-readable error detail.
     *
     * @return string|null The error description, or null if none was provided.
     */
    public function getDescription(): ?string;
}
