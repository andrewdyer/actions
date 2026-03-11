<?php

declare(strict_types=1);

namespace Anddye\Actions\Tests\Actions;

use Anddye\Actions\Actions\AbstractAction;
use Anddye\Actions\Payloads\ActionPayload;
use JsonException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
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
     * Ensures getParsedBody rejects unsupported payload types.
     *
     * @return void
     */
    public function testGetParsedBodyThrowsWhenBodyIsNotArray(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory
            ->createServerRequest('POST', '/things/42')
            ->withParsedBody((object)['name' => 'Widget']);

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new TestAction();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request body must decode to an array.');

        $action($request, $response, ['thingId' => 42]);
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing route argument: thingId');

        $action($request, $response, []);
    }

    /**
     * Ensures respondWithJson bubbles JsonException when encoding fails.
     *
     * @return void
     */
    public function testRespondWithJsonThrowsWhenEncodingFails(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/invalid');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $invalidUtf8 = "\xB1";

                return $this->respondWithJson(
                    ActionPayload::success(['payload' => $invalidUtf8])
                );
            }
        };

        $this->expectException(JsonException::class);

        $action($request, $response, []);
    }
}
