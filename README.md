[![PHP Version Require](http://poser.pugx.org/waffle-commons/utils/require/php)](https://packagist.org/packages/waffle-commons/utils)
[![PHP CI](https://github.com/waffle-commons/utils/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/utils/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/utils/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/utils)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/utils/v)](https://packagist.org/packages/waffle-commons/utils)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/utils/v/unstable)](https://packagist.org/packages/waffle-commons/utils)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/utils.svg)](https://packagist.org/packages/waffle-commons/utils)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/utils)](https://github.com/waffle-commons/utils/blob/main/LICENSE.md)

Waffle Utils Component
======================

> **Release:** `v0.1.0-beta1`

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

The package grows only when a helper is genuinely shared across more than one component.

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

All three services are `final readonly class` with strict types and explicit return types throughout. They hold no mutable state and are safe to reuse across FrankenPHP worker requests.

## 🧪 Testing

```bash
docker exec -w /waffle-commons/utils waffle-dev composer tests
```

## 📄 License

MIT — see [LICENSE.md](./LICENSE.md).
