# PHP SDK

A framework for building PHP SDKs for third-party APIs: a Client to extend,
Resources that build plain PSR-7 requests, typed Data Objects mapped from the
responses, and authentication the Consumer chooses.

## Requirements

- PHP 8.3 or higher
- Composer
- A PSR-18 HTTP client and PSR-17 factories (any implementation; discovered automatically)

## Dependencies

- `php-http/client-common` - the plugin chain requests pass through
- `php-http/message` - the `Authentication` interface every auth strategy implements
- `php-http/discovery` - finds your installed PSR-18 client and PSR-17 factories
- `cuyz/valinor` - maps API responses onto data objects
- `rooberthh/http-tools` - HTTP utilities

## Installation

```bash
composer require rooberthh/php-sdk
```

## Building an SDK

### The Client

Extend `Client` and add a method per Resource. Nothing else is required — no
constructor, no transport wiring, no authentication code:

```php
use Rooberthh\Sdk\Client;

final class Acme extends Client
{
    public function users(): UserResource
    {
        return new UserResource($this);
    }
}
```

### Resources

A Resource is a plain class composed from two traits:

- `CanAccessClient` — the constructor that receives the Client.
- `CanCreateRequests` — `request()` builds a PSR-7 request against the Client's
  URL, and `payload()` encodes a request body.

```php
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Concerns\Resources\CanAccessClient;
use Rooberthh\Sdk\Concerns\Resources\CanCreateRequests;

final class UserResource
{
    use CanAccessClient;
    use CanCreateRequests;

    /**
     * @return list<User>
     */
    public function list(?string $status = null): array
    {
        $request = $this->request(Method::GET, '/users', query: ['status' => $status]);

        return $this->client->send($request)->dataObjects(User::class, 'data');
    }

    public function show(int $id): User
    {
        $request = $this->request(Method::GET, "/users/{$id}");

        return $this->client->send($request)->dataObject(User::class);
    }

    public function create(CreateUser $user): User
    {
        $request = $this->request(Method::POST, '/users')
            ->withBody($this->payload($user));

        return $this->client->send($request)->dataObject(User::class);
    }

    public function delete(int $id): void
    {
        $this->client->send($this->request(Method::DELETE, "/users/{$id}"))->throw();
    }
}
```

`request()` returns a plain PSR-7 request, so anything else goes on with the
usual `with…()` methods — a header, a different body:

```php
$request = $this->request(Method::POST, '/orders')
    ->withHeader('Content-Type', 'application/xml')
    ->withBody($this->payload('<order><id>1</id></order>'));
```

The Client has the same two methods as `createRequest()` and `createPayload()`,
for operations that don't belong to a Resource:

```php
final class Acme extends Client
{
    public function ping(): bool
    {
        return $this->send($this->createRequest(Method::GET, '/ping'))->successful();
    }
}
```

### Data Objects

A Data Object is a `readonly` class built from the response by
`dataObject()` / `dataObjects()`. snake_case response keys map to camelCase
properties, and keys the object doesn't declare are ignored, so an API adding a
field is never a breaking change:

```php
use Rooberthh\Sdk\Concerns\DataObjects\CanCreateInstances;
use Rooberthh\Sdk\Contracts\DataObjectContract;

final readonly class User implements DataObjectContract
{
    use CanCreateInstances;

    public function __construct(
        public int $id,
        public string $name,
        public ?string $avatarUrl,
    ) {}
}
```

Pass a key to read inside an envelope — `dataObjects(User::class, 'data')` reads
the rows out of `{"data": [...], "meta": {...}}`.

### Generating the skeleton

The included generator writes a Client, a Resource and a Data Object to start
from:

```bash
./vendor/bin/php-sdk-generate
```

## Using an SDK

The Consumer constructs the Client with the API's URL and the credentials they
hold:

```php
use Rooberthh\Sdk\Auth\BearerToken;

$acme = new Acme(
    url: 'https://api.acme.test',
    auth: new BearerToken($token),
);

$user = $acme->users()->show(1);
echo $user->name;
```

## Authentication

Authentication is the Consumer's choice: whatever strategy is passed to the
constructor is applied to every request. These ship ready-made:

| Strategy | Sends |
| --- | --- |
| `new BearerToken($token)` | `Authorization: Bearer <token>` |
| `new BasicAuth($username, $password)` | `Authorization: Basic <base64>` |
| `ApiKey::header('X-Api-Key', $key)` | the key in the header you name |
| `ApiKey::query('api_key', $key)` | the key as a query parameter, after the query already there |
| `new NoAuth()` | nothing — the request as it is |

All of them live in `Rooberthh\Sdk\Auth` and implement
`Http\Message\Authentication`, so anything else that does fits too —
`php-http/message`'s `Wsse` and `Chain`, or your own:

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

### Per-request authentication

A Resource can send one request with other credentials, or with none, by
passing them to `send()`:

```php
use Rooberthh\Sdk\Auth\NoAuth;

public function status(): Response
{
    return $this->client->send($this->request(Method::GET, '/status'), auth: new NoAuth());
}

public function login(string $username, string $password): Session
{
    $request = $this->request(Method::POST, '/sessions');

    return $this->client
        ->send($request, auth: new BasicAuth($username, $password))
        ->dataObject(Session::class);
}
```

Credentials are applied before the plugin chain, so every plugin — and the
fake transport in tests — sees the request exactly as it goes over the wire.

