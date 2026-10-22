# PHP SDK Generator

A flexible PHP SDK framework that helps you quickly build API client libraries with built-in authentication, resource management, and automatic data object mapping.

## Requirements

- PHP 8.3 or higher
- Composer
- A PSR-18 HTTP client and PSR-17 factories (any implementation; discovered automatically)

## Dependencies

- `php-http/client-common` - the plugin chain requests pass through
- `php-http/discovery` - finds your installed PSR-18 client and PSR-17 factories
- `cuyz/valinor` - maps API responses onto data objects
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

use Http\Message\Authentication;
use Http\Message\Authentication\Bearer;
use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Contracts\AuthenticationContract;
use Rooberthh\Sdk\Http\Builder;
use MyApi\Resources\UserResource;

class MyApiClient extends Client implements AuthenticationContract
{
    public function __construct(
        string $url,
        private readonly string $token,
        ?Builder $builder = null,
    ) {
        parent::__construct($url, $builder);
    }

    public function users(): UserResource
    {
        return new UserResource($this);
    }

    public function authentication(): Authentication
    {
        return new Bearer($this->token);
    }
}
```

Credentials are promoted constructor properties on purpose. `authentication()`
is called from `parent::__construct()`, and promoted properties are assigned
before the parent constructor body runs — assigning them afterwards fails with
*"must not be accessed before initialization"*.

### Using Your Generated SDK

```php
$client = new MyApi\MyApiClient('https://api.example.com');

// Use the resource methods
$user = $client->users()->show(1);
echo $user->name;
```

The client assembles its own transport from whatever PSR-18 client and PSR-17
factories you have installed, so nothing else needs wiring up. To supply your
own, pass a `Builder`:

```php
use Rooberthh\Sdk\Http\Builder;

$client = new MyApi\MyApiClient(
    url: 'https://api.example.com',
    builder: new Builder($myPsr18Client),
);
```

## Authentication

### How Authentication Works

Implement `AuthenticationContract` and the credentials it returns are applied to
every request — the client wires it up in its constructor, so there is no
separate setup step.

#### Bearer Token

```php
use Http\Message\Authentication;
use Http\Message\Authentication\Bearer;

class MyApiClient extends Client implements AuthenticationContract
{
    public function authentication(): Authentication
    {
        return new Bearer($this->token);
    }
}
```

#### Other forms

`php-http/message` ships the common ones — swap `Bearer` for any of these:

| Class | Sends |
| --- | --- |
| `Bearer` | `Authorization: Bearer <token>` |
| `BasicAuth` | `Authorization: Basic <base64>` |
| `Header` | any header you name |
| `QueryParam` | credentials as query parameters |
| `Wsse` | a WSSE token |
| `Chain` | several of the above, in order |

#### Signing requests, and refreshing tokens

Anything else is your own `Authentication`. It is handed the request and
returns it authenticated, so HMAC signing and fetching a fresh token both
belong here:

```php
use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;

final class HmacSignature implements Authentication
{
    public function __construct(private readonly string $secret) {}

    public function authenticate(RequestInterface $request): RequestInterface
    {
        $signature = hash_hmac('sha256', (string) $request->getBody(), $this->secret);

        return $request->withHeader('X-Signature', $signature);
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

// Optional filters - null is dropped, so no conditionals are needed
$request = FilterBuilder::for($this->request(Method::GET, '/users'))
    ->where('status', 'active')
    ->where('q', $search ?: null)
    ->apply();

// The key format is up to you - use whatever your API expects
$request = FilterBuilder::for($this->request(Method::GET, '/products'))
    ->where('filter[price][gte]', 100)  // Nested bracket notation
    ->where('price_lte', 500)            // Underscore notation
    ->apply();

// Bulk filters - nulls are dropped, so optional filters can be passed straight through
$request = FilterBuilder::for($this->request(Method::GET, '/users'))
    ->whereAll(['status' => $status, 'role' => $role])
    ->apply();
```

#### Value handling

Values are normalised when the query string is built:

| Given                 | Sent as                     |
| --------------------- | --------------------------- |
| `null`                | omitted entirely            |
| `true` / `false`      | `true` / `false`            |
| Backed enum           | its `value`                 |
| Pure enum             | its `name`                  |
| `DateTimeInterface`   | ATOM (`2026-01-02T03:04:05+00:00`) |
| `array`               | bracketed pairs (`tags[0]=vip&tags[1]=new`) |
| `Stringable`          | its string form             |

Anything else throws an `InvalidArgumentException` naming the offending filter.

Query parameters already present on the request are preserved; filters merge over
them, and bracketed keys merge key by key rather than colliding. Values are encoded
per RFC 3986, so spaces become `%20` rather than `+`.

## Advanced Usage

### The transport

Every request passes through a plugin chain held by a `Builder`. Plugins can be
added and removed at any point, and the Transport is rebuilt on the next request:

```php
use Http\Client\Common\Plugin\RetryPlugin;

$client->builder()->addPlugin(new RetryPlugin(['retries' => 3]));
$client->builder()->removePlugin(RetryPlugin::class);
```

Anything implementing `Http\Client\Common\Plugin` fits here — logging,
retries, redirects, recording. `php-http/cache-plugin` adds HTTP response
caching if you install it.

To apply plugins to every client of a given SDK instead, override
`defaultPlugins()`:

```php
class MyApiClient extends Client implements AuthenticationContract
{
    public function defaultPlugins(): array
    {
        return [new RetryPlugin(['retries' => 3])];
    }
}
```

It returns nothing by default. `ErrorPlugin` is deliberately not included:
failures are reported by `$response->failed()` and thrown only when you call
`$response->throw()`. `RetryPlugin` is left out too, since retrying a request
that is not idempotent is the SDK author's call to make.

### Reading the last response

Resource methods usually return data objects, which discards the response they
came from. `getLastResponse()` gets it back — for pagination links, rate limit
headers, or request ids:

```php
$users = $client->users()->list();          // list<UserDataObject>

$client->getLastResponse()?->header('X-RateLimit-Remaining');
$client->getLastResponse()?->status();
```

### Custom headers
Override `defaultHeaders()` in your client:

```php
class MyApiClient extends Client implements AuthenticationContract
{
    public function defaultHeaders(): array
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
