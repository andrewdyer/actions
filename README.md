![Actions](https://public-assets.andrewdyer.rocks/images/covers/actions.png)

<p align="center">
  <a href="https://packagist.org/packages/andrewdyer/actions"><img src="https://poser.pugx.org/andrewdyer/actions/v/stable?style=for-the-badge" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/andrewdyer/actions"><img src="https://poser.pugx.org/andrewdyer/actions/downloads?style=for-the-badge" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/andrewdyer/actions"><img src="https://poser.pugx.org/andrewdyer/actions/license?style=for-the-badge" alt="License"></a>
  <a href="https://packagist.org/packages/andrewdyer/actions"><img src="https://poser.pugx.org/andrewdyer/actions/require/php?style=for-the-badge" alt="PHP Version Required"></a>
</p>

# Actions

A framework-agnostic PHP library for building structured and predictable JSON API endpoints with standardised request and response handling.

## Introduction

This library adheres to standard HTTP messaging principles (PSR-compliant) and provides a small set of utilities to standardise how actions handle requests and generate responses. By establishing clear patterns for success responses and error payloads, it helps keep action classes focused on domain logic while giving clients predictable, well-structured JSON responses, regardless of the framework or HTTP layer used.

## Prerequisites

- **[PHP](https://www.php.net/)**: Version 8.3 or higher is required.
- **[Composer](https://getcomposer.org/)**: Dependency management tool for PHP.

## Installation

```bash
composer require andrewdyer/actions
```

## Getting Started

The examples below demonstrate how this library can be used with [Slim Framework 4](https://www.slimframework.com/).

> ⚠️ Slim and a PSR-7 implementation are not included as dependencies of this package and must be installed separately before running these examples.

### 1. Create an action

The action validates the request, retrieves data, and returns a JSON response.

```php
declare(strict_types=1);

namespace App\Http\Actions;

use AndrewDyer\Actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;

final class GetUserAction extends AbstractAction
{
    protected function handle(): ResponseInterface
    {
        $id = (string) $this->resolveArg('id');

        if (!ctype_digit($id)) {
            return $this->badRequest('User ID must be numeric.');
        }

        if ((int) $id !== 123) {
            return $this->notFound("User {$id} not found.");
        }

        return $this->ok([
            'id' => (int) $id,
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
        ]);
    }
}
```

### 2. Register the route

The action is wired into the Slim bootstrap so requests to `/users/{id}` are dispatched to the action class.

```php
declare(strict_types=1);

use App\Http\Actions\GetUserAction;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// If using a container, ensure GetUserAction is resolvable there.
$app->get('/users/{id}', GetUserAction::class);

$app->run();
```

## Usage

Once the route is registered, Slim invokes the action and returns the payload as JSON.

### Successful request

```
GET /users/123
Accept: application/json
```

**Response: 200 OK**

```json
{
  "data": {
    "id": 123,
    "name": "John Smith",
    "email": "john.smith@example.com"
  }
}
```

### Validation error

```
GET /users/abc
Accept: application/json
```

**Response: 400 Bad Request**

```json
{
  "error": {
    "type": "BAD_REQUEST",
    "description": "User ID must be numeric."
  }
}
```

### Resource not found

```
GET /users/999
Accept: application/json
```

**Response: 404 Not Found**

```json
{
  "error": {
    "type": "RESOURCE_NOT_FOUND",
    "description": "User 999 not found."
  }
}
```

## Response helpers

Helper methods provided by `AbstractAction` generate structured JSON responses. Each builds the appropriate `ActionPayload` and writes it to the response.

| Method                                        | When to use                                      | Status          | Error type                |
| --------------------------------------------- | ------------------------------------------------ | --------------- | ------------------------- |
| `ok(mixed $data, int $statusCode = 200)`      | Successful operation with data payload           | 200 (or custom) | —                         |
| `badRequest(?string $description = null)`     | Request is malformed or violates business rules  | 400             | `BAD_REQUEST`             |
| `unauthorized(?string $description = null)`   | Request lacks valid authentication credentials   | 401             | `UNAUTHENTICATED`         |
| `forbidden(?string $description = null)`      | Authenticated caller lacks required permissions  | 403             | `INSUFFICIENT_PRIVILEGES` |
| `notFound(?string $description = null)`       | Requested resource does not exist                | 404             | `RESOURCE_NOT_FOUND`      |
| `notAllowed(?string $description = null)`     | HTTP method not allowed for the resource         | 405             | `NOT_ALLOWED`             |
| `serverError(?string $description = null)`    | Unexpected server error occurred                 | 500             | `SERVER_ERROR`            |
| `notImplemented(?string $description = null)` | Requested functionality has not been implemented | 501             | `NOT_IMPLEMENTED`         |

For full control over the payload, call `json(ActionPayloadInterface $payload)` directly.

## Exception handling

Domain exceptions are caught automatically by `AbstractAction` and mapped to appropriate HTTP responses. This is useful when exceptions are thrown from services, repositories, or other domain logic that should not be coupled to HTTP concerns. Extend the base exception classes to create domain-specific exceptions.

| Base exception             | When to use                                      | Status | Error type                |
| -------------------------- | ------------------------------------------------ | ------ | ------------------------- |
| `BadRequestException`      | Request is malformed or violates business rules  | 400    | `BAD_REQUEST`             |
| `UnauthenticatedException` | Request lacks valid authentication credentials   | 401    | `UNAUTHENTICATED`         |
| `ForbiddenException`       | Authenticated caller lacks required permissions  | 403    | `INSUFFICIENT_PRIVILEGES` |
| `NotFoundException`        | Requested resource does not exist                | 404    | `RESOURCE_NOT_FOUND`      |
| `NotImplementedException`  | Requested functionality has not been implemented | 501    | `NOT_IMPLEMENTED`         |

Example:

```php
declare(strict_types=1);

namespace App\Domain\Exceptions;

use AndrewDyer\Actions\Exceptions\NotFoundException;

final class UserNotFoundException extends NotFoundException
{
    public function __construct(int $id)
    {
        parent::__construct("User {$id} not found.");
    }
}
```

## License

Licensed under the [MIT license](https://opensource.org/licenses/MIT) and is free for private or commercial projects.
