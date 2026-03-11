# Slim API Kernel

PHP Slim helpers for abstract actions, consistent JSON responses, and structured API error payloads.

## ✨ Introduction

Slim API Kernel provides a straightforward way to build Action-Domain-Responder style HTTP endpoints in Slim 4 applications. The library offers an easy-to-use abstract action base for request argument and body handling, includes reusable payload objects for consistent JSON success and error responses, and supports clear, structured API error modeling. Additionally, it helps keep action classes focused and response formatting predictable across your application.

## Installation

```bash
composer require andrewdyer/slim-api-kernel
```

## 🚀 Getting Started

Below is a minimal Slim 4 setup that exposes a simple endpoint using this package's action base class and payload helpers.

### 1) Create an action
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

### 2) Register the route
Wire the action into your Slim 4 bootstrap so requests to `/ping/{mode}` are dispatched to the action class.

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

## Usage

### 1) Call the endpoint
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