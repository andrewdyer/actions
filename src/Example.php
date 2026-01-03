<?php

declare(strict_types=1);

namespace Slim\ApiKernel;

final class Example
{
    public function sayHello(string $name): string
    {
        return "Hello, $name!";
    }
}
