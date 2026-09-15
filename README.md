# coolms/field

Field definitions for the CoolMS platform: the definition model and its value
objects, the field-type and widget registries, and the ports a host application
implements to store and resolve them.

A *field definition* describes one configurable field — its name, type, widget,
validation and presentation — so an application can offer fields that were not
declared in PHP. The definitions themselves are data; this package owns their
shape and their vocabulary, not their storage.

## Installation

```bash
composer require coolms/field
```

## What is here, and what is not

This package is framework-free by design, in line with `coolms/core` and
`coolms/entity`: it declares contracts and value objects and leaves wiring to
the packages layered above it.

| Package | Holds |
| --- | --- |
| `coolms/field` | the definition model, value objects, registries and ports |
| `coolms/field-doctrine` | the ORM mapping and repository implementation |
| `coolms/field-bundle` | the Symfony bundle, DI extension, API resources, console commands, form types and validators |

Depending on `coolms/field` alone gives you the vocabulary without committing to
a database or to Symfony.

## Status

Part of the CoolMS platform. The package follows the platform's
release cadence: while the 2.0 generation is settling there is no stable tag,
and `dev-develop` is aliased to `2.0.x-dev`.

## Licence

MIT. See [LICENSE](LICENSE).
