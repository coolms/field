<?php

declare(strict_types=1);

namespace CoolMS\Field\Service;

use CoolMS\Core\Field\FieldConfigProviderInterface;
use CoolMS\Field\Repository\DefinitionRepositoryInterface;
use CoolMS\Field\VO\FieldMetadata;
use CoolMS\Field\Contract\FieldMetaReaderInterface;

/**
 * Resolves merged FieldMetadata for a static entity class via a 4-layer pipeline.
 *
 * Layer 1 -- PHP #[Assert\*] attributes (via the field-meta reader)
 * Layer 2 -- PHP #[FieldMeta] attributes (via the field-meta reader)
 * Layer 3 -- Module config files via FieldConfigProviderInterface (YAML/XML/PHP)
 * Layer 4 -- DB Definition rows (DefinitionRepository) -- always wins
 *
 * Results are cached in-memory per class+alias per request.
 */
final class FieldMetadataRegistry
{
    /**
     * @var array<string, array<string, FieldMetadata>>
     */
    private array $cache = [];

    /**
     * Cache for hasExternalOverride() -- keyed by entityAlias, value is a map of
     * field name -> true for fields that have a Layer 3 or Layer 4 override.
     *
     * @var array<string, array<string, bool>>
     */
    private array $externalOverrideCache = [];

    /**
     * Cache for hasFileOverride() -- keyed by entityAlias, value is a map of
     * field name -> true for fields that have a Layer 3 (config file) override only.
     *
     * @var array<string, array<string, bool>>
     */
    private array $fileOverrideCache = [];

    /**
     * @param iterable<FieldConfigProviderInterface> $configProviders
     */
    public function __construct(
        private readonly FieldMetaReaderInterface $reader,
        private readonly DefinitionRepositoryInterface $repository,
        private readonly iterable $configProviders = [],
    ) {
    }

    /**
     * @param class-string $class
     * @param string       $entityAlias Used for Layer 3 (config files) + Layer 4 (DB)
     *
     * @return array<string, FieldMetadata> indexed by property name
     */
    public function getAll(string $class, string $entityAlias): array
    {
        $cacheKey = $class . '::' . $entityAlias;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        // Layers 1+2: PHP attributes via Reflection
        $fields = $this->reader->readAll($class);
        // Layer 3: module config files
        foreach ($this->configProviders as $provider) {
            $config = $provider->getConfig($entityAlias);
            if (null === $config) {
                continue;
            }
            foreach ($config as $name => $overrides) {
                // If no PHP property / #[FieldMeta] exists for this name (e.g. extras
                // fields stored in the JSON column), synthesise a blank base so the
                // field still appears to all callers (DoctrineEntitySchemaProvider etc.).
                $fields[$name] = $this->applyOverrides(
                    $fields[$name] ?? new FieldMetadata(
                        name: $name,
                        label: ucfirst($name),
                        formType: null,
                        formOptions: [],
                        showInForm: true,
                        constraints: [],
                        normalizationGroups: [],
                        denormalizationGroups: [],
                        securityRead: [],
                        securityWrite: [],
                        sortOrder: null,
                    ),
                    $overrides,
                );
            }
        }
        // Layer 4: DB Definition rows -- always wins
        foreach ($this->repository->findByEntityAlias($entityAlias) as $def) {
            $name = $def->name;
            $base = $fields[$name] ?? new FieldMetadata(
                name: $name,
                label: $def->label,
                formType: null,
                formOptions: [],
                showInForm: true,
                constraints: [],
                normalizationGroups: [],
                denormalizationGroups: [],
                securityRead: [],
                securityWrite: [],
                sortOrder: null,
            );
            $fields[$name] = new FieldMetadata(
                name: $name,
                label: '' !== $def->label ? $def->label : $base->label,
                formType: $def->formType ?? $base->formType,
                formOptions: [] !== $def->formOptions ? $def->formOptions : $base->formOptions,
                showInForm: $def->showInForm,
                constraints: [] !== $def->validationRules
                    ? $def->validationRules
                    : $base->constraints,
                normalizationGroups: [] !== $def->normalizationGroups
                    ? $def->normalizationGroups
                    : $base->normalizationGroups,
                denormalizationGroups: [] !== $def->denormalizationGroups
                    ? $def->denormalizationGroups
                    : $base->denormalizationGroups,
                securityRead: [] !== $def->securityRead
                    ? $def->securityRead
                    : $base->securityRead,
                securityWrite: [] !== $def->securityWrite
                    ? $def->securityWrite
                    : $base->securityWrite,
                sortOrder: $def->sortOrder,
                hasMeta: $base->hasMeta,
            );
        }

        return $this->cache[$cacheKey] = $fields;
    }

