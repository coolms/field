<?php

declare(strict_types=1);

namespace CoolMS\Field\VO;

/**
 * Resolved semantic metadata for a single entity property.
 */
final readonly class FieldMetadata
{
    /**
     * @param array<string, mixed> $constraints
     * @param string[]             $normalizationGroups
     * @param string[]             $denormalizationGroups
     * @param string[]             $securityRead
     * @param string[]             $securityWrite
     * @param array<string, mixed> $formOptions
     */
    public function __construct(
        public string $name,
        public string $label,
        public ?string $formType,
        public array $formOptions,
        public bool $showInForm,
        public array $constraints,
        public array $normalizationGroups,
        public array $denormalizationGroups,
        public array $securityRead,
        public array $securityWrite,
        public ?int $sortOrder,
        public bool $private = false,
        /** True when the property has an explicit #[FieldMeta] attribute on the PHP class. */
        public bool $hasMeta = false,
    ) {
    }
}
