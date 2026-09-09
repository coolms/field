# Changelog

All notable changes to `coolms/field` are recorded here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning is described in `CONTRIBUTING.md` -- read it before assuming what a
major number means here.

Unlike the packages extracted before it, every entry in this file was written in
the same commit as the change it describes. Nothing here is reconstructed.

## Unreleased

### Added

- The package skeleton: `composer.json`, licence, CI workflow, PHPUnit
  configuration and contribution guide, matching the shape `coolms/entity`
  established. No source has moved yet.

### Notes

- !! **The domain is not yet framework-free.** `Field\Domain\Entity\Definition`
  carries `Doctrine\ORM\Mapping` attributes and `Doctrine\DBAL\Types\Types`,
  which is why `doctrine/orm` is absent from `require` here: the mapping is to
  move to `coolms/field-doctrine` before this package is publishable, in line
  with `coolms/entity`, whose `src/` has zero Doctrine imports. Recorded rather
  than quietly satisfied by adding the dependency, because adding it would make
  every consumer of the vocabulary take an ORM they may not use.
