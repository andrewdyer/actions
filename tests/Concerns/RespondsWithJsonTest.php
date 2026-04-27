<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Tests\Concerns;

use AndrewDyer\Actions\AbstractAction;
use AndrewDyer\Actions\Payloads\ActionError;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Unit tests for RespondsWithJson.
 */
final class RespondsWithJsonTest extends TestCase
{
    /**
     * Factory for creating server requests used in tests.
     */
    private ServerRequestFactory $requestFactory;

    /**
     * Factory for creating HTTP responses used in tests.
     */
    private ResponseFactory $responseFactory;

    /**
     * Creates the request and response factories before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    /**
     * Returns the response produced by invoking the action with a blank GET request.
     *
     * @param AbstractAction $action The action to dispatch.
     *
     * @return ResponseInterface The response returned by the action.
     */
    private function dispatch(AbstractAction $action): ResponseInterface
    {
        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $this->responseFactory->createResponse();

        return $action($request, $response, []);
    }

    /**
     * Returns the response body decoded as an array.
     *
     * @param ResponseInterface $response The response to decode.
     *
     * @return array The decoded JSON body.
     */
    private function decodeBody(ResponseInterface $response): array
    {
        $decoded = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            self::fail('Expected response body JSON to decode to an array.');
        }

        return $decoded;
    }

    /**
     * Asserts that ok method returns 200 with a data payload.
     */
    public function testOkReturns200(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->ok(['message' => 'pong']);
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['data' => ['message' => 'pong']], $this->decodeBody($response));
    }

    /**
     * Asserts that ok method honours a custom status code.
     */
    public function testOkHonoursCustomStatusCode(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->ok(['id' => 1], 201);
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(201, $response->getStatusCode());
    }

    /**
     * Asserts that badRequest method returns 400 with a BAD_REQUEST error type.
     */
    public function testBadRequestReturns400(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->badRequest('Invalid input.');
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(400, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertSame(ActionError::BAD_REQUEST, $body['error']['type']);
        self::assertSame('Invalid input.', $body['error']['description']);
    }

    /**
     * Asserts that badRequest method accepts a null description.
     */
    public function testBadRequestAcceptsNullDescription(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->badRequest();
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(400, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertArrayNotHasKey('description', $body['error']);
    }

    /**
     * Asserts that unauthorized method returns 401 with an UNAUTHENTICATED error type.
     */
    public function testUnauthorizedReturns401(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->unauthorized('Token expired.');
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(401, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertSame(ActionError::UNAUTHENTICATED, $body['error']['type']);
    }

    /**
     * Asserts that unauthorized method accepts a null description.
     */
    public function testUnauthorizedAcceptsNullDescription(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->unauthorized();
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(401, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertArrayNotHasKey('description', $body['error']);
    }

    /**
     * Asserts that forbidden method returns 403 with an INSUFFICIENT_PRIVILEGES error type.
     */
    public function testForbiddenReturns403(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->forbidden('Access denied.');
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(403, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertSame(ActionError::INSUFFICIENT_PRIVILEGES, $body['error']['type']);
    }

    /**
     * Asserts that forbidden method accepts a null description.
     */
    public function testForbiddenAcceptsNullDescription(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->forbidden();
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(403, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertArrayNotHasKey('description', $body['error']);
    }

    /**
     * Asserts that notFound method returns 404 with a RESOURCE_NOT_FOUND error type.
     */
    public function testNotFoundReturns404(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->notFound('Item not found.');
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(404, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertSame(ActionError::RESOURCE_NOT_FOUND, $body['error']['type']);
    }

    /**
     * Asserts that notFound method accepts a null description.
     */
    public function testNotFoundAcceptsNullDescription(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->notFound();
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(404, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertArrayNotHasKey('description', $body['error']);
    }

    /**
     * Asserts that notAllowed method returns 405 with a NOT_ALLOWED error type.
     */
    public function testNotAllowedReturns405(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->notAllowed('Method not allowed.');
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(405, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertSame(ActionError::NOT_ALLOWED, $body['error']['type']);
    }

    /**
     * Asserts that notAllowed method accepts a null description.
     */
    public function testNotAllowedAcceptsNullDescription(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->notAllowed();
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(405, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertArrayNotHasKey('description', $body['error']);
    }

    /**
     * Asserts that notImplemented method returns 501 with a NOT_IMPLEMENTED error type.
     */
    public function testNotImplementedReturns501(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->notImplemented('Coming soon.');
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(501, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertSame(ActionError::NOT_IMPLEMENTED, $body['error']['type']);
    }

    /**
     * Asserts that notImplemented method accepts a null description.
     */
    public function testNotImplementedAcceptsNullDescription(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->notImplemented();
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(501, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertArrayNotHasKey('description', $body['error']);
    }

    /**
     * Asserts that serverError method returns 500 with a SERVER_ERROR error type.
     */
    public function testServerErrorReturns500(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->serverError('Unexpected failure.');
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(500, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertSame(ActionError::SERVER_ERROR, $body['error']['type']);
    }

    /**
     * Asserts that serverError method accepts a null description.
     */
    public function testServerErrorAcceptsNullDescription(): void
    {
        $action = new class () extends AbstractAction {
            protected function handle(): ResponseInterface
            {
                return $this->serverError();
            }
        };

        $response = $this->dispatch($action);

        self::assertSame(500, $response->getStatusCode());

        $body = $this->decodeBody($response);
        self::assertArrayNotHasKey('description', $body['error']);
    }
}
