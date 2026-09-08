<?php

declare(strict_types=1);

namespace CoolMS\Field\Entity;

use CoolMS\Field\Repository\DefinitionRepositoryInterface;
use CoolMS\Core\Attribute\ClassMeta;
use CoolMS\Core\Identifier\IdentifierProviderTrait;
use CoolMS\Core\Translation\Translatable;
use CoolMS\Entity\Traits\OrderProviderTrait;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A runtime field definition attached to an entity type, describing its type, validation, and display config.
 */
#[ClassMeta(label: 'Field Definition')]
// F5.b Phase 5 -- the display `label` (stored in options['label']) is
// localizable; so is each select option's `label`. Short class name
// derives to `definition`, domain to `field` (namespace segment after
// App\). Catalogue keys: `definition.{uuid}.label` for the field label,
// `definition.{uuid}.option.{value}.label` for a per-option label (the
// option `value` is the stable local id; see the inline-child seam on
// LabelResolverInterface::keyForChild()). Reads go through LabelResolver
// in DefinitionProvider; with no XLIFF override the raw value is served
// verbatim (behaviour-preserving). Options stay inline {value,label}
// arrays -- the child seam translates them WITHOUT promoting them to
// entities.
#[Translatable(fields: ['label'], children: [self::TRANSLATABLE_OPTION_CHILD => ['label']])]
final class Definition implements DefinitionInterface
{
    use IdentifierProviderTrait {
        IdentifierProviderTrait::__construct as private __identityConstruct;
    }
    use OrderProviderTrait;

    /**
     * childKind token for the LabelResolver inline-child seam (F5.b
     * Phase 5): each select option's `label` is translatable, keyed by
     * the option `value`. Used in the #[Translatable(children: ...)]
     * declaration above and by DefinitionProvider when resolving option
     * labels. Single source of truth so read + write never skew.
     */
    public const string TRANSLATABLE_OPTION_CHILD = 'option';

    /**
     * The DATA types {@see \CoolMS\Entity\Doctrine\Schema\Platform\PlatformSchemaManagerInterface::mapTypeToSql()}
     * is willing to answer for -- the vocabulary a {@see $type} word is
     * translated INTO before a column is built from it.
     *
     * This is not the vocabulary `$type` is written in. `$type` holds WIDGET
     * words -- see the property's own docblock -- and
     * {@see \CoolMS\Field\Registry\FieldTypeMap} is the single point that
     * translates one into the other. Nothing outside that class should compare
     * `$type` against this list: `mapTypeToSql()` answers via a `default` arm
     * and so cannot tell "the author asked for text" from "this word is from
     * the other vocabulary", and reading the second as the first DOWNGRADES a
     * good column.
     *
     * Five members -- `string`, `int`, `bool`, `float`, `datetime` -- are also
     * legacy spellings still present in `$type` on old rows. FieldTypeMap maps
     * them to themselves; the schema editor no longer offers them.
     *
     * @var list<string>
     */
    public const array DATA_TYPES = ['string', 'int', 'bool', 'float', 'money', 'datetime', 'json'];

    /**
     * Entity alias (= DynamicEntityType slug) this field belongs to.
     * Kept denormalized for ExtrasFieldMappingDriver boot-time DBAL query performance:
     *   SELECT name, type FROM coolms_field_definitions WHERE entity_alias = ?
     * The node_id column is retained in the DB for CASCADE DELETE, but the ORM
     * association was removed -- entityAlias is set directly by callers.
     */
    #[Assert\NotBlank]
    public string $entityAlias = '';

