<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\ApiKernel\Payloads\ActionPayloadInterface;
use Slim\Exception\HttpBadRequestException;

abstract class AbstractAction
{
    private array $args = [];
    private ServerRequestInterface $request;
    private ResponseInterface $response;

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

    abstract protected function handle(): ResponseInterface;

    protected function getParsedBody(): array
    {
        $data = $this->request->getParsedBody() ?? [];

        return is_array($data) ? $data : (array)$data;
    }

    protected function resolveArg(string $name): string|int
    {
        if (!array_key_exists($name, $this->args)) {
            throw new HttpBadRequestException(
                $this->request,
                "Missing route argument: {$name}"
            );
        }

        return $this->args[$name];
    }

    protected function respondWithJson(ActionPayloadInterface $payload): ResponseInterface
    {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json !== false) {
            $this->response->getBody()->write($json);
        }

        return $this->response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($payload->getStatusCode());
    }
}
