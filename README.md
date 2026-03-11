# Slim API Kernel

Framework-agnostic tools for building structured and predictable JSON APIs with an Action–Domain–Responder style approach.

## ✨ Introduction

Slim API Kernel provides a structured and predictable approach to building JSON APIs using the Action–Domain–Responder pattern. The library includes a small set of utilities that standardise how actions handle requests and responses, ensuring consistency across API endpoints.

## 💡 Rationale

Well-structured APIs benefit from consistent response formats and clear separation of concerns. By establishing clear patterns for success responses and error payloads, Slim API Kernel keeps action classes focused on domain logic while providing clients with predictable, well-structured JSON responses.

## 📥 Installation

```bash
composer require andrewdyer/slim-api-kernel
```

## 🚀 Getting Started

Below is a minimal [Slim Framework 4](https://www.slimframework.com/) setup that exposes a simple endpoint using this package's action base class and payload helpers.

### 1. Create an action

Define an action that reads a route argument and returns either a success or error JSON payload.

```php
declare(strict_types=1);

namespace App\Application\Http\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\ApiKernel\Actions\AbstractAction;
use Slim\ApiKernel\Payloads\ActionError;
use Slim\ApiKernel\Payloads\ActionPayload;

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

Wire the action into the Slim bootstrap so requests to `/ping/{mode}` are dispatched to the action class.

```php
declare(strict_types=1);

use App\Application\Http\Actions\PingAction;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// If you're using a container, ensure PingAction is resolvable there.
$app->get('/ping/{mode}', PingAction::class);

$app->run();
```

## 📚 Usage

Once the route is registered, Slim will invoke your action and return the payload as JSON.

```
GET /ping/ok
Accept: application/json
```

**200 OK**

```json
{
  "data": {
    "message": "pong"
  }
}
```

```
GET /ping/nope
Accept: application/json
```

**400 Bad Request**

```json
{
  "error": {
    "type": "BAD_REQUEST",
    "description": "Mode must be \"ok\"."
  }
}
```