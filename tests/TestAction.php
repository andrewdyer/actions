<?php

declare(strict_types=1);

namespace AndrewDyer\Actions\Tests;

use AndrewDyer\Actions\AbstractAction;
use AndrewDyer\Actions\Payloads\ActionPayload;
use Psr\Http\Message\ResponseInterface;

/**
 * Test double exercising the AbstractAction response helper.
 *
 * @internal
 */
final class TestAction extends AbstractAction
{
    /**
     * Builds a canned JSON payload using the request context.
     *
     * @return ResponseInterface The JSON response.
     */
    protected function handle(): ResponseInterface
    {
        return $this->json(
            ActionPayload::success([
                'message' => 'ok',
                'body' => $this->getParsedBody(),
                'thingId' => $this->resolveArg('thingId'),
            ])
        );
    }
}
