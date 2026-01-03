<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Payloads;

use JsonSerializable;

interface ActionPayloadInterface extends JsonSerializable
{
    public function getStatusCode(): int;
}
