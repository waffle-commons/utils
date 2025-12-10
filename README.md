[![PHP Version Require](http://poser.pugx.org/waffle-commons/utils/require/php)](https://packagist.org/packages/waffle-commons/utils)
[![PHP CI](https://github.com/waffle-commons/utils/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/utils/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/utils/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/utils)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/utils/v)](https://packagist.org/packages/waffle-commons/utils)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/utils/v/unstable)](https://packagist.org/packages/waffle-commons/utils)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/utils.svg)](https://packagist.org/packages/waffle-commons/utils)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/utils)](https://github.com/waffle-commons/utils/blob/main/LICENSE.md)

Waffle Utils Component
======================

A collection of utility classes and traits used throughout the Waffle Framework.

## 📦 Installation

```bash
composer require waffle-commons/utils
```

## 🚀 Usage

### ReflectionTrait

Provides helper methods for reflection, useful for testing or metaprogramming.

```php
use Waffle\Commons\Utils\Trait\ReflectionTrait;

class MyClass {
    use ReflectionTrait;
}
```

Testing
-------

To run the tests, use the following command:

```bash
composer tests
```

Contributing
------------

Contributions are welcome! Please refer to [CONTRIBUTING.md](./CONTRIBUTING.md) for details.

License
-------

This project is licensed under the MIT License. See the [LICENSE.md](./LICENSE.md) file for details.
