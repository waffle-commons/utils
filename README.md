[![Discord](https://img.shields.io/discord/755288001592033391?logo=discord)](https://discord.gg/eKgywnfXr2)
[![PHP Version Require](http://poser.pugx.org/waffle-commons/utils/require/php)](https://packagist.org/packages/waffle-commons/utils)
[![PHP CI](https://github.com/waffle-commons/utils/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/utils/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/utils/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/utils)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/utils/v)](https://packagist.org/packages/waffle-commons/utils)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/utils/v/unstable)](https://packagist.org/packages/waffle-commons/utils)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/utils.svg)](https://packagist.org/packages/waffle-commons/utils)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/utils)](https://github.com/waffle-commons/utils/blob/main/LICENSE.md)

Waffle Utils Component
======================

> **Release:** `0.1.0-beta6` &nbsp;|&nbsp; [`CHANGELOG.md`](./CHANGELOG.md)

Stateless, pure-function helpers shared across the Waffle ecosystem. The package intentionally has no I/O dependencies and no per-process state — every helper here is safe to use across FrankenPHP worker requests without reset.

## 🆕 Beta-1 change

The former `Waffle\Commons\Utils\Trait\ReflectionTrait` has been **removed** and decomposed into three single-responsibility `final readonly` services (Beta-1 Phase 1 architectural pass — Single Responsibility over trait-based reuse). Consumers inject the service they need instead of mixing in a trait.

## 📦 Installation

```bash
composer require waffle-commons/utils
```

## 🧱 Surface

| Class | Role |
| :--- | :--- |
| `Waffle\Commons\Utils\Service\ClassParser` | Tokenizer-based class introspection. `className(string $path): string` reads a PHP file with `token_get_all()` (no regex, no eval) and returns the fully qualified class/interface/trait/enum name, or `''` if none. Used by routing's `RouteDiscoverer` / `ControllerFinder`. |
| `Waffle\Commons\Utils\Service\AttributeReader` | `newAttributeInstance(object $target, string $attribute): object` resolves an attribute instance from a target, falling back to a zero-arg instance when the target carries no matching attribute (preserving the former trait's contract). |
| `Waffle\Commons\Utils\Service\ReflectionInspector` | Object-shape inspection: `isFinal()`, `isInstance()`, `getProperties()`, `getMethods()`. |
| `Waffle\Commons\Utils\Dto\Shape\DtoShapeReader` | Build-time reader of a `#[Dto]` class: constructor parameters in, readable properties out. Shared by the serializer and the OpenAPI generator (`0.1.0-beta7`, CARRY-04). |

The package grows only when a helper is genuinely shared across more than one component.

## 🧩 `DtoShapeReader`

Reads what a `#[Dto]` class accepts and emits, at build time, so the serializer's compiled normalizers and the OpenAPI schema describe the same thing:

```php
use Waffle\Commons\Utils\Dto\Shape\DtoShapeReader;

$shape = new DtoShapeReader()->read(OrderInput::class);
$shape->inputs;  // list<InputField>: the constructor parameters (name, type, required, default)
$shape->outputs; // list<OutputField>: the readable properties (name, type, computed)

$graph = new DtoShapeReader()->readGraph(OrderInput::class); // the DTO and every #[Dto] it reaches
```

- **Inputs** are the constructor parameters: a payload is hydrated through the constructor, so `set` hooks always run. A parameter is required when it has no default and is not nullable.
- **Outputs** are the public, non-static properties, minus write-only virtual ones (a `set` hook without a `get` hook). A virtual property with a `get` hook is a computed output. `private(set)`, `protected(set)` and `readonly` do not affect reading.
- **Types:** `int`, `float`, `string`, `bool`, a backed enum, a nested `#[Dto]` (`self` included), or an `array` whose element type is declared with `#[ListOf]`. Any of them can be nullable. A default value is `null`, a scalar, a backed enum case or `[]`.
- Anything else (an untyped member, `mixed`, another union, an intersection, a pure enum, an object that is not a `#[Dto]`, a variadic or by-reference parameter) throws `Waffle\Commons\Utils\Exception\UnsupportedDtoShapeException`, naming the class and the member.

It reflects on every call and caches nothing: no state survives a worker request.

## 🔍 `ClassParser`

Reads a PHP file with `token_get_all()` (no regex, no eval) and returns the fully qualified class/interface/trait/enum name found inside, or an empty string if none is present.

```php
use Waffle\Commons\Utils\Service\ClassParser;

$parser = new ClassParser();
$fqcn = $parser->className('/path/to/UserController.php'); // 'App\Controller\UserController'
```

The implementation handles:

- Bracketed (`namespace App { … }`) and statement (`namespace App;`) namespace forms.
- PHP 8.x `final`, `readonly`, `abstract` modifiers in front of `class`/`interface`/`trait`/`enum`.
- Anonymous classes — they are skipped (returns the first non-anonymous declaration).

## 🐘 PHP 8.5 surface

All the services, the DTO-shape reader included, are `final readonly class` with strict types and explicit return types throughout. They hold no mutable state and are safe to reuse across FrankenPHP worker requests.

## 🧭 Architectural boundary (`mago guard`)

An active dependency **perimeter** is enforced on every CI run by `vendor/bin/mago guard` (bundled into `composer mago`; zero baselines). The rules live in [`mago.toml`](./mago.toml) under `[guard.perimeter]` — a forbidden `use` statement fails the build, not a reviewer.

Production code under `Waffle\Commons\Utils` may depend **only** on:

- `Waffle\Commons\Utils\**` — itself
- `Waffle\Commons\Contracts\**` — the shared contracts package, the **only** Waffle dependency permitted
- `Psr\**` — PSR interfaces
- `@global` + `Psl\**` — PHP core and the PHP Standard Library

Test code under `WaffleTests\Commons\Utils` is unrestricted (`@all`). Structural rules are guarded too: interfaces must be named `*Interface`, `Exception\**` classes must end in `*Exception`, and any `Enum\**` namespace may hold only `enum` declarations.

Contract-first, component-agnostic by construction: components compose through `waffle-commons/contracts`, never directly through one another.

## 🧪 Testing

```bash
docker exec -w /waffle-commons/utils waffle-dev composer tests
```

## 📚 Documentation

Full guides live in the central Diátaxis documentation tree:

- [Reference — `waffle-commons/utils`](https://github.com/waffle-commons/documentation/blob/main/reference/utils.md)
- [Documentation home](https://github.com/waffle-commons/documentation)

## 📄 License

MIT — see [LICENSE.md](./LICENSE.md).
