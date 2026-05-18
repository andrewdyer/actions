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
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Unit tests for AbstractAction.
 */
final class AbstractActionTest extends TestCase
{
    private function makeRequest(string $method, string $uri): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest($method, $uri);
    }

    private function makeResponse(): ResponseInterface
    {
        return (new ResponseFactory())->createResponse();
    }

    /**
     * Asserts that json writes the expected headers and payload.
     */
    public function testJsonWritesPayloadAndHeaders(): void
    {
        $request = $this->makeRequest('POST', '/things/42')
            ->withParsedBody(['name' => 'Widget']);

        $action = new TestAction();
        $result = $action($request, $this->makeResponse(), ['thingId' => 42]);

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
        $request = $this->makeRequest('POST', '/things/42')
            ->withParsedBody((object)['name' => 'Widget']);

        $action = new TestAction();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request body must decode to an array.');

        $action($request, $this->makeResponse(), ['thingId' => 42]);
    }

    /**
     * Asserts that resolveArg throws when the argument is absent.
     */
    public function testResolveArgThrowsWhenMissing(): void
    {
        $action = new TestAction();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing route argument: thingId');

        $action($this->makeRequest('GET', '/missing'), $this->makeResponse(), []);
    }

    /**
     * Asserts that json propagates a JsonException when encoding fails.
     */
    public function testJsonThrowsWhenEncodingFails(): void
    {
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

        $action($this->makeRequest('GET', '/invalid'), $this->makeResponse(), []);
    }

    /**
     * Asserts that BadRequestException is caught and returns a 400 JSON response.
     */
    public function testCatchesBadRequestException(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new BadRequestException('Invalid email format');
            }
        };

        $result = $action($this->makeRequest('POST', '/invalid'), $this->makeResponse(), []);

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
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new ForbiddenException('Admin role required');
            }
        };

        $result = $action($this->makeRequest('DELETE', '/admin/users/1'), $this->makeResponse(), []);

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
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new NotFoundException('User not found');
            }
        };

        $result = $action($this->makeRequest('GET', '/users/999'), $this->makeResponse(), []);

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
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new NotImplementedException('Refunds are not yet supported');
            }
        };

        $result = $action($this->makeRequest('POST', '/payments/refund'), $this->makeResponse(), []);

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
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new UnauthenticatedException('Token expired');
            }
        };

        $result = $action($this->makeRequest('GET', '/protected'), $this->makeResponse(), []);

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
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new NotFoundException();
            }
        };

        $result = $action($this->makeRequest('GET', '/users/999'), $this->makeResponse(), []);

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
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                throw new NotFoundException('0');
            }
        };

        $result = $action($this->makeRequest('GET', '/items/0'), $this->makeResponse(), []);

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
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $queryParams = $this->getQueryParams();

                return $this->json(
                    ActionPayload::success(['queryParams' => $queryParams])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/search?q=test&page=2&limit=10'), $this->makeResponse(), []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertEquals(
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
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $queryParams = $this->getQueryParams();

                return $this->json(
                    ActionPayload::success(['queryParams' => $queryParams])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items'), $this->makeResponse(), []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame([], $decoded['data']['queryParams']);
    }

    /**
     * Asserts that resolveQueryParam successfully retrieves a required query parameter.
     */
    public function testResolveQueryParamReturnsParameter(): void
    {
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

        $result = $action($this->makeRequest('GET', '/search?q=test&category=books'), $this->makeResponse(), []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame('test', $decoded['data']['query']);
        self::assertSame('books', $decoded['data']['category']);
    }

    /**
     * Asserts that resolveQueryParam throws when the parameter is absent and no default is provided.
     */
    public function testResolveQueryParamThrowsWhenMissing(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $this->resolveQueryParam('missing');

                return $this->json(ActionPayload::success([]));
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing query parameter: missing');

        $action($this->makeRequest('GET', '/search?q=test'), $this->makeResponse(), []);
    }

    /**
     * Asserts that resolveQueryParam can retrieve array values from query parameters.
     */
    public function testResolveQueryParamReturnsArrayValues(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $tags = $this->resolveQueryParam('tags');

                return $this->json(
                    ActionPayload::success(['tags' => $tags])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items?tags[]=foo&tags[]=bar&tags[]=baz'), $this->makeResponse(), []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertIsArray($decoded['data']['tags']);
        self::assertSame(['foo', 'bar', 'baz'], $decoded['data']['tags']);
    }

    /**
     * Asserts that resolveQueryParam returns an existing optional query parameter.
     */
    public function testResolveQueryParamReturnsOptionalParameter(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $page = $this->resolveQueryParam('page', 1);
                $limit = $this->resolveQueryParam('limit', 20);

                return $this->json(
                    ActionPayload::success([
                        'page' => $page,
                        'limit' => $limit,
                    ])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items?page=2&limit=10'), $this->makeResponse(), []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame('2', $decoded['data']['page']);
        self::assertSame('10', $decoded['data']['limit']);
    }

    /**
     * Asserts that resolveQueryParam returns array values from optional query parameters.
     */
    public function testResolveQueryParamReturnsOptionalArrayValues(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $tags = $this->resolveQueryParam('tags', []);

                return $this->json(
                    ActionPayload::success(['tags' => $tags])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items?tags[]=foo&tags[]=bar'), $this->makeResponse(), []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertIsArray($decoded['data']['tags']);
        self::assertSame(['foo', 'bar'], $decoded['data']['tags']);
    }

    /**
     * Asserts that resolveQueryParam returns the provided default when parameter is missing.
     */
    public function testResolveQueryParamReturnsDefaultWhenMissing(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $page = $this->resolveQueryParam('page', 1);
                $limit = $this->resolveQueryParam('limit', 20);
                $sort = $this->resolveQueryParam('sort', 'asc');

                return $this->json(
                    ActionPayload::success([
                        'page' => $page,
                        'limit' => $limit,
                        'sort' => $sort,
                    ])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items'), $this->makeResponse(), []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame(1, $decoded['data']['page']);
        self::assertSame(20, $decoded['data']['limit']);
        self::assertSame('asc', $decoded['data']['sort']);
    }

    /**
     * Asserts that resolveQueryParam returns null when explicitly provided as default.
     */
    public function testResolveQueryParamReturnsNullAsExplicitDefault(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $filter = $this->resolveQueryParam('filter', null);

                return $this->json(
                    ActionPayload::success(['filter' => $filter])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items'), $this->makeResponse(), []);

        $body = (string)$result->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertNull($decoded['data']['filter']);
    }
}
