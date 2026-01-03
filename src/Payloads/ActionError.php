<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Payloads;

final readonly class ActionError extends AbstractActionError
{
    public const string BAD_REQUEST = 'BAD_REQUEST';
    public const string UNAUTHENTICATED = 'UNAUTHENTICATED';
    public const string INSUFFICIENT_PRIVILEGES = 'INSUFFICIENT_PRIVILEGES';
    public const string NOT_ALLOWED = 'NOT_ALLOWED';
    public const string RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const string NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    public const string SERVER_ERROR = 'SERVER_ERROR';

    public static function badRequest(?string $description = null): self
    {
        return new self(self::BAD_REQUEST, $description);
    }

    public static function notFound(?string $description = null): self
    {
        return new self(self::RESOURCE_NOT_FOUND, $description);
    }

    public static function notAllowed(?string $description = null): self
    {
        return new self(self::NOT_ALLOWED, $description);
    }

    public static function unauthenticated(?string $description = null): self
    {
        return new self(self::UNAUTHENTICATED, $description);
    }

    public static function insufficientPrivileges(?string $description = null): self
    {
        return new self(self::INSUFFICIENT_PRIVILEGES, $description);
    }

    public static function notImplemented(?string $description = null): self
    {
        return new self(self::NOT_IMPLEMENTED, $description);
    }

    public static function serverError(?string $description = null): self
    {
        return new self(self::SERVER_ERROR, $description);
    }
}
