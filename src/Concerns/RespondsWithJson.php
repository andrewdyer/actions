<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Concerns;

use AndrewDyer\Actions\Contracts\ActionPayloadInterface;
use AndrewDyer\Actions\Payloads\ActionError;
use AndrewDyer\Actions\Payloads\ActionPayload;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides JSON response helpers for action classes.
 *
 * This trait requires the consuming class to expose a PSR-7 ResponseInterface
 * instance via the $response property, as AbstractAction does.
 *
 * @api
 */
trait RespondsWithJson
{
    /**
     * Serializes a payload to JSON and writes it to the response body.
     *
     * @param ActionPayloadInterface $payload The payload to serialize and send.
     *
     * @throws JsonException When the payload cannot be JSON-encoded.
     *
     * @return ResponseInterface The response with the JSON body, Content-Type header, and status applied.
     */
    protected function json(ActionPayloadInterface $payload): ResponseInterface
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json !== false) {
            $this->response->getBody()->write($json);
        }

        return $this->response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($payload->getStatusCode());
    }

    /**
     * Responds with a 200 OK JSON payload containing the given data.
     *
     * @param mixed $data       The response data to include in the payload.
     * @param int   $statusCode The HTTP status code, defaults to 200.
     *
     * @throws JsonException When the payload cannot be JSON-encoded.
     *
     * @return ResponseInterface The JSON response.
     */
    protected function ok(mixed $data, int $statusCode = 200): ResponseInterface
    {
        return $this->json(ActionPayload::success($data, $statusCode));
    }

    /**
     * Responds with a 400 Bad Request JSON error payload.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @throws JsonException When the payload cannot be JSON-encoded.
     *
     * @return ResponseInterface The JSON response.
     */
    protected function badRequest(?string $description = null): ResponseInterface
    {
        return $this->json(ActionPayload::error(ActionError::badRequest($description), 400));
    }

    /**
     * Responds with a 401 Unauthorized JSON error payload.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @throws JsonException When the payload cannot be JSON-encoded.
     *
     * @return ResponseInterface The JSON response.
     */
    protected function unauthorized(?string $description = null): ResponseInterface
    {
        return $this->json(ActionPayload::error(ActionError::unauthenticated($description), 401));
    }

    /**
     * Responds with a 403 Forbidden JSON error payload.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @throws JsonException When the payload cannot be JSON-encoded.
     *
     * @return ResponseInterface The JSON response.
     */
    protected function forbidden(?string $description = null): ResponseInterface
    {
        return $this->json(ActionPayload::error(ActionError::insufficientPrivileges($description), 403));
    }

    /**
     * Responds with a 405 Method Not Allowed JSON error payload.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @throws JsonException When the payload cannot be JSON-encoded.
     *
     * @return ResponseInterface The JSON response.
     */
    protected function notAllowed(?string $description = null): ResponseInterface
    {
        return $this->json(ActionPayload::error(ActionError::notAllowed($description), 405));
    }

    /**
     * Responds with a 404 Not Found JSON error payload.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @throws JsonException When the payload cannot be JSON-encoded.
     *
     * @return ResponseInterface The JSON response.
     */
    protected function notFound(?string $description = null): ResponseInterface
    {
        return $this->json(ActionPayload::error(ActionError::notFound($description), 404));
    }

    /**
     * Responds with a 501 Not Implemented JSON error payload.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @throws JsonException When the payload cannot be JSON-encoded.
     *
     * @return ResponseInterface The JSON response.
     */
    protected function notImplemented(?string $description = null): ResponseInterface
    {
        return $this->json(ActionPayload::error(ActionError::notImplemented($description), 501));
    }

    /**
     * Responds with a 500 Internal Server Error JSON error payload.
     *
     * @param string|null $description An optional human-readable description of the error.
     *
     * @throws JsonException When the payload cannot be JSON-encoded.
     *
     * @return ResponseInterface The JSON response.
     */
    protected function serverError(?string $description = null): ResponseInterface
    {
        return $this->json(ActionPayload::error(ActionError::serverError($description), 500));
    }
}
