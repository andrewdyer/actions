<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Tests\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\ApiKernel\Actions\AbstractAction;
use Slim\ApiKernel\Payloads\ActionPayload;

final class TestAction extends AbstractAction
{
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
