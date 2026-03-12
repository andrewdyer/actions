<?php

declare(strict_types=1);

namespace Anddye\Actions\Payloads;

use JsonSerializable;

/**
 * Contract for payloads describing action errors.
 *
 * Role: Payload. Guides serialization of structured failure responses.
 *
 * @api
 */
interface ActionErrorInterface extends JsonSerializable
{
    /**
     * Returns the machine-readable error identifier.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Returns the optional human-readable error detail.
     *
     * @return string|null
     */
    public function getDescription(): ?string;
}
