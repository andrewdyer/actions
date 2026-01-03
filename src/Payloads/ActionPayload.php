<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Payloads;

final readonly class ActionPayload implements ActionPayloadInterface
{
    public function __construct(
        private mixed $data = null,
        private int $statusCode = 200,
        private ?ActionErrorInterface $error = null
    ) {
    }

    public static function success(mixed $data, int $statusCode = 200): self
    {
        return new self($data, $statusCode);
    }

    public static function error(
        ActionErrorInterface $error,
        int $statusCode = 500
    ): self {
        return new self(null, $statusCode, $error);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function jsonSerialize(): array
    {
        return array_filter(
            [
                'data' => $this->data,
                'error' => $this->error,
            ],
            static fn ($value) => $value !== null
        );
    }

    public function toJson(): string
    {
        return json_encode(
            $this,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
        );
    }
}
