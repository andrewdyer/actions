<?php

declare(strict_types=1);

namespace Slim\ApiKernel\Tests;

use PHPUnit\Framework\TestCase;
use Slim\ApiKernel\Example;

class ExampleTest extends TestCase
{
    public function testSayHello(): void
    {
        $pkg = new Example();
        $this->assertSame('Hello, John!', $pkg->sayHello('John'));
    }
}
