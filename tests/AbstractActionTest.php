<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Tests;

use AndrewDyer\Actions\AbstractAction;
use AndrewDyer\Actions\Exceptions\BadRequestException;
use AndrewDyer\Actions\Exceptions\ForbiddenException;
use AndrewDyer\Actions\Exceptions\NotFoundException;
use AndrewDyer\Actions\Exceptions\NotImplementedException;
use AndrewDyer\Actions\Exceptions\UnauthenticatedException;
use AndrewDyer\Actions\Payloads\ActionError;
use AndrewDyer\Actions\Payloads\ActionPayload;
use JsonException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Unit tests for AbstractAction.
 */
final class AbstractActionTest extends TestCase
{
    /**
     * Asserts that json writes the expected headers and payload.
     */
    public function testJsonWritesPayloadAndHeaders(): void
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
     * Asserts that getParsedBody throws when the body is not an array.
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
     * Asserts that resolveArg throws when the argument is absent.
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
     * Asserts that json propagates a JsonException when encoding fails.
     */
    public function testJsonThrowsWhenEncodingFails(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/invalid');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            /**
             * Returns a response using json with an invalid UTF-8 payload to trigger a JSON encoding failure.
             *
             * @throws JsonException When the payload cannot be JSON-encoded.
             *
             * @return ResponseInterface The response attempted by the action.
             */
            protected function handle(): ResponseInterface
            {
                $invalidUtf8 = "\xB1";

                return $this->json(
                    ActionPayload::success(['payload' => $invalidUtf8])
                );
            }
        };

        $this->expectException(JsonException::class);

