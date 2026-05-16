[![PHP Version Require](http://poser.pugx.org/waffle-commons/utils/require/php)](https://packagist.org/packages/waffle-commons/utils)
[![PHP CI](https://github.com/waffle-commons/utils/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/utils/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/utils/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/utils)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/utils/v)](https://packagist.org/packages/waffle-commons/utils)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/utils/v/unstable)](https://packagist.org/packages/waffle-commons/utils)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/utils.svg)](https://packagist.org/packages/waffle-commons/utils)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/utils)](https://github.com/waffle-commons/utils/blob/main/LICENSE.md)

Waffle Utils Component
======================

> **Release:** `v0.1.0-beta0`

Stateless, pure-function helpers shared across the Waffle ecosystem. The package intentionally has no I/O dependencies and no per-process state — every helper here is safe to use across FrankenPHP worker requests without reset.

## 📦 Installation

```bash
composer require waffle-commons/utils
```

## 🧱 Surface

| Class / trait | Role |
| :--- | :--- |
| `Waffle\Commons\Utils\Trait\ReflectionTrait` | Tokenizer-based class introspection used by the router and container for attribute discovery. |

That is the entire Beta 0 surface. The package will grow only when a helper is genuinely shared across more than one component.

## 🔍 `ReflectionTrait`

Reads a PHP file with `token_get_all()` (no regex, no eval) and returns the fully qualified class/interface/trait/enum name found inside, or an empty string if none is present. Used by the routing component's `RouteDiscoverer` and `ControllerFinder` for attribute-based route scanning.

```php
use Waffle\Commons\Utils\Trait\ReflectionTrait;

final class MyDiscoverer
{
    use ReflectionTrait;

    public function fqcnFor(string $absolutePath): string
    {
        return $this->className($absolutePath);
    }
}
```

The implementation handles:

- Bracketed (`namespace App { … }`) and statement (`namespace App;`) namespace forms.
- PHP 8.x `final`, `readonly`, `abstract` modifiers in front of `class`/`interface`/`trait`/`enum`.
- Anonymous classes — they are skipped (returns the first non-anonymous declaration).

## 🐘 PHP 8.5 surface

`ReflectionTrait` declares strict types and explicit return types throughout. The trait does not introduce mutable state and is safe to compose into a `readonly` class.

## 🧪 Testing

```bash
docker exec -w /waffle-commons/utils waffle-dev composer tests
```

## 📄 License

MIT — see [LICENSE.md](./LICENSE.md).
