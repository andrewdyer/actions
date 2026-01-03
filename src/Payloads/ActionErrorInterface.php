<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Payloads;

use JsonSerializable;

interface ActionErrorInterface extends JsonSerializable
{
    public function getType(): string;

    public function getDescription(): ?string;
}
