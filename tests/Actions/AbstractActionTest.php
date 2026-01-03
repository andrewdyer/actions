<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Tests\Actions;

use PHPUnit\Framework\TestCase;
use Slim\Exception\HttpBadRequestException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Validates the AbstractAction helper behavior.
 *
 * Role: Test. Guards JSON responses and argument resolution.
 *
 * @internal
 */
final class AbstractActionTest extends TestCase
{
    /**
     * Ensures respondWithJson writes headers and serialized payloads.
     *
     * @return void
     */
    public function testRespondWithJsonWritesPayloadAndHeaders(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory
            ->createServerRequest('POST', '/things/42')
            ->withParsedBody(['name' => 'Widget']);

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new TestAction();
        $result = $action($request, $response, ['thingId' => 42]);

        self::assertSame('application/json; charset=utf-8', $result->getHeaderLine('Content-Type'));
        self::assertSame(200, $result->getStatusCode());

        $body = (string)$result->getBody();
        self::assertJson($body);

        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('data', $decoded);
        self::assertArrayNotHasKey('error', $decoded);
        self::assertSame(
            [
                'data' => [
                    'message' => 'ok',
                    'body' => ['name' => 'Widget'],
                    'thingId' => 42,
                ],
            ],
            $decoded
        );
    }

    /**
     * Asserts resolveArg throws when required arguments are absent.
     *
     * @return void
     */
    public function testResolveArgThrowsWhenMissing(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/missing');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new TestAction();

        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage('Missing route argument: thingId');

        $action($request, $response, []);
    }
}
