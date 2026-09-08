<?php

declare(strict_types=1);

namespace CoolMS\Field\Contract;

use CoolMS\Field\VO\FieldMetadata;

/**
 * Reads per-property semantic metadata from a given entity class.
 *
 * Implemented by FieldMetaReader (Infrastructure\Reflection).
 * Declared here so Infrastructure consumers in other modules can depend on
 * a Domain contract instead of crossing Infra->Infra module boundaries.
 */
interface FieldMetaReaderInterface
{
    /**
     * Read metadata for all public non-static properties of $class.
     *
     * @param class-string $class
     *
     * @return array<string, FieldMetadata> indexed by property name
     */
    public function readAll(string $class): array;
}
