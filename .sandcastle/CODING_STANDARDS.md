# Coding Standards

<!-- The reviewer agent loads this file during code review via @.sandcastle/CODING_STANDARDS.md. -->

## Style

- Every PHP file starts with `declare(strict_types=1);`.
- Formatting follows Laravel Pint with the `per` preset (`pint.json`). Run `vendor/bin/pint`; don't hand-format against it.
- Import classes with `use` statements instead of writing fully qualified names inline.
- In PHPDoc, put `null` first in union types (`null|array<mixed, mixed>`) and order tags `@param`, `@throws`, `@return`.
- Classes are `final` by default; data/value objects are `final readonly`. Use constructor property promotion.
- Docblocks explain why something is the way it is and what callers can rely on — they don't restate the signature. Inline comments explain non-obvious decisions.

## Static analysis

- PHPStan at level 6 over `src/` must pass (`vendor/bin/phpstan analyse`).
- Give arrays and class names precise PHPDoc types (`array<mixed, mixed>`, `class-string`).

## Testing

- Tests are written with Pest (`it('...')` closures and the `expect()` API), not PHPUnit test classes.
- `tests/Unit` covers single classes; `tests/Feature` covers flows through an SDK built on the Connector (requests, request payloads, response mapping).
- `tests/Fixtures/Acme` is a fixture SDK for a fictional Acme API (`AcmeConnector`, Resources, Data Objects, Payloads), built the way an SDK Author would. Feature tests drive it end to end; extend it when a new framework feature needs an SDK to exercise it. Its JSON response bodies live in `tests/Fixtures/Acme/responses`.
- HTTP is faked with `php-http/mock-client` (`Http\Mock\Client`), passed as the Connector's `$http` argument or through `->client()`; tests never hit the network. Queue answers with `addResponse()`/`addException()`, or `on($matcher, $result)` to match the request, and assert on `getLastRequest()`/`getRequests()`, which hold each request as it went over the wire (after auth and plugins). Don't write a hand-rolled PSR-18 fake.
- The mock answers an empty 200 when nothing is queued, so set `setDefaultException()` with an `Http\Client\Exception` (e.g. `TransferException`) to make an unexpected request fail the test. It throws all queued exceptions before any queued response; use `on()` to interleave them.

## Architecture

- Root namespace is `Rooberthh\Sdk\` (PSR-4 from `src/`).
- Interfaces live in `Contracts/` with a `Contract` suffix (`ClientContract`, `DataObjectContract`).
- Reusable behaviour is a trait in `Concerns/`, named `Can…` (`CanCreateInstances`).
- Each API operation is a `final readonly` class implementing `Rooberthh\Sdk\Contracts\RequestContract<T>`, taking its inputs through the constructor. It has `method()`, `path()` and `result()`. A Request that sends a Payload also implements `Contracts\HasBody`, returning a `Contracts\BodyContract`: `Http\Body\JsonBody`, `FormBody`, `MultipartBody` (text fields and `Http\File` uploads, a list for several parts under one name) or `RawBody($content, $contentType)`. Encode the Payload in the Body's constructor so a bad one fails where the Request builds it. An encoding the framework lacks is a new `BodyContract` implementation; one that doesn't goes out with no body and no Content-Type. Give every Request an `@implements RequestContract<...>` tag (or `@extends` of an SDK's own base Request) so `send()` returns a typed result. Encode input that goes into a path (`rawurlencode()`). A path never carries a query: a Request with one also implements `Contracts\HasQuery`, returning the parameters as an array (`['status' => $this->status?->value, 'page' => $this->page]`), which the Connector encodes through `Http\Query`. Null values are left out, booleans are sent as `true`/`false`, and a list repeats its key (name the key `tags[]` for the bracket style, or `implode()` the values for the comma style).
- `Connector::send(RequestContract<T>): T` sends a Request and returns its `result()`; `raw(RequestContract)` returns the PSR-7 response itself. The Connector alone turns a Request into PSR-7 (resolving its path under the base URI, adding a `HasQuery` Request's Query, attaching a `HasBody` Request's Body with its own Content-Type, which no default or Consumer header overrides); nothing else builds PSR-7 requests.
- Resources are given the Connector through their constructor and only create Requests and `send()` them. They are a convenience: a Consumer can always send a Request directly.
- Authentication strategies are any `Http\Message\Authentication`; use `php-http/message`'s (`Bearer`, `BasicAuth`, `Header`) rather than re-implementing them. `Auth/` holds only what php-http lacks (`NoAuth`). The Consumer passes one to the Connector's constructor, and it is applied to every request before the plugin chain.
- Exceptions are `final`, extend an SPL exception, and implement `SdkException` so consumers can catch everything the SDK throws. They are named for what went wrong, without an `Exception` suffix (`RequestFailed`, `UnableToReadResponse`), and are built through named static constructors (`::with()`, `::for()`, `::missingKey()`) rather than `new`.
- Data objects implement `DataObjectContract` and hydrate through `CanCreateInstances`: snake_case response keys map to camelCase properties, and unknown keys are ignored so new API fields aren't breaking changes.
- Wrap PSR-7/17/18 objects rather than replace them, and keep the original reachable (e.g. `Response::psr()`).
- Don't decide on the caller's behalf: a failed response is reported (`failed()`), not thrown, unless the caller asks (`throw()`) or asks for Data Objects (`dataObject()`, `dataObjects()`), which assert success.
