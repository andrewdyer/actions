<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Payloads;

abstract readonly class AbstractActionError implements ActionErrorInterface
{
    public function __construct(
        protected string $type,
        protected ?string $description = null
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
        ];
    }
}
