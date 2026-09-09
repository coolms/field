# Changelog

All notable changes to `coolms/field` are recorded here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning is described in `CONTRIBUTING.md` -- read it before assuming what a
major number means here.

Unlike the packages extracted before it, every entry in this file was written in
the same commit as the change it describes. Nothing here is reconstructed.

## 2.0.0-alpha1 - 2026-09-10

**A pre-release. It carries no compatibility promise**, which is the honest
statement of where the platform is: the shape is still moving, and a stable tag
would be a promise that cannot be kept yet.

Composer will not install it under default stability. Set

```json
"minimum-stability": "alpha",
"prefer-stable": true
```

in your root `composer.json`, then:

```
composer require coolms/field:^2.0
```

`prefer-stable` keeps every other dependency of yours on its newest stable
release, so this loosening applies to what actually needs it and nothing else.

!! **A per-package stability flag is not enough.** `composer require
coolms/field:^2.0@alpha` admits the alpha of the package it names and **nothing
behind it**, so the siblings it pulls in still fail to resolve -- and composer
reports that against the sibling rather than against what you asked for.

### Added

The field-definition vocabulary: the definition model and its value objects, the
field-type and widget registries, and the ports a host application implements to
store and resolve them.

### The domain is framework-free, and that was a condition of this release

An earlier note in this file recorded that `Definition` carried
`Doctrine\ORM\Mapping` attributes and `Doctrine\DBAL\Types\Types`, and that the
mapping had to move to `coolms/field-doctrine` before this package could be
published.

It has. `src/` imports Doctrine **zero times** -- the same count as
`coolms/core` and `coolms/entity`, which is the standard that note named -- and
the mapping lives in `coolms/field-doctrine` as `src/mapping/Definition.orm.xml`.

That is why `doctrine/orm` is absent from `require` here, and why it stays
absent: adding it would make every consumer of the vocabulary take an ORM it may
not use.

