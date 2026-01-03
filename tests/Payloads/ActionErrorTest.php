<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Tests\Payloads;

use PHPUnit\Framework\TestCase;
use Slim\ApiKernel\Payloads\ActionError;

final class ActionErrorTest extends TestCase
{
    /**
     * @dataProvider factoryProvider
     */
    public function testFactoryOutputs(string $factoryMethod, string $expectedType, ?string $description): void
    {
        $error = ActionError::$factoryMethod($description);

        self::assertSame($expectedType, $error->getType());
        self::assertSame($description, $error->getDescription());
    }

    public static function factoryProvider(): iterable
    {
        yield 'bad request with description' => ['badRequest', ActionError::BAD_REQUEST, 'Invalid input'];
        yield 'not found default description' => ['notFound', ActionError::RESOURCE_NOT_FOUND, null];
        yield 'not allowed' => ['notAllowed', ActionError::NOT_ALLOWED, 'Method not allowed'];
        yield 'unauthenticated' => ['unauthenticated', ActionError::UNAUTHENTICATED, 'Missing token'];
        yield 'insufficient privileges' => ['insufficientPrivileges', ActionError::INSUFFICIENT_PRIVILEGES, 'Need admin role'];
        yield 'not implemented' => ['notImplemented', ActionError::NOT_IMPLEMENTED, 'Coming soon'];
        yield 'server error' => ['serverError', ActionError::SERVER_ERROR, 'Unexpected failure'];
    }

    public function testJsonSerialization(): void
    {
        $error = ActionError::notFound();

        self::assertSame(
            [
                'type' => ActionError::RESOURCE_NOT_FOUND,
                'description' => null,
            ],
            $error->jsonSerialize()
        );
    }
}
