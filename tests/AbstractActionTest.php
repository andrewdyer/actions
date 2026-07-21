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

        self::assertSame(200, $result->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $result->getHeaderLine('Content-Type'));

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
                return $this->json(
                    ActionPayload::success(['payload' => "\xB1"])
                );
            }
        };

        $this->expectException(JsonException::class);

        $action($this->makeRequest('GET', '/invalid'), $this->makeResponse(), []);
    }

    /**
     * Asserts that getParsedBody throws when the body is not an array.
     */
    public function testGetParsedBodyThrowsWhenBodyIsNotArray(): void
    {
        $request = $this->makeRequest('POST', '/things/42')
            ->withParsedBody((object)['name' => 'Widget']);

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $this->getParsedBody();

                return $this->json(ActionPayload::success([]));
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request body must decode to an array.');

        $action($request, $this->makeResponse(), []);
    }

    /**
     * Asserts that resolveBodyParam successfully retrieves a present required parameter.
     */
    public function testResolveBodyParamReturnsParameter(): void
    {
        $request = $this->makeRequest('POST', '/products')
            ->withParsedBody(['name' => 'Laptop', 'price' => 999.99]);

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(
                    ActionPayload::success([
                        'name' => $this->resolveBodyParam('name'),
                        'price' => $this->resolveBodyParam('price'),
                    ])
                );
            }
        };

        $result = $action($request, $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame('Laptop', $decoded['data']['name']);
        self::assertSame(999.99, $decoded['data']['price']);
    }

    /**
     * Asserts that resolveBodyParam throws when the parameter is absent and no default is provided.
     */
    public function testResolveBodyParamThrowsWhenMissing(): void
    {
        $request = $this->makeRequest('POST', '/products')
            ->withParsedBody(['name' => 'Laptop']);

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $this->resolveBodyParam('price');

                return $this->json(ActionPayload::success([]));
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing body parameter: price');

        $action($request, $this->makeResponse(), []);
    }

    /**
     * Asserts that resolveBodyParam returns an existing optional parameter (not the default).
     */
    public function testResolveBodyParamReturnsOptionalParameter(): void
    {
        $request = $this->makeRequest('POST', '/products')
            ->withParsedBody(['name' => 'Laptop', 'description' => 'A powerful laptop']);

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(
                    ActionPayload::success([
                        'name' => $this->resolveBodyParam('name'),
                        'description' => $this->resolveBodyParam('description', ''),
                    ])
                );
            }
        };

        $result = $action($request, $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame('Laptop', $decoded['data']['name']);
        self::assertSame('A powerful laptop', $decoded['data']['description']);
    }

    /**
     * Asserts that resolveBodyParam returns the provided default when the parameter is missing.
     */
    public function testResolveBodyParamReturnsDefaultWhenMissing(): void
    {
        $request = $this->makeRequest('POST', '/products')
            ->withParsedBody(['name' => 'Laptop']);

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(
                    ActionPayload::success([
                        'name' => $this->resolveBodyParam('name'),
                        'description' => $this->resolveBodyParam('description', 'No description provided'),
                        'inStock' => $this->resolveBodyParam('inStock', true),
                    ])
                );
            }
        };

        $result = $action($request, $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame('Laptop', $decoded['data']['name']);
        self::assertSame('No description provided', $decoded['data']['description']);
        self::assertTrue($decoded['data']['inStock']);
    }

    /**
     * Asserts that resolveBodyParam returns null when explicitly provided as a default.
     */
    public function testResolveBodyParamReturnsNullAsExplicitDefault(): void
    {
        $request = $this->makeRequest('POST', '/products')
            ->withParsedBody(['name' => 'Laptop']);

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(
                    ActionPayload::success(['discount' => $this->resolveBodyParam('discount', null)])
                );
            }
        };

        $result = $action($request, $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertNull($decoded['data']['discount']);
    }

    /**
     * Asserts that getArgs returns route arguments from the matched URL pattern.
     */
    public function testGetArgsReturnsArguments(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(
                    ActionPayload::success(['args' => $this->getArgs()])
                );
            }
        };

        $result = $action(
            $this->makeRequest('GET', '/orders/123/items/456'),
            $this->makeResponse(),
            ['orderId' => '123', 'itemId' => '456']
        );

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame(['orderId' => '123', 'itemId' => '456'], $decoded['data']['args']);
    }

    /**
     * Asserts that getArgs returns an empty array when no route arguments are present.
     */
    public function testGetArgsReturnsEmptyArrayWhenNoArguments(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(
                    ActionPayload::success(['args' => $this->getArgs()])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items'), $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame([], $decoded['data']['args']);
    }

    /**
     * Asserts that resolveArg throws when the argument is absent.
     */
    public function testResolveArgThrowsWhenMissing(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $this->resolveArg('thingId');

                return $this->json(ActionPayload::success([]));
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing route argument: thingId');

        $action($this->makeRequest('GET', '/missing'), $this->makeResponse(), []);
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

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

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

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

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

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

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

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

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

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

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

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

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

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

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
                return $this->json(
                    ActionPayload::success(['queryParams' => $this->getQueryParams()])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/search?q=test&page=2&limit=10'), $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame(
            ['q' => 'test', 'page' => '2', 'limit' => '10'],
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
                return $this->json(
                    ActionPayload::success(['queryParams' => $this->getQueryParams()])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items'), $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame([], $decoded['data']['queryParams']);
    }

    /**
     * Asserts that resolveQueryParam successfully retrieves a present required parameter.
     */
    public function testResolveQueryParamReturnsParameter(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(
                    ActionPayload::success([
                        'query' => $this->resolveQueryParam('q'),
                        'category' => $this->resolveQueryParam('category'),
                    ])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/search?q=test&category=books'), $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

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
                return $this->json(
                    ActionPayload::success(['tags' => $this->resolveQueryParam('tags')])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items?tags[]=foo&tags[]=bar&tags[]=baz'), $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame(['foo', 'bar', 'baz'], $decoded['data']['tags']);
    }

    /**
     * Asserts that resolveQueryParam returns an existing optional query parameter (not the default).
     */
    public function testResolveQueryParamReturnsOptionalParameter(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(
                    ActionPayload::success([
                        'page' => $this->resolveQueryParam('page', 1),
                        'limit' => $this->resolveQueryParam('limit', 20),
                    ])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items?page=2&limit=10'), $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

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
                return $this->json(
                    ActionPayload::success(['tags' => $this->resolveQueryParam('tags', [])])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items?tags[]=foo&tags[]=bar'), $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame(['foo', 'bar'], $decoded['data']['tags']);
    }

    /**
     * Asserts that resolveQueryParam returns the provided default when the parameter is missing.
     */
    public function testResolveQueryParamReturnsDefaultWhenMissing(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(
                    ActionPayload::success([
                        'page' => $this->resolveQueryParam('page', 1),
                        'limit' => $this->resolveQueryParam('limit', 20),
                        'sort' => $this->resolveQueryParam('sort', 'asc'),
                    ])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items'), $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertSame(1, $decoded['data']['page']);
        self::assertSame(20, $decoded['data']['limit']);
        self::assertSame('asc', $decoded['data']['sort']);
    }

    /**
     * Asserts that resolveQueryParam returns null when explicitly provided as a default.
     *
     * This validates that null is treated as a real default value and not as
     * an omitted argument, which would otherwise trigger the required-parameter exception.
     */
    public function testResolveQueryParamReturnsNullAsExplicitDefault(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(
                    ActionPayload::success(['filter' => $this->resolveQueryParam('filter', null)])
                );
            }
        };

        $result = $action($this->makeRequest('GET', '/items'), $this->makeResponse(), []);

        self::assertSame(200, $result->getStatusCode());

        $decoded = json_decode((string)$result->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('data', $decoded);
        self::assertNull($decoded['data']['filter']);
    }

    /**
     * Asserts that resolveQueryParam throws when the parameter is absent and null is not
     * explicitly passed — confirming that omission and explicit null are distinct.
     */
    public function testResolveQueryParamThrowsWhenMissingAndNullNotExplicitlyPassed(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                // No second argument — must throw even though the PHP default is null
                $this->resolveQueryParam('filter');

                return $this->json(ActionPayload::success([]));
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing query parameter: filter');

        $action($this->makeRequest('GET', '/items'), $this->makeResponse(), []);
    }

    /**
     * Asserts that getAttribute returns an existing request attribute.
     */
    public function testGetAttributeReturnsAttribute(): void
    {
        $request = $this->makeRequest('GET', '/protected')
            ->withAttribute('user', 'authenticated-user');

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(ActionPayload::success([
                    'user' => $this->getAttribute('user'),
                ]));
            }
        };

        $result = $action($request, $this->makeResponse(), []);
        $decoded = json_decode(
            (string)$result->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('authenticated-user', $decoded['data']['user']);
    }

    /**
     * Asserts that getAttribute returns the supplied default when the attribute is absent.
     */
    public function testGetAttributeReturnsDefaultWhenMissing(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(ActionPayload::success([
                    'locale' => $this->getAttribute('locale', 'en'),
                ]));
            }
        };

        $result = $action(
            $this->makeRequest('GET', '/'),
            $this->makeResponse(),
            []
        );

        $decoded = json_decode(
            (string)$result->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('en', $decoded['data']['locale']);
    }

    /**
     * Asserts that resolveAttribute returns an existing required request attribute.
     */
    public function testResolveAttributeReturnsAttribute(): void
    {
        $request = $this->makeRequest('GET', '/protected')
            ->withAttribute('user', 'authenticated-user');

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(ActionPayload::success([
                    'user' => $this->resolveAttribute('user'),
                ]));
            }
        };

        $result = $action($request, $this->makeResponse(), []);
        $decoded = json_decode(
            (string)$result->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('authenticated-user', $decoded['data']['user']);
    }

    /**
     * Asserts that resolveAttribute distinguishes an existing null value from a missing attribute.
     */
    public function testResolveAttributeReturnsExistingNullAttribute(): void
    {
        $request = $this->makeRequest('GET', '/')
            ->withAttribute('tenant', null);

        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->json(ActionPayload::success([
                    'tenant' => $this->resolveAttribute('tenant'),
                ]));
            }
        };

        $result = $action($request, $this->makeResponse(), []);

        $decoded = json_decode(
            (string)$result->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertArrayHasKey('tenant', $decoded['data']);
        self::assertNull($decoded['data']['tenant']);
    }

    /**
     * Asserts that resolveAttribute throws when the required request attribute is absent.
     */
    public function testResolveAttributeThrowsWhenMissing(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                $this->resolveAttribute('user');

                return $this->json(ActionPayload::success([]));
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing request attribute: user');

        $action(
            $this->makeRequest('GET', '/protected'),
            $this->makeResponse(),
            []
        );
    }
}
