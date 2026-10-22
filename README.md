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
separate setup step. `authentication()` is called on the first request, so
credentials can be assigned anywhere in the constructor.

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
use Rooberthh\HttpTools\Enums\Method;
use MyApi\DataObjects\UserDataObject;

class UserResource
{
    use CanAccessClient;

    public function list(): array
    {
        return $this->client
            ->request(Method::GET, '/users', query: [
                'sort'  => 'name',
                'order' => 'asc',
                'limit' => 10,
            ])
            // Results in: /users?sort=name&order=asc&limit=10
            ->dataObjects(UserDataObject::class);
    }

    public function show(int $id): UserDataObject
    {
        return $this->client
            ->request(Method::GET, "/users/{$id}")
            ->dataObject(UserDataObject::class);
    }

    public function create(array $userData): UserDataObject
    {
        return $this->client
            ->request(Method::POST, '/users', body: $userData)
            ->dataObject(UserDataObject::class);
    }

    public function update(int $id, array $userData): UserDataObject
    {
        return $this->client
            ->request(Method::PUT, "/users/{$id}", body: $userData)
            ->dataObject(UserDataObject::class);
    }

    public function delete(int $id): void
    {
        $this->client->request(Method::DELETE, "/users/{$id}");
    }
}
```

`request()` builds the request and sends it, returning a `Response`. The `$uri`
is joined onto the client's base URL, the `query` array is applied as query
parameters, and the `body` is encoded by its type:

| `body` type | Sent as |
| --- | --- |
| object | snake_case JSON — camelCase properties become snake_case keys, and null properties are dropped |
| array | JSON, exactly as given |
| string | the raw string, untouched — for APIs that do not speak JSON |
| `null` (default) | no body |

Per-call `headers` are set on the request and win over the client's defaults, so
a raw body can declare its own `Content-Type`:

```php
$this->client->request(
    Method::POST,
    '/orders',
    body: '<order><id>1</id></order>',
    headers: ['Content-Type' => 'application/xml'],
);
```

### Failed requests

The Data Object methods — `dataObject()`, `dataObjects()` and `paginated()` —
throw `RequestFailed` when the request did not come back 2xx, before they read
the body. Asking for a Data Object asserts the request succeeded, so a failed
response surfaces the failed status rather than a mapping or reading error:

```php
try {
    $user = $this->client->request(Method::GET, "/users/{$id}")
        ->dataObject(UserDataObject::class);
} catch (RequestFailed $exception) {
    // $exception->response is the failed Response
    $status = $exception->response->status();
}
```

Everything else only reports failure: `failed()`, `successful()`, `status()`,
`json()`, `body()` and the header methods never throw on a failed response.

To throw your own exception rather than `RequestFailed`, see
[Your own exceptions](#your-own-exceptions).

### Query parameters

The `query` argument adds query-string parameters to the request. Null values
are dropped, so optional filters can be passed straight through without
conditionals, and the key format is whatever your API expects:

```php
// Optional filters — null is dropped
$this->client->request(Method::GET, '/users', query: [
    'status' => 'active',
    'q'      => $search ?: null,
]);

// Bracket and underscore notation both pass through untouched
$this->client->request(Method::GET, '/products', query: [
    'filter[price][gte]' => 100,
    'price_lte'          => 500,
]);
```

Parameters already present in the `$uri` are preserved; the `query` array merges
over them.

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

## Testing your SDK

Tests shouldn't hit the network, and you shouldn't have to wire a mock to avoid
it. `Rooberthh\Sdk\Testing\FakeTransport` is a Transport you hand your Client a
table of canned responses and plug straight into a `Builder`:

```php
use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Testing\FakeResponse;
use Rooberthh\Sdk\Testing\FakeTransport;

$fake = new FakeTransport([
    'GET users/1' => FakeResponse::json(['id' => 1, 'name' => 'Ada']),
    'POST users'  => FakeResponse::json(['id' => 2], 201),
    'users/*'     => FakeResponse::status(404),
]);

$client = new MyApiClient(url: 'https://api.example.com', token: 't', builder: $fake->builder());
```

Each key is an optional HTTP method, a space, then a path pattern matched against
the request URI's path. A key without a method matches any method; `*` matches any
run of characters, slashes included, so `*` alone matches every request. The query
string is ignored and a leading `/` doesn't matter on either side. The first matching
key wins, in array order. A value may be a `ResponseInterface`, an array (shorthand
for `FakeResponse::json()`), or a `Closure(RequestInterface): ResponseInterface` for
dynamic responses.

The fake sits at the end of the plugin chain, so every recorded request is exactly
what would have gone over the wire — auth and default headers included. Assert on
them with the assertions (they use `PHPUnit\Framework\Assert`, so they work in both
PHPUnit and Pest):

```php
$fake->assertSent(fn (RequestInterface $r) => $r->getMethod() === 'POST'
    && json_decode((string) $r->getBody(), true)['name'] === 'Ada');

$fake->assertNotSent(fn (RequestInterface $r) => $r->getMethod() === 'DELETE');
$fake->assertSentCount(1);
$fake->assertNothingSent();
```

A request that matches no key throws `Rooberthh\Sdk\Testing\UnexpectedRequest` listing
the registered keys — a missing fake is a bug in your test, so the transport never
falls back to a silent default.

> The assertions need `phpunit/phpunit`. It ships with Pest, so if you test with Pest
> it's already there; otherwise `composer require --dev phpunit/phpunit`.

## Advanced Usage

### The Transport

Every request passes through a plugin chain held by a `Builder`. Plugins can be
added and removed at any point, and the Transport is rebuilt on the next request.
The builder passed in is copied, so one `Builder` can safely be reused across
several clients:

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
    protected function defaultPlugins(): array
    {
        return [new RetryPlugin(['retries' => 3])];
    }
}
```

It returns nothing by default. `ErrorPlugin` is deliberately not included:
failures are reported by `$response->failed()` and thrown only when you call
`$response->throw()` or ask for Data Objects. `RetryPlugin` is left out too,
since retrying a request that is not idempotent is the SDK author's call to
make.

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

### Your own exceptions

By default a failed request throws `RequestFailed`. Override `exceptionFor()` in
your client to map a failed response to your own exception instead. It is
consulted only when a failed response is about to throw — through `throw()` and
the Data Object methods — never for a successful one. Return `null` for a status
you do not handle, and `RequestFailed` is thrown as before:

```php
protected function exceptionFor(Response $response): ?Throwable
{
    return match ($response->status()) {
        404     => new NotFound($response),
        422     => ValidationFailed::from($response->json('errors', [])),
        default => null,
    };
}
```

Have your exceptions implement `Rooberthh\Sdk\Exceptions\SdkException` and
Consumers can catch everything the SDK throws — yours and `RequestFailed` — with
a single catch.

## Special Thanks

This project would not have been possible without the incredible work of the open-source community.

A huge thank you to [Steve McDougall](https://github.com/JustSteveKing) whose work heavily inspired the architecture and patterns used in this SDK. The foundation and approach taken here are directly influenced by his contributions to the PHP ecosystem.

## License

MIT License