    /**
     * System field name (used as a JSON key and in code).
     */
    #[Assert\Regex('/^[a-z0-9_]+$/')]
    #[Assert\Length(
        max: 61,
        maxMessage: 'Field name must not exceed 61 chars to fit PostgreSQL v_* generated column identifier limit (63 chars total).',
    )]
    public string $name = '';

    /**
     * The field's WIDGET type -- what the operator picks in the schema editor and
     * what every rendering surface resolves a control from
     * ({@see \CoolMS\Field\Registry\FieldWidgetRegistry::widgetForType()},
     * the admin field panels, the DynamicForm builder).
     *
     * It is NOT the storage type. `textarea`, `select`, `relation`, `tags` and
     * `image` are five different widgets over one TEXT column; the widget axis
     * is strictly finer than the data axis, which is why the two are not one
     * word. {@see \CoolMS\Field\Registry\FieldTypeMap} translates this into
     * a {@see DATA_TYPES} member, and it is the ONLY thing that may -- see its
     * docblock for the vocabulary, the legacy spellings still stored here, and
     * why an unknown word must not be defaulted to text by a caller that would
     * rebuild a column on the answer.
     */
    public string $type = 'string';

    /**
     * Validation configuration (Symfony Constraints as an array)
     * Example: ['NotBlank' => null, 'Range' => ['min' => 10]].
     *
     * @var array<string, mixed>
     */
    public array $validationRules = [];

    /**
     * Additional configuration options (label, help text, default value, options for select).
     *
     * @var array<string, mixed>
     */
    public array $options = [];

    /**
     * Serializer groups used when reading (normalizing) this field.
     * Example: ['field:definition:read', 'field:definition:list'].
     *
     * @var array<string, mixed>
     */
    public array $normalizationGroups = [];

    /**
     * Serializer groups used when writing (denormalizing) this field.
     * Example: ['field:definition:write'].
     *
     * @var array<string, mixed>
     */
    public array $denormalizationGroups = [];

    /**
     * Symfony Serializer configuration for this field.
     * Supported keys: 'serialized_name' (string), 'ignore' (bool), 'normalizer' (class-string).
     *
     * @var array<string, mixed>
     */
    public array $serializerConfig = [];

    /**
     * Symfony Form component configuration for this field.
     * Supported keys: 'form_type' (class-string), 'form_options' (array), 'show_in_form' (bool).
     *
     * @var array<string, mixed>
     */
    public array $formConfig = [];

    /**
     * API Platform metadata for this field.
     * Supported keys: 'filterable' (bool), 'sortable' (bool), 'searchable' (bool).
     *
     * @var array<string, mixed>
     */
    public array $apiConfig = [];

    /**
     * Allows automatically generating a human-readable label if not specified.
     */
    public string $label {
        get => $this->options['label'] ?? ucfirst($this->name);
        set {
            $this->options['label'] = $value;
        }
    }

    /**
     * Helper to check / set whether the field is required.
     *
     * Derived from validationRules['NotBlank'] -- no separate DB column needed.
     * Setting true adds 'NotBlank' => null to validationRules (??= keeps existing options).
     * Setting false removes 'NotBlank' from validationRules.
     */
    public bool $isRequired {
        get => array_key_exists('NotBlank', $this->validationRules);
        set {
            if ($value) {
                $this->validationRules['NotBlank'] ??= null;
            } else {
                unset($this->validationRules['NotBlank']);
            }
        }
    }

    /**
     * Whether this field is locked (cannot be deleted via API).
     * Stored in options['locked']; no migration needed.
     */
    public bool $locked {
        get => (bool) ($this->options['locked'] ?? false);
        set {
            $this->options['locked'] = $value;
        }
    }

    public ?int $maxLength {
        get => isset($this->options['maxLength']) ? (int) $this->options['maxLength'] : null;
        set {
            $this->options['maxLength'] = $value;
        }
    }
    public ?string $placeholder {
        get => $this->options['placeholder'] ?? null;
        set {
            $this->options['placeholder'] = $value;
        }
    }
    public ?float $min {
        get => isset($this->options['min']) ? (float) $this->options['min'] : null;
        set {
            $this->options['min'] = $value;
        }
    }
    public ?float $max {
        get => isset($this->options['max']) ? (float) $this->options['max'] : null;
        set {
            $this->options['max'] = $value;
        }
    }
    public ?float $step {
        get => isset($this->options['step']) ? (float) $this->options['step'] : null;
        set {
            $this->options['step'] = $value;
        }
    }
    public ?int $precision {
        get => isset($this->options['precision']) ? (int) $this->options['precision'] : null;
        set {
            $this->options['precision'] = $value;
        }
    }

    /**
     * @var array<array{value: string, label: string}>
     */
    public array $selectOptions {
        get => (array) ($this->options['selectOptions'] ?? []);
        set {
            $this->options['selectOptions'] = $value;
        }
    }

    public bool $multiple {
        get => (bool) ($this->options['multiple'] ?? false);
        set {
            $this->options['multiple'] = $value;
        }
    }
    public ?string $relationTarget {
        get => $this->options['relationTarget'] ?? null;
        set {
            $this->options['relationTarget'] = $value;
        }
    }

    /**
     * 'one' | 'many'.
     */
    public string $relationCardinality {
        get => $this->options['relationCardinality'] ?? 'one';
        set {
            $this->options['relationCardinality'] = $value;
        }
    }

    /**
     * 'select' | 'autocomplete' | 'tree'.
     */
    public string $relationWidget {
        get => $this->options['relationWidget'] ?? 'select';
        set {
            $this->options['relationWidget'] = $value;
        }
    }

    /**
     * RQL filter string e.g. "mimeType sw 'image/'".
     */
    public ?string $relationFilter {
        get => $this->options['relationFilter'] ?? null;
        set {
            $this->options['relationFilter'] = $value;
        }
    }

    public ?string $relationDisplayField {
        get => $this->options['relationDisplayField'] ?? null;
        set {
            $this->options['relationDisplayField'] = $value;
        }
    }

    /**
     * ISO 8601 format string e.g. 'Y-m-d'.
     */
    public ?string $dateFormat {
        get => $this->options['dateFormat'] ?? null;
        set {
            $this->options['dateFormat'] = $value;
        }
    }

    public ?string $dateMin {
        get => $this->options['dateMin'] ?? null;
        set {
            $this->options['dateMin'] = $value;
        }
    }
    public ?string $dateMax {
        get => $this->options['dateMax'] ?? null;
        set {
            $this->options['dateMax'] = $value;
        }
    }

    /**
     * @var string[] roles allowed to READ this field
     */
    public array $securityRead {
        get => (array) ($this->options['security']['read'] ?? []);
        set(array $v) {
            $this->options['security']['read'] = $v;
        }
    }

    /**
     * @var string[] roles allowed to WRITE this field
     */
    public array $securityWrite {
        get => (array) ($this->options['security']['write'] ?? []);
        set(array $v) {
            $this->options['security']['write'] = $v;
        }
    }

    public ?string $serializedName {
        get => $this->serializerConfig['serialized_name'] ?? null;
    }
    public bool $ignore {
        get => $this->serializerConfig['ignore'] ?? false;
    }
    public ?string $formType {
        get => $this->formConfig['form_type'] ?? null;
    }

    /** @var array<string, mixed> */
    public array $formOptions {
        get => $this->formConfig['form_options'] ?? [];
    }

    public bool $showInForm {
        get => $this->formConfig['show_in_form'] ?? true;
    }
    public bool $isFilterable {
        get => $this->apiConfig['filterable'] ?? false;
    }
    public bool $isSortable {
        get => $this->apiConfig['sortable'] ?? false;
    }
    public bool $isSearchable {
        get => $this->apiConfig['searchable'] ?? false;
    }

    /**
     * Auto-generates a UUID v7 ID on construction.
     * All other properties have sensible defaults and are set via property access.
     */
    public function __construct()
    {
        $this->__identityConstruct(Uuid::v7());
    }
}
