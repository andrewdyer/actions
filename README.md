![Actions](https://public-assets.andrewdyer.rocks/images/covers/actions.png)

<p align="center">
  <a href="https://packagist.org/packages/andrewdyer/actions"><img src="https://poser.pugx.org/andrewdyer/actions/v/stable?style=for-the-badge" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/andrewdyer/actions"><img src="https://poser.pugx.org/andrewdyer/actions/downloads?style=for-the-badge" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/andrewdyer/actions"><img src="https://poser.pugx.org/andrewdyer/actions/license?style=for-the-badge" alt="License"></a>
  <a href="https://packagist.org/packages/andrewdyer/actions"><img src="https://poser.pugx.org/andrewdyer/actions/require/php?style=for-the-badge" alt="PHP Version Required"></a>
</p>

# Actions

A framework-agnostic PHP library for building structured and predictable JSON API endpoints with standardised request and response handling.

## ✨ Introduction

This library adheres to standard HTTP messaging principles (PSR-compliant) and provides a small set of utilities to standardise how actions handle requests and generate responses. By establishing clear patterns for success responses and error payloads, it helps keep action classes focused on domain logic while giving clients predictable, well-structured JSON responses, regardless of the framework or HTTP layer used.

## 📋 Prerequisites

- **[PHP](https://www.php.net/)**: Version 8.3 or higher is required.
- **[Composer](https://getcomposer.org/)**: Dependency management tool for PHP.

## 📥 Installation

```bash
composer require andrewdyer/actions
```

## 🚀 Getting Started

The examples below demonstrate how this library can be used with [Slim Framework 4](https://www.slimframework.com/).

> ⚠️ Slim and a PSR-7 implementation are not included as dependencies of this package and must be installed separately before running these examples.

### 1. Create an action

The action below reads a route argument and returns either a success or error JSON payload.

```php
declare(strict_types=1);

namespace App\Http\Actions;

use AndrewDyer\Actions\AbstractAction;
use AndrewDyer\Actions\Payloads\ActionError;
use AndrewDyer\Actions\Payloads\ActionPayload;
use Psr\Http\Message\ResponseInterface;

final class PingAction extends AbstractAction
{
    protected function handle(): ResponseInterface
    {
        $mode = (string) $this->resolveArg('mode');

        if ($mode !== 'ok') {
            return $this->respondWithJson(
                ActionPayload::error(
                    ActionError::badRequest('Mode must be "ok".'),
                    400
                )
            );
        }

        return $this->respondWithJson(
            ActionPayload::success([
                'message' => 'pong',
            ])
        );
    }
}
```

### 2. Register the route

The action is wired into the Slim bootstrap so requests to `/ping/{mode}` are dispatched to the action class.

```php
declare(strict_types=1);

use App\Http\Actions\PingAction;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// If using a container, ensure PingAction is resolvable there.
$app->get('/ping/{mode}', PingAction::class);

$app->run();
```

## 📚 Usage

Once the route is registered, Slim will invoke the action and return the payload as JSON.

### Successful request

```
GET /ping/ok
Accept: application/json
```

**Response: 200 OK**

```json
{
  "data": {
    "message": "pong"
  }
}
```

### Invalid request

```
GET /ping/nope
Accept: application/json
```

**Response: 400 Bad Request**

```json
{
  "error": {
    "type": "BAD_REQUEST",
    "description": "Mode must be \"ok\"."
  }
}
```

## ⚖️ License

Licensed under the [MIT license](https://opensource.org/licenses/MIT) and is free for private or commercial projects.
