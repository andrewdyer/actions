<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Tests\Payloads;

use PHPUnit\Framework\TestCase;
use Slim\ApiKernel\Payloads\ActionError;
use Slim\ApiKernel\Payloads\ActionPayload;

final class ActionPayloadTest extends TestCase
{
    public function testSuccessPayload(): void
    {
        $payload = ActionPayload::success(['foo' => 'bar'], 201);

        self::assertSame(201, $payload->getStatusCode());
        self::assertSame(['data' => ['foo' => 'bar']], $payload->jsonSerialize());
    }

    public function testErrorPayload(): void
    {
        $error = ActionError::serverError('Boom');
        $payload = ActionPayload::error($error, 500);

        self::assertSame(500, $payload->getStatusCode());
        self::assertSame(['error' => $error], $payload->jsonSerialize());
    }

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