### Requiring a particular strategy

An SDK whose API only accepts one form can say so in its own constructor:

```php
final class Acme extends Client
{
    public function __construct(string $token, string $url = 'https://api.acme.test')
    {
        parent::__construct($url, new BearerToken($token));
    }
}
```

## Requests

### Query parameters

The `query` argument adds query-string parameters to the request. Null values
are dropped, so optional filters can be passed straight through without
conditionals, and the key format is whatever your API expects:

```php
// Optional filters — null is dropped
$this->request(Method::GET, '/users', query: [
    'status' => 'active',
    'q'      => $search ?: null,
]);

// Bracket and underscore notation both pass through untouched
$this->request(Method::GET, '/products', query: [
    'filter[price][gte]' => 100,
    'price_lte'          => 500,
]);
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

Query parameters already in the `$uri` are preserved; the `query` array merges
over them, and bracketed keys merge key by key rather than colliding. Values are
encoded per RFC 3986, so spaces become `%20` rather than `+`. A `$uri` given no
`query` array is sent exactly as written.

### Payloads

`payload()` encodes a body by its type:

| Given | Sent as |
| --- | --- |
| object | snake_case JSON — camelCase properties become snake_case keys, and null properties are dropped |
| array | JSON, exactly as given |
| string | the raw string, untouched — for APIs that do not speak JSON |

### Failed requests

The Data Object methods — `dataObject()` and `dataObjects()` —
throw `RequestFailed` when the request did not come back 2xx, before they read
the body. Asking for a Data Object asserts the request succeeded, so a failed
response surfaces the failed status rather than a mapping or reading error:

```php
try {
    $user = $acme->users()->show($id);
} catch (RequestFailed $exception) {
    // $exception->response is the failed Response
    $status = $exception->response->status();
}
```

Everything else only reports failure: `failed()`, `successful()`, `status()`,
`json()`, `body()` and the header methods never throw on a failed response.
Call `throw()` to ask for it.

## Testing your SDK

Tests shouldn't hit the network, and you shouldn't have to wire a mock to avoid
it. `Rooberthh\Sdk\Testing\FakeTransport` is a PSR-18 client you hand a table of
canned responses, and give to your Client with `client()`:

```php
use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Auth\BearerToken;
use Rooberthh\Sdk\Testing\FakeResponse;
use Rooberthh\Sdk\Testing\FakeTransport;

$fake = new FakeTransport([
    'GET users/1' => FakeResponse::json(['id' => 1, 'name' => 'Ada']),
    'POST users'  => FakeResponse::json(['id' => 2], 201),
    'users/*'     => FakeResponse::status(404),
]);

$acme = (new Acme('https://api.acme.test', new BearerToken('t')))->client($fake);
```

Each key is an optional HTTP method, a space, then a path pattern matched against
the request URI's path. A key without a method matches any method; `*` matches any
run of characters, slashes included, so `*` alone matches every request. The query
string is ignored and a leading `/` doesn't matter on either side. The first matching
key wins, in array order. A value may be a `ResponseInterface`, an array (shorthand
for `FakeResponse::json()`), or a `Closure(RequestInterface): ResponseInterface` for
dynamic responses.

The fake sits at the end of the plugin chain, so every recorded request is exactly
what would have gone over the wire — credentials and default headers included.
Assert on them with the assertions (they use `PHPUnit\Framework\Assert`, so they
work in both PHPUnit and Pest):

```php
$fake->assertSent(fn (RequestInterface $r) => $r->getHeaderLine('Authorization') === 'Bearer t');

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

### Plugins

Every request passes through a plugin chain. The Consumer adds to it with
`setup()`, at any time:

```php
use Http\Client\Common\Plugin\RetryPlugin;

$acme = (new Acme('https://api.acme.test', new BearerToken($token)))
    ->setup([new RetryPlugin(['retries' => 3])]);
```

Anything implementing `Http\Client\Common\Plugin` fits here — logging,
retries, redirects, recording. `php-http/cache-plugin` adds HTTP response
caching if you install it.

An SDK Author applies plugins to every Client of their SDK by overriding
`defaultPlugins()`. They run after the default headers and before the
Consumer's `setup()` plugins:

```php
final class Acme extends Client
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

### Your own HTTP client

The PSR-18 client is discovered from whatever is installed. To send through a
particular one, pass it to `client()`; plugins still apply:

```php
$acme = (new Acme('https://api.acme.test'))->client($myPsr18Client);
```

### Reading the last response

Resource methods usually return data objects, which discards the response they
came from. `getLastResponse()` gets it back — for pagination links, rate limit
headers, or request ids:

```php
$users = $acme->users()->list();          // list<User>

$acme->getLastResponse()?->header('X-RateLimit-Remaining');
$acme->getLastResponse()?->status();
```

### Custom headers

Override `defaultHeaders()` in your Client. It is read on the first request, so
it may use properties your constructor assigns after `parent::__construct()`:

```php
final class Acme extends Client
{
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'User-Agent' => 'AcmeSDK/1.0',
        ];
    }
}
```

## Special Thanks

This project would not have been possible without the incredible work of the open-source community.

A huge thank you to [Steve McDougall](https://github.com/JustSteveKing) whose work heavily inspired the architecture and patterns used in this SDK. The foundation and approach taken here are directly influenced by his contributions to the PHP ecosystem.

## License

MIT License
