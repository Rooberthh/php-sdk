# PHP SDK Generator

A flexible PHP SDK framework that helps you quickly build API client libraries with built-in authentication, resource management, and automatic data object mapping.

## Requirements

- PHP 8.1 or higher
- Composer

## Dependencies

- `php-http/httplug` - HTTP client abstraction
- `league/object-mapper` - Automatic DTO hydration
- `rooberthh/http-tools` - HTTP utilities

## Quick Start

### Installation

```bash
composer require rooberthh/php-sdk
```

## Generating Your SDK

### Using the CLI Generator

The fastest way to create your SDK is using the included CLI generator that you can run from your project root.

```bash
./vendor/bin/php-sdk-generate
```

### Example Generated Client

```php
<?php

namespace MyApi;

use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Contracts\AuthenticationContract;
use MyApi\Resources\UserResource;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Message\Authentication\Bearer;

class MyApiClient extends Client implements AuthenticationContract
{
    public function users(): UserResource
    {
        return new UserResource($this);
    }

    public function authentication(): AuthenticationPlugin
    {
        return new AuthenticationPlugin(new Bearer('my-api-token'));
    }
}
```

### Using Your Generated SDK

```php
$client = new MyApi\MyApiClient();

// Use the resource methods
$user = $client->users()->show(1);
echo $user->name;
```

## Authentication

### How Authentication Works

The SDK uses a plugin-based authentication system built on `php-http/httplug`. When your client implements the `AuthenticationContract` you can leverage the authentication forms provided from `php-http/httplug`.

#### Bearer Token

```php
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Message\Authentication\Bearer;

class MyApiClient extends Client implements AuthenticationContract
{
    public function authentication(): AuthenticationPlugin
    {
        return new AuthenticationPlugin(new Bearer('your-api-token'));
    }
}
```

## Resource Classes

Resources represent API endpoints and handle HTTP requests. Each resource typically manages one API resource type (users, products, orders, etc.).

```php
<?php

namespace MyApi\Resources;

use Rooberthh\Sdk\Concerns\Resources\CanAccessClient;
use Rooberthh\Sdk\Concerns\Resources\CanCreateRequests;
use Rooberthh\Sdk\FilterBuilder;
use Rooberthh\HttpTools\Enums\Method;
use MyApi\DataObjects\UserDataObject;

class UserResource
{
    use CanAccessClient;
    use CanCreateRequests;

    public function list(): array
    {
        // Use FilterBuilder for fluent query parameter construction
        $request = FilterBuilder::for($this->request(Method::GET, '/users'))
            ->where('sort', 'name')
            ->where('order', 'asc')
            ->where('limit', 10)
            ->apply();
        // Results in: /users?sort=name&order=asc&limit=10

        $response = $this->client->send($request);

        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(
            fn($user) => UserDataObject::make($user),
            $data
        );
    }

    public function show(int $id): UserDataObject
    {
        $request = $this->request(Method::GET, "/users/{$id}");
        $response = $this->client->send($request);

        $data = json_decode($response->getBody()->getContents(), true);

        return UserDataObject::make($data);
    }

    public function create(array $userData): UserDataObject
    {
        $request = $this->request(Method::POST, '/users')
            ->withBody($this->payload(json_encode($userData)));

        $response = $this->client->send($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return UserDataObject::make($data);
    }

    public function update(int $id, array $userData): UserDataObject
    {
        $request = $this->request(Method::PUT, "/users/{$id}")
            ->withBody($this->payload(json_encode($userData)));

        $response = $this->client->send($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return UserDataObject::make($data);
    }

    public function delete(int $id): void
    {
        $request = $this->request(Method::DELETE, "/users/{$id}");
        $this->client->send($request);
    }
}
```

### FilterBuilder

The `FilterBuilder` provides a fluent interface for adding query parameters to requests:

```php
use Rooberthh\Sdk\FilterBuilder;

// Basic usage
$request = FilterBuilder::for($this->request(Method::GET, '/users'))
    ->where('status', 'active')
    ->where('page', 1)
    ->apply();

// Conditional filters
$request = FilterBuilder::for($this->request(Method::GET, '/users'))
    ->where('status', 'active')
    ->when($search, fn($builder) => $builder->where('q', $search))
    ->apply();

// The key format is up to you - use whatever your API expects
$request = FilterBuilder::for($this->request(Method::GET, '/products'))
    ->where('filter[price][gte]', 100)  // Nested bracket notation
    ->where('price_lte', 500)            // Underscore notation
    ->apply();
```

## Advanced Usage

### Custom headers
Override `defaultHeaders()` in your client:

```php
class MyApiClient extends Client implements AuthenticationContract
{
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'User-Agent' => 'MySDK/1.0',
            'X-Custom-Header' => 'value',
        ];
    }
}
```

## Special Thanks

This project would not have been possible without the incredible work of the open-source community.

A huge thank you to [Steve McDougall](https://github.com/JustSteveKing) whose work heavily inspired the architecture and patterns used in this SDK. The foundation and approach taken here are directly influenced by his contributions to the PHP ecosystem.

## License

MIT License
