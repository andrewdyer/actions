<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Payloads;

/**
 * Immutable payload representing action outcomes.
 *
 * Role: Payload. Couples response data with status codes and errors.
 *
 * @api
 */
final readonly class ActionPayload implements ActionPayloadInterface
{
    /**
     * Creates a new payload instance.
     *
     * @param mixed $data
     * @param int $statusCode
     * @param ActionErrorInterface|null $error
     */
    public function __construct(
        private mixed $data = null,
        private int $statusCode = 200,
        private ?ActionErrorInterface $error = null
    ) {
    }

    /**
     * Builds a successful payload with optional data.
     *
     * @param mixed $data
     * @param int $statusCode
     *
     * @return self
     */
    public static function success(mixed $data, int $statusCode = 200): self
    {
        return new self($data, $statusCode);
    }

    /**
     * Builds an error payload with the given status.
     *
     * @param ActionErrorInterface $error
     * @param int $statusCode
     *
     * @return self
     */
    public static function error(
        ActionErrorInterface $error,
        int $statusCode = 500
    ): self {
        return new self(null, $statusCode, $error);
    }

    /**
     * Returns the HTTP status code associated with the payload.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Reduces the payload to an array suitable for JSON serialization.
     *
     * @return array{data?:mixed,error?:ActionErrorInterface}
     */
    public function jsonSerialize(): array
    {
        $payload = [];

        if ($this->data !== null) {
            $payload['data'] = $this->data;
        }

        if ($this->error !== null) {
            $payload['error'] = $this->error;
        }

        return $payload;
    }

    /**
     * Encodes the payload as a JSON string.
     *
     * @return string
     *
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
