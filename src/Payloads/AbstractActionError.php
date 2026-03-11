<?php

declare(strict_types=1);

namespace Anddye\Actions\Payloads;

/**
 * Shared base for action error payloads.
 *
 * Role: Payload. Supplies typed metadata for structured error responses.
 *
 * @api
 */
abstract readonly class AbstractActionError implements ActionErrorInterface
{
    /**
     * Initializes the immutable error payload.
     *
     * @param string $type
     * @param string|null $description
     */
    public function __construct(
        protected string $type,
        protected ?string $description = null
    ) {
    }

    /**
     * Returns the machine-readable error identifier.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the human-readable error detail.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Maps the payload to an array for JSON encoding.
     *
     * @return array{type:string,description:?string}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
        ];
    }
}
