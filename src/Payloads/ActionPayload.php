<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Payloads;

use AndrewDyer\Actions\Contracts\ActionErrorInterface;
use AndrewDyer\Actions\Contracts\ActionPayloadInterface;

/**
 * Immutable payload representing action outcomes.
 *
 * @api
 */
final readonly class ActionPayload implements ActionPayloadInterface
{
    /**
     * Creates a new payload instance.
     *
     * @param mixed                    $data       The response data to include, or null when no data is applicable.
     * @param int                      $statusCode The HTTP status code for the response.
     * @param ActionErrorInterface|null $error     An optional structured error to attach for failure responses.
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
     * @param mixed $data       The response data to include in the payload.
     * @param int   $statusCode The HTTP status code, defaults to 200.
     *
     * @return self A new success payload instance.
     */
    public static function success(mixed $data, int $statusCode = 200): self
    {
        return new self($data, $statusCode);
    }

    /**
     * Builds an error payload with the given status.
     *
     * @param ActionErrorInterface $error      The structured error to include in the payload.
     * @param int                  $statusCode The HTTP status code associated with the error.
     *
     * @return self A new error payload instance.
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
     * @return int The HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Reduces the payload to an array suitable for JSON serialization.
     *
     * @return array{data?:mixed,error?:ActionErrorInterface} The serialized payload fields.
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
     * @throws \JsonException When the payload cannot be encoded to JSON.
     *
     * @return string A JSON-encoded representation of the payload.
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
