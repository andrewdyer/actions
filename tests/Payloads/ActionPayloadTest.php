<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Tests\Payloads;

use AndrewDyer\Actions\Payloads\ActionError;
use AndrewDyer\Actions\Payloads\ActionPayload;
use PHPUnit\Framework\TestCase;

/**
 * Validates ActionPayload factories and serialization guarantees.
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
        $payload = ActionPayload::success(['foo' => 'bar'], null, 201);

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

    /**
     * Confirms success payloads with meta include both data and meta fields.
     *
     * @return void
     */
    public function testSuccessPayloadWithMeta(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];
        $meta = [
            'total' => 5,
            'page' => 1,
            'perPage' => 2,
            'totalPages' => 3,
        ];

        $payload = ActionPayload::success($data, $meta, 200);

        self::assertSame(200, $payload->getStatusCode());
        self::assertSame(
            [
                'data' => $data,
                'meta' => $meta,
            ],
            $payload->jsonSerialize()
        );
    }

    /**
     * Ensures meta is excluded when not provided (backwards compatibility).
     *
     * @return void
     */
    public function testSuccessPayloadWithoutMetaExcludesMetaField(): void
    {
        $payload = ActionPayload::success(['foo' => 'bar']);

        $serialized = $payload->jsonSerialize();

        self::assertArrayHasKey('data', $serialized);
        self::assertArrayNotHasKey('meta', $serialized);
    }

    /**
     * Verifies meta preserves falsy values during serialization.
     *
     * @return void
     */
    public function testMetaPreservesFalsyValues(): void
    {
        $meta = [
            'hasMore' => false,
            'offset' => 0,
            'query' => '',
        ];

        $payload = ActionPayload::success(['items' => []], $meta, 200);

        $serialized = $payload->jsonSerialize();

        self::assertSame($meta, $serialized['meta']);
    }

    /**
     * Checks that toJson produces valid JSON with meta field.
     *
     * @return void
     */
    public function testToJsonWithMetaProducesValidJson(): void
    {
        $data = ['users' => []];
        $meta = ['total' => 0];
        $payload = ActionPayload::success($data, $meta, 200);

        $json = $payload->toJson();

        self::assertJson($json);
        self::assertSame(
            [
                'data' => $data,
                'meta' => $meta,
            ],
            json_decode($json, true)
        );
    }
}
