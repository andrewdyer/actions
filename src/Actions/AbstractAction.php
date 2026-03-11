<?php

declare(strict_types=1);

namespace Anddye\Actions\Actions;

use Anddye\Actions\Payloads\ActionPayloadInterface;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Base class for HTTP actions following the ADR pattern.
 *
 * Role: Action. It centralizes argument resolution and JSON payload handling.
 *
 * @api
 */
abstract class AbstractAction
{
    private array $args = [];
    private ServerRequestInterface $request;
    private ResponseInterface $response;

    /**
     * Executes the action with the current request context.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, string|int> $args
     *
     * @return ResponseInterface
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
     * @return ResponseInterface
     */
    abstract protected function handle(): ResponseInterface;

    /**
     * Returns the parsed request body as an array.
     *
     * @throws RuntimeException
     *
     * @return array
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
     * Resolves a required route argument.
     *
     * @param string $name
     *
     * @throws RuntimeException
     *
     * @return string|int
     */
    protected function resolveArg(string $name): string|int
    {
        if (!array_key_exists($name, $this->args)) {
            throw new RuntimeException("Missing route argument: {$name}");
        }

        return $this->args[$name];
    }

    /**
     * Serializes a payload to JSON and applies it to the response.
     *
     * @param ActionPayloadInterface $payload
     *
     * @throws RuntimeException|JsonException
     *
     * @return ResponseInterface
     */
    protected function respondWithJson(ActionPayloadInterface $payload): ResponseInterface
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json !== false) {
            $this->response->getBody()->write($json);
        }

        return $this->response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($payload->getStatusCode());
    }
}
