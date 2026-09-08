<?php

declare(strict_types=1);

namespace CoolMS\Field\Contract;

use CoolMS\Field\Entity\Definition;

/**
 * Persistence strategy for field metadata overrides on static (PHP-registered) entities.
 *
 * In dev: FileFieldOverrideStorage writes to config/modules/{module}/fields/{alias}/{field}.yaml
 * In prod: DbFieldOverrideStorage writes to coolms_field_definitions via the ORM.
 * Runtime types always use the DB path regardless of environment.
 *
 * $data shape (same as a single-field YAML block):
 *   type, label, sortOrder, locked, isRequired, formConfig, apiConfig,
 *   serializerConfig, validationRules, maxLength, placeholder, min, max,
 *   step, precision, selectOptions, multiple, relationTarget,
 *   relationCardinality, relationWidget, relationFilter,
 *   relationDisplayField, dateFormat, dateMin, dateMax,
 *   securityRead, securityWrite.
 * Only non-null / non-empty values need to be present.
 *
 * save() returns the persisted Definition entity when storage writes to the DB
 * (DbFieldOverrideStorage / prod), or null when it writes only to a YAML file
 * (FileFieldOverrideStorage / dev).  Callers that need an IRI-capable resource
 * must handle the null case explicitly.
 */
interface FieldOverrideStorageInterface
{
    /**
     * @param array<string, mixed> $data
     *
     * @return Definition|null persisted entity (DB path) or null (file path)
     */
    public function save(string $alias, string $fieldName, array $data): ?Definition;

    public function delete(string $alias, string $fieldName): void;
}
