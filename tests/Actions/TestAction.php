<?php

declare(strict_types=1);

namespace Anddye\Actions\Tests\Actions;

use Anddye\Actions\Actions\AbstractAction;
use Anddye\Actions\Payloads\ActionPayload;
use Psr\Http\Message\ResponseInterface;

/**
 * Test double exercising the AbstractAction response helper.
 *
 * Role: Test Action. Supplies deterministic data for assertions.
 *
 * @internal
 */
final class TestAction extends AbstractAction
{
    /**
     * Builds a canned JSON payload using the request context.
     *
     * @return ResponseInterface
     */
    protected function handle(): ResponseInterface
    {
        return $this->respondWithJson(
            ActionPayload::success([
                'message' => 'ok',
                'body' => $this->getParsedBody(),
                'thingId' => $this->resolveArg('thingId'),
            ])
        );
    }
}