    /**
     * Returns true when $fieldName has a Layer 3 (YAML/config) or Layer 4 (DB) override
     * for the given entity alias -- i.e. it was externally configured beyond PHP attributes.
     *
     * Results are cached per entityAlias for the lifetime of the request.
     *
     * @param class-string $class
     */
    public function hasExternalOverride(string $class, string $entityAlias, string $fieldName): bool
    {
        if (!isset($this->externalOverrideCache[$entityAlias])) {
            $this->externalOverrideCache[$entityAlias] = $this->buildExternalOverrideMap($entityAlias);
        }

        return $this->externalOverrideCache[$entityAlias][$fieldName] ?? false;
    }

    /**
     * Returns true when $fieldName has a Layer 3 (YAML/config) override for the
     * given entity alias -- i.e. a file-based override written by FileFieldOverrideStorage.
     * Layer 4 (DB) is intentionally excluded; use hasExternalOverride() for both combined.
     *
     * Results are cached per entityAlias for the lifetime of the request.
     */
    public function hasFileOverride(string $entityAlias, string $fieldName): bool
    {
        if (!isset($this->fileOverrideCache[$entityAlias])) {
            $this->fileOverrideCache[$entityAlias] = $this->buildFileOverrideMap($entityAlias);
        }

        return $this->fileOverrideCache[$entityAlias][$fieldName] ?? false;
    }

    public function invalidate(?string $cacheKey = null): void
    {
        if (null === $cacheKey) {
            $this->cache = [];
        } else {
            unset($this->cache[$cacheKey]);
        }
        // These caches are keyed by entityAlias only, so we cannot do a precise
        // lookup by the class::alias format -- clear all entries to be safe.
        $this->externalOverrideCache = [];
        $this->fileOverrideCache = [];
    }

    /**
     * Scans Layer 3 (config file providers only) to collect all field names that
     * have a file-based override for the given entity alias.
     *
     * @return array<string, bool>
     */
    private function buildFileOverrideMap(string $entityAlias): array
    {
        $map = [];

        foreach ($this->configProviders as $provider) {
            $config = $provider->getConfig($entityAlias);
            if (null === $config) {
                continue;
            }
            foreach (array_keys($config) as $name) {
                $map[$name] = true;
            }
        }

        return $map;
    }

    /**
     * Scans Layer 3 (config providers) and Layer 4 (DB) to collect all field names
     * that have an external override for the given entity alias.
     *
     * @return array<string, bool>
     */
    private function buildExternalOverrideMap(string $entityAlias): array
    {
        $map = [];

        // Layer 3: YAML / PHP / XML config files via FieldConfigProviderInterface
        foreach ($this->configProviders as $provider) {
            $config = $provider->getConfig($entityAlias);
            if (null === $config) {
                continue;
            }
            foreach (array_keys($config) as $name) {
                $map[$name] = true;
            }
        }

        // Layer 4: DB FieldDefinition records
        foreach ($this->repository->findByEntityAlias($entityAlias) as $fd) {
            $map[$fd->name] = true;
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function applyOverrides(FieldMetadata $base, array $overrides): FieldMetadata
    {
        return new FieldMetadata(
            name: $base->name,
            label: isset($overrides['label']) ? (string) $overrides['label'] : $base->label,
            formType: isset($overrides['formType']) ? (string) $overrides['formType'] : $base->formType,
            formOptions: isset($overrides['formOptions']) ? (array) $overrides['formOptions'] : $base->formOptions,
            showInForm: isset($overrides['showInForm']) ? (bool) $overrides['showInForm'] : $base->showInForm,
            constraints: isset($overrides['constraints']) ? (array) $overrides['constraints'] : $base->constraints,
            normalizationGroups: isset($overrides['normalizationGroups']) ? (array) $overrides['normalizationGroups'] : $base->normalizationGroups,
            denormalizationGroups: isset($overrides['denormalizationGroups']) ? (array) $overrides['denormalizationGroups'] : $base->denormalizationGroups,
            securityRead: isset($overrides['securityRead']) ? (array) $overrides['securityRead'] : $base->securityRead,
            securityWrite: isset($overrides['securityWrite']) ? (array) $overrides['securityWrite'] : $base->securityWrite,
            sortOrder: isset($overrides['sortOrder']) ? (int) $overrides['sortOrder'] : $base->sortOrder,
            hasMeta: $base->hasMeta,
        );
    }
}
