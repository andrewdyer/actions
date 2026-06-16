# Actions

A framework-agnostic library for building structured and predictable JSON responses with standardised response handling.

[![Latest Stable Version](http://poser.pugx.org/andrewdyer/actions/v?style=flat-square)](https://packagist.org/packages/andrewdyer/actions)
[![Total Downloads](http://poser.pugx.org/andrewdyer/actions/downloads?style=flat-square)](https://packagist.org/packages/andrewdyer/actions)
[![License](http://poser.pugx.org/andrewdyer/actions/license?style=flat-square)](https://packagist.org/packages/andrewdyer/actions)
[![PHP Version Require](http://poser.pugx.org/andrewdyer/actions/require/php?style=flat-square)](https://packagist.org/packages/andrewdyer/actions)

## Introduction

This library offers a small set of utilities that standardise how actions handle requests and generate structured JSON responses, establishing clear patterns for success responses and error payloads. By keeping action classes focused on domain logic and giving clients well-structured, predictable JSON responses, it simplifies API development regardless of the framework or HTTP layer in use.

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

| Method                                                       | When to use                                      | Status          | Error type                |
| ------------------------------------------------------------ | ------------------------------------------------ | --------------- | ------------------------- |
| `ok(mixed $data, mixed $meta = null, int $statusCode = 200)` | Successful operation with data payload           | 200 (or custom) | —                         |
| `badRequest(?string $description = null)`                    | Request is malformed or violates business rules  | 400             | `BAD_REQUEST`             |
| `unauthorized(?string $description = null)`                  | Request lacks valid authentication credentials   | 401             | `UNAUTHENTICATED`         |
| `forbidden(?string $description = null)`                     | Authenticated caller lacks required permissions  | 403             | `INSUFFICIENT_PRIVILEGES` |
| `notFound(?string $description = null)`                      | Requested resource does not exist                | 404             | `RESOURCE_NOT_FOUND`      |
| `notAllowed(?string $description = null)`                    | HTTP method not allowed for the resource         | 405             | `NOT_ALLOWED`             |
| `serverError(?string $description = null)`                   | Unexpected server error occurred                 | 500             | `SERVER_ERROR`            |
| `notImplemented(?string $description = null)`                | Requested functionality has not been implemented | 501             | `NOT_IMPLEMENTED`         |

> **Note:** When the `description` parameter is `null`, the `description` field is omitted entirely from the error response. API consumers should treat `description` as an optional field.

For full control over the payload, call `json(ActionPayloadInterface $payload)` directly.

## Request helpers

Helper methods are available for accessing request data in a consistent and predictable manner.

### Route arguments

Route arguments are extracted from the URL pattern matched by your router (e.g., `/users/{id}`).

- **`getArgs(): array`** — Returns all route arguments as an associative array
- **`resolveArg(string $name): string|int`** — Retrieves a route argument by name. Throws a RuntimeException if the argument is missing.

#### Example: Fetching a resource by ID

```php
declare(strict_types=1);

namespace App\Http\Actions;

use AndrewDyer\Actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;

final class GetOrderAction extends AbstractAction
{
    protected function handle(): ResponseInterface
    {
        // Extract the order ID from the route
        $orderId = (int) $this->resolveArg('id');

        // Your domain logic here...
        $order = $this->fetchOrder($orderId);

        return $this->ok($order);
    }
}
```

**Request:**

```
GET /orders/12345
Accept: application/json
```

### Request body

The request body can be accessed as an associative array using `getParsedBody()`.

- **`getParsedBody(): array`** — Returns the parsed request body as an array. Returns an empty array if no body is present. Throws a RuntimeException if the body cannot be parsed as an array.
- **`resolveBodyParam(string $name, mixed $default = null): mixed`** — Retrieves a body parameter by name. If a default value is provided, the parameter is optional and the default is returned when missing. If no default value is provided, the parameter is required and a RuntimeException is thrown when missing.

#### Example: Creating a resource

```php
declare(strict_types=1);

namespace App\Http\Actions;

use AndrewDyer\Actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;

final class CreateProductAction extends AbstractAction
{
    protected function handle(): ResponseInterface
    {
        // Required parameters (throw exception if missing)
        $name = $this->resolveBodyParam('name');
        $price = $this->resolveBodyParam('price');

        // Optional parameter with default
        $description = $this->resolveBodyParam('description', 'No description provided');

        // Your domain logic here...
        $product = $this->createProduct($name, (float) $price, $description);

        return $this->ok($product, null, 201);
    }
}
```

**Request:**

```
POST /products
Accept: application/json
Content-Type: application/json

{
  "name": "Wireless Mouse",
  "price": 29.99
}
```

**Response: 201 Created**

```json
{
  "data": {
    "id": 456,
    "name": "Wireless Mouse",
    "description": "No description provided",
    "price": 29.99
  }
}
```

### Query parameters

Query parameters from the request URI can be accessed using two methods:

- **`getQueryParams(): array`** — Returns all query parameters as an associative array
- **`resolveQueryParam(string $name, mixed $default = null): mixed`** — Retrieves a query parameter by name. If a default value is provided, the parameter is optional and the default is returned when missing. If no default value is provided, the parameter is required and a RuntimeException is thrown when missing.

#### Example: Pagination and filtering

```php
declare(strict_types=1);

namespace App\Http\Actions;

use AndrewDyer\Actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;

final class ListProductsAction extends AbstractAction
{
    protected function handle(): ResponseInterface
    {
        // Required parameter (throws exception if missing)
        $category = $this->resolveQueryParam('category');

        // Optional parameters with defaults
        $page = max(1, (int) $this->resolveQueryParam('page', 1));
        $limit = max(1, (int) $this->resolveQueryParam('limit', 20));

        // Your domain logic here...
        $products = $this->fetchProducts($category, $page, $limit);
        $total = $this->countProducts($category);

        return $this->ok(
            $products,
            [
                'total' => $total,
                'page' => $page,
                'perPage' => $limit,
                'totalPages' => (int) ceil($total / $limit),
            ]
        );
    }
}
```

**Request**

```
GET /products?category=electronics&page=2&limit=10
Accept: application/json
```

**Response: 200 OK**

```json
{
  "data": [...],
  "meta": {
    "total": 156,
    "page": 2,
    "perPage": 10,
    "totalPages": 16
  }
}
```

#### Example: Array query parameters

Query parameters may also contain array values (for example, `?tags[]=foo&tags[]=bar&tags[]=baz`):

```php
protected function handle(): ResponseInterface
{
    // Resolves to ['foo', 'bar', 'baz']
    $tags = $this->resolveQueryParam('tags');

    // Your domain logic here...
    $items = $this->findByTags($tags);

    return $this->ok(['items' => $items]);
}
```

## Exception handling

Domain exceptions are caught automatically by `AbstractAction` and mapped to appropriate HTTP responses. This is useful when exceptions are thrown from services, repositories, or other domain logic that should not be coupled to HTTP concerns. Extend the base exception classes to create domain-specific exceptions.

| Base exception             | When to use                                      | Status | Error type                |
| -------------------------- | ------------------------------------------------ | ------ | ------------------------- |
| `BadRequestException`      | Request is malformed or violates business rules  | 400    | `BAD_REQUEST`             |
| `UnauthenticatedException` | Request lacks valid authentication credentials   | 401    | `UNAUTHENTICATED`         |
| `ForbiddenException`       | Authenticated caller lacks required permissions  | 403    | `INSUFFICIENT_PRIVILEGES` |
| `NotFoundException`        | Requested resource does not exist                | 404    | `RESOURCE_NOT_FOUND`      |
| `NotImplementedException`  | Requested functionality has not been implemented | 501    | `NOT_IMPLEMENTED`         |

> **Note:** When an exception has an empty message, the `description` field is omitted from the error response. API consumers should treat `description` as an optional field.

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
