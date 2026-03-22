<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Payloads;

/**
 * Concrete payload enumerating standard action errors.
 *
 * Role: Payload. Provides helpers for building consistent error responses.
 *
 * @api
 */
final readonly class ActionError extends AbstractActionError
{
    public const string BAD_REQUEST = 'BAD_REQUEST';
    public const string UNAUTHENTICATED = 'UNAUTHENTICATED';
    public const string INSUFFICIENT_PRIVILEGES = 'INSUFFICIENT_PRIVILEGES';
    public const string NOT_ALLOWED = 'NOT_ALLOWED';
    public const string RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const string NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    public const string SERVER_ERROR = 'SERVER_ERROR';

    /**
     * Creates a bad request error representation.
     *
     * @param string|null $description
     *
     * @return self
     */
    public static function badRequest(?string $description = null): self
    {
        return new self(self::BAD_REQUEST, $description);
    }

    /**
     * Creates a not found error representation.
     *
     * @param string|null $description
     *
     * @return self
     */
    public static function notFound(?string $description = null): self
    {
        return new self(self::RESOURCE_NOT_FOUND, $description);
    }

    /**
     * Creates a not allowed error representation.
     *
     * @param string|null $description
     *
     * @return self
     */
    public static function notAllowed(?string $description = null): self
    {
        return new self(self::NOT_ALLOWED, $description);
    }

    /**
     * Creates an unauthenticated error representation.
     *
     * @param string|null $description
     *
     * @return self
     */
    public static function unauthenticated(?string $description = null): self
    {
        return new self(self::UNAUTHENTICATED, $description);
    }

    /**
     * Creates an insufficient privileges error representation.
     *
     * @param string|null $description
     *
     * @return self
     */
    public static function insufficientPrivileges(?string $description = null): self
    {
        return new self(self::INSUFFICIENT_PRIVILEGES, $description);
    }

    /**
     * Creates a not implemented error representation.
     *
     * @param string|null $description
     *
     * @return self
     */
    public static function notImplemented(?string $description = null): self
    {
        return new self(self::NOT_IMPLEMENTED, $description);
    }

    /**
     * Creates a generic server error representation.
     *
     * @param string|null $description
     *
     * @return self
     */
    public static function serverError(?string $description = null): self
    {
        return new self(self::SERVER_ERROR, $description);
    }
}