        $action($request, $response, []);
    }

    /**
     * Asserts that BadRequestException is caught and returns a 400 JSON response.
     */
    public function testCatchesBadRequestException(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('POST', '/invalid');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new BadRequestException('Invalid email format');
            }
        };

        $result = $action($request, $response, []);

        self::assertSame(400, $result->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $result->getHeaderLine('Content-Type'));

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $decoded);
        self::assertArrayNotHasKey('data', $decoded);
        self::assertSame(ActionError::BAD_REQUEST, $decoded['error']['type'] ?? null);
        self::assertSame('Invalid email format', $decoded['error']['description'] ?? null);
    }

    /**
     * Asserts that ForbiddenException is caught and returns a 403 JSON response.
     */
    public function testCatchesForbiddenException(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('DELETE', '/admin/users/1');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new ForbiddenException('Admin role required');
            }
        };

        $result = $action($request, $response, []);

        self::assertSame(403, $result->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $result->getHeaderLine('Content-Type'));

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $decoded);
        self::assertArrayNotHasKey('data', $decoded);
        self::assertSame(ActionError::INSUFFICIENT_PRIVILEGES, $decoded['error']['type'] ?? null);
        self::assertSame('Admin role required', $decoded['error']['description'] ?? null);
    }

    /**
     * Asserts that NotFoundException is caught and returns a 404 JSON response.
     */
    public function testCatchesNotFoundException(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/users/999');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new NotFoundException('User not found');
            }
        };

        $result = $action($request, $response, []);

        self::assertSame(404, $result->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $result->getHeaderLine('Content-Type'));

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $decoded);
        self::assertArrayNotHasKey('data', $decoded);
        self::assertSame(ActionError::RESOURCE_NOT_FOUND, $decoded['error']['type'] ?? null);
        self::assertSame('User not found', $decoded['error']['description'] ?? null);
    }

    /**
     * Asserts that NotImplementedException is caught and returns a 501 JSON response.
     */
    public function testCatchesNotImplementedException(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('POST', '/payments/refund');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new NotImplementedException('Refunds are not yet supported');
            }
        };

        $result = $action($request, $response, []);

        self::assertSame(501, $result->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $result->getHeaderLine('Content-Type'));

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $decoded);
        self::assertArrayNotHasKey('data', $decoded);
        self::assertSame(ActionError::NOT_IMPLEMENTED, $decoded['error']['type'] ?? null);
        self::assertSame('Refunds are not yet supported', $decoded['error']['description'] ?? null);
    }

    /**
     * Asserts that UnauthenticatedException is caught and returns a 401 JSON response.
     */
    public function testCatchesUnauthenticatedException(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/protected');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new UnauthenticatedException('Token expired');
            }
        };

        $result = $action($request, $response, []);

        self::assertSame(401, $result->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $result->getHeaderLine('Content-Type'));

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $decoded);
        self::assertArrayNotHasKey('data', $decoded);
        self::assertSame(ActionError::UNAUTHENTICATED, $decoded['error']['type'] ?? null);
        self::assertSame('Token expired', $decoded['error']['description'] ?? null);
    }

    /**
     * Asserts that exceptions with empty messages use null descriptions.
     */
    public function testCatchesExceptionWithEmptyMessage(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/users/999');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new NotFoundException();
            }
        };

        $result = $action($request, $response, []);

        self::assertSame(404, $result->getStatusCode());

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $decoded);
        self::assertSame(ActionError::RESOURCE_NOT_FOUND, $decoded['error']['type'] ?? null);
        self::assertArrayNotHasKey('description', $decoded['error']);
    }

    /**
     * Asserts that exceptions with "0" as message preserve it (not treated as falsy).
     */
    public function testCatchesExceptionWithZeroMessage(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/items/0');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new NotFoundException('0');
            }
        };

        $result = $action($request, $response, []);

        self::assertSame(404, $result->getStatusCode());

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $decoded);
        self::assertSame(ActionError::RESOURCE_NOT_FOUND, $decoded['error']['type'] ?? null);
        self::assertSame('0', $decoded['error']['description'] ?? null);
    }

    /**
     * Asserts that getQueryParams returns query parameters from the request.
     */
    public function testGetQueryParamsReturnsParameters(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/search?q=test&page=2&limit=10');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $queryParams = $this->getQueryParams();

                return $this->json(
                    ActionPayload::success(['queryParams' => $queryParams])
                );
            }
        };

        $result = $action($request, $response, []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame(
            [
                'q' => 'test',
                'page' => '2',
                'limit' => '10',
            ],
            $decoded['data']['queryParams']
        );
    }

    /**
     * Asserts that getQueryParams returns an empty array when no query parameters are present.
     */
    public function testGetQueryParamsReturnsEmptyArrayWhenNoParameters(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/items');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $queryParams = $this->getQueryParams();

                return $this->json(
                    ActionPayload::success(['queryParams' => $queryParams])
                );
            }
        };

        $result = $action($request, $response, []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame([], $decoded['data']['queryParams']);
    }

    /**
     * Asserts that resolveQueryParam successfully retrieves a query parameter.
     */
    public function testResolveQueryParamReturnsParameter(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/search?q=test&category=books');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $query = $this->resolveQueryParam('q');
                $category = $this->resolveQueryParam('category');

                return $this->json(
                    ActionPayload::success([
                        'query' => $query,
                        'category' => $category,
                    ])
                );
            }
        };

        $result = $action($request, $response, []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame('test', $decoded['data']['query']);
        self::assertSame('books', $decoded['data']['category']);
    }

    /**
     * Asserts that resolveQueryParam throws when the parameter is absent.
     */
    public function testResolveQueryParamThrowsWhenMissing(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/search?q=test');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $this->resolveQueryParam('missing');

                return $this->json(ActionPayload::success([]));
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing query parameter: missing');

        $action($request, $response, []);
    }

    /**
     * Asserts that resolveQueryParam can retrieve array values from query parameters.
     */
    public function testResolveQueryParamReturnsArrayValues(): void
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', '/items?tags[]=foo&tags[]=bar&tags[]=baz');

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $tags = $this->resolveQueryParam('tags');

                return $this->json(
                    ActionPayload::success(['tags' => $tags])
                );
            }
        };

        $result = $action($request, $response, []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertIsArray($decoded['data']['tags']);
        self::assertSame(['foo', 'bar', 'baz'], $decoded['data']['tags']);
    }
}
