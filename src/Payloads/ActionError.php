<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Payloads;

/**
 * Concrete payload enumerating standard action errors.
 *
 * @api
 */
final readonly class ActionError extends AbstractActionError
{
    /** Indicates a malformed or otherwise invalid client request. */
    public const string BAD_REQUEST = 'BAD_REQUEST';

    /** Indicates the request lacks valid authentication credentials. */
    public const string UNAUTHENTICATED = 'UNAUTHENTICATED';

    /** Indicates the authenticated caller lacks the required permissions. */
    public const string INSUFFICIENT_PRIVILEGES = 'INSUFFICIENT_PRIVILEGES';

    /** Indicates the requested operation or HTTP method is not permitted. */
    public const string NOT_ALLOWED = 'NOT_ALLOWED';

    /** Indicates the requested resource could not be found. */
    public const string RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';

    /** Indicates the requested functionality has not yet been implemented. */
    public const string NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';

    /** Indicates an unexpected internal server failure. */
    public const string SERVER_ERROR = 'SERVER_ERROR';

    /**
     * Creates a bad request error representation.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @return self A new instance representing a bad request error.
     */
    public static function badRequest(?string $description = null): self
    {
        return new self(self::BAD_REQUEST, $description);
    }

    /**
     * Creates a not found error representation.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @return self A new instance representing a resource not found error.
     */
    public static function notFound(?string $description = null): self
    {
        return new self(self::RESOURCE_NOT_FOUND, $description);
    }

    /**
     * Creates a not allowed error representation.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @return self A new instance representing a not allowed error.
     */
    public static function notAllowed(?string $description = null): self
    {
        return new self(self::NOT_ALLOWED, $description);
    }

    /**
     * Creates an unauthenticated error representation.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @return self A new instance representing an unauthenticated error.
     */
    public static function unauthenticated(?string $description = null): self
    {
        return new self(self::UNAUTHENTICATED, $description);
    }

    /**
     * Creates an insufficient privileges error representation.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @return self A new instance representing an insufficient privileges error.
     */
    public static function insufficientPrivileges(?string $description = null): self
    {
        return new self(self::INSUFFICIENT_PRIVILEGES, $description);
    }

    /**
     * Creates a not implemented error representation.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @return self A new instance representing a not implemented error.
     */
    public static function notImplemented(?string $description = null): self
    {
        return new self(self::NOT_IMPLEMENTED, $description);
    }

    /**
     * Creates a generic server error representation.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @return self A new instance representing a server error.
     */
    public static function serverError(?string $description = null): self
    {
        return new self(self::SERVER_ERROR, $description);
    }
}
