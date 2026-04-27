<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Tests\Payloads;

use AndrewDyer\Actions\Payloads\ActionError;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the ActionError factory helpers and serialization.
 *
 * @internal
 */
final class ActionErrorTest extends TestCase
{
    /**
     * Ensures each factory emits the expected error metadata.
     *
     * @dataProvider factoryProvider
     *
     * @param string      $factoryMethod
     * @param string      $expectedType
     * @param string|null $description
     *
     * @return void
     */
    public function testFactoryOutputs(string $factoryMethod, string $expectedType, ?string $description): void
    {
        $error = ActionError::$factoryMethod($description);

        self::assertSame($expectedType, $error->getType());
        self::assertSame($description, $error->getDescription());
    }

    /**
     * Supplies representative factory scenarios.
     *
     * @return iterable<string, array{0:string,1:string,2:?string}>
     */
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

    /**
     * Verifies jsonSerialize emits the documented schema.
     *
     * @return void
     */
    public function testJsonSerialization(): void
    {
        $error = ActionError::notFound();

        self::assertSame(
            [
                'type' => ActionError::RESOURCE_NOT_FOUND,
            ],
            $error->jsonSerialize()
        );
    }
}
