<?php

declare(strict_types=1);

namespace AndrewDyer\Actions;

use AndrewDyer\Actions\Concerns\RespondsWithJson;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Base class for HTTP actions following the ADR pattern.
 *
 * @api
 */
abstract class AbstractAction
{
    use RespondsWithJson;

    /**
     * Route arguments resolved from the matched URL pattern.
     */
    private array $args = [];

    /**
     * The current incoming HTTP request being processed.
     */
    private ServerRequestInterface $request;

    /**
     * The HTTP response that will be written to and returned.
     */
    private ResponseInterface $response;

    /**
     * Executes the action with the current request context.
     *
     * @param ServerRequestInterface    $request  The incoming HTTP request.
     * @param ResponseInterface         $response The HTTP response to populate and return.
     * @param array<string, string|int> $args     Named route arguments resolved by the router.
     *
     * @return ResponseInterface The response produced by the action.
     */
    final public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        return $this->handle();
    }

    /**
     * Runs the domain-specific action logic.
     *
     * @return ResponseInterface The response produced by the action.
     */
    abstract protected function handle(): ResponseInterface;

    /**
     * Returns the parsed request body as an array.
     *
     * @throws RuntimeException When the parsed body is present but is not an array.
     *
     * @return array The decoded key-value pairs from the request body.
     */
    protected function getParsedBody(): array
    {
        $data = $this->request->getParsedBody();

        if ($data === null) {
            return [];
        }

        if (is_array($data)) {
            return $data;
        }

        throw new RuntimeException('Request body must decode to an array.');
    }

    /**
     * Resolves a required route argument by name.
     *
     * @param string $name The name of the route argument to retrieve.
     *
     * @throws RuntimeException When the argument is absent from the current route.
     *
     * @return string|int The resolved argument value.
     */
    protected function resolveArg(string $name): string|int
    {
        if (!array_key_exists($name, $this->args)) {
            throw new RuntimeException("Missing route argument: {$name}");
        }

        return $this->args[$name];
    }

    /**
     * Serializes a payload to JSON and writes it to the response body.
     *
     * @deprecated Use json() instead.
     *
     * @param ActionPayloadInterface $payload The payload to serialize and send.
     *
     * @throws JsonException When the payload cannot be JSON-encoded.
     *
     * @return ResponseInterface The response with the JSON body, Content-Type header, and status applied.
     */
    protected function respondWithJson(ActionPayloadInterface $payload): ResponseInterface
    {
        return $this->json($payload);
    }
}
