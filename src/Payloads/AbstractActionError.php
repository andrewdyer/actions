<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Payloads;

use AndrewDyer\Actions\Contracts\ActionErrorInterface;

/**
 * Shared base for action error payloads.
 *
 * @api
 */
abstract readonly class AbstractActionError implements ActionErrorInterface
{
    /**
     * Initializes the immutable error payload.
     *
     * @param string      $type        The machine-readable error identifier.
     * @param string|null $description An optional human-readable description of the error.
     */
    public function __construct(
        protected string $type,
        protected ?string $description = null
    ) {
    }

    /**
     * Returns the machine-readable error identifier.
     *
     * @return string The error type string.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the human-readable error detail.
     *
     * @return string|null The error description, or null if none was provided.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Maps the error payload to an array for JSON encoding.
     *
     * @return array{type:string,description:?string} The serialized fields of the error.
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
        ];
    }
}
