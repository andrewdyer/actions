<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Tests\Payloads;

use AndrewDyer\Actions\Payloads\ActionError;
use AndrewDyer\Actions\Payloads\ActionPayload;
use PHPUnit\Framework\TestCase;

/**
 * Validates ActionPayload factories and serialization guarantees.
 *
 * Role: Test. Ensures payloads expose stable status and body data.
 *
 * @internal
 */
final class ActionPayloadTest extends TestCase
{
    /**
     * Confirms success payloads carry provided data and status codes.
     *
     * @return void
     */
    public function testSuccessPayload(): void
    {
        $payload = ActionPayload::success(['foo' => 'bar'], 201);

        self::assertSame(201, $payload->getStatusCode());
        self::assertSame(['data' => ['foo' => 'bar']], $payload->jsonSerialize());
    }

    /**
     * Guards against dropping falsy values during serialization.
     *
     * @return void
     */
    public function testSuccessPayloadPreservesFalsyValues(): void
    {
        $payload = ActionPayload::success([
            'false' => false,
            'zero' => 0,
            'empty' => '',
        ]);

        self::assertSame(
            [
                'data' => [
                    'false' => false,
                    'zero' => 0,
                    'empty' => '',
                ],
            ],
            $payload->jsonSerialize()
        );
    }

    /**
     * Ensures error payloads expose status codes and embedded errors.
     *
     * @return void
     */
    public function testErrorPayload(): void
    {
        $error = ActionError::serverError('Boom');
        $payload = ActionPayload::error($error, 500);

        self::assertSame(500, $payload->getStatusCode());
        self::assertSame(['error' => $error], $payload->jsonSerialize());
    }

    /**
     * Checks that toJson produces valid JSON matching the schema.
     *
     * @return void
     */
    public function testToJsonProducesValidJson(): void
    {
        $payload = ActionPayload::success(['ok' => true]);

        $json = $payload->toJson();

        self::assertJson($json);
        self::assertSame(
            ['data' => ['ok' => true]],
            json_decode($json, true)
        );
    }
}
