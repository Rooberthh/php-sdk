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
- `tests/Unit` covers single classes; `tests/Feature` covers flows through the client (pagination, request payloads, etc.).
- Test doubles live in `tests/Stubs`; JSON response fixtures live in `tests/Fixtures`.
- HTTP is faked with `Rooberthh\Sdk\Testing\FakeTransport` (older tests use `php-http/mock-client`); tests never hit the network.

## Architecture

- Root namespace is `Rooberthh\Sdk\` (PSR-4 from `src/`).
- Interfaces live in `Contracts/` with a `Contract` suffix (`ClientContract`, `DataObjectContract`).
- Reusable behaviour is a trait in `Concerns/`, named `Can…` (`CanCreateInstances`, `CanCreateRequests`).
- Exceptions are `final`, extend an SPL exception, and implement `SdkException` so consumers can catch everything the SDK throws. They are named for what went wrong, without an `Exception` suffix (`RequestFailed`, `UnableToReadResponse`), and are built through named static constructors (`::with()`, `::for()`, `::missingKey()`) rather than `new`.
- Data objects implement `DataObjectContract` and hydrate through `CanCreateInstances`: snake_case response keys map to camelCase properties, and unknown keys are ignored so new API fields aren't breaking changes.
- Wrap PSR-7/17/18 objects rather than replace them, and keep the original reachable (e.g. `Response::psr()`).
- Don't decide on the caller's behalf: a failed response is reported (`failed()`), not thrown, unless the caller asks (`throw()`) or asks for Data Objects (`dataObject()`, `dataObjects()`, `paginated()`), which assert success.
- SDK-author hooks are `protected` methods on `Client` with a null/empty default (`defaultHeaders()`, `defaultPlugins()`, `exceptionFor()`), not added to `ClientContract`. `exceptionFor(Response): ?Throwable` maps a failed response to the SDK's own exception; it is consulted only when a failed response is about to throw, never for a 2xx one.
