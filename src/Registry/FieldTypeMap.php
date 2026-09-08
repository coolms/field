<?php

declare(strict_types=1);

namespace CoolMS\Field\Registry;

use CoolMS\Field\Entity\Definition;

/**
 * The ONE place a {@see Definition::$type} word is translated into the storage
 * declaration the schema layer builds a column from.
 *
 * ## Two axes, one column
 *
 * `$type` answers a UI question -- "what does the operator see and type into?" --
 * and a storage question -- "what shape is this value on disk?". Those are not
 * the same question, and the UI one is strictly finer: `textarea` and `text`
 * are different widgets over the same TEXT column; so are `select`, `relation`,
 * `tags`, `taxonomy` and `image`. Collapsing `$type` to the storage axis would
 * throw all of that away -- {@see FieldWidgetRegistry::widgetForType()} keys off
 * this exact string, as do the admin schema editor and the content field panels.
 *
 * So `$type` stays the WIDGET vocabulary, and this class is the documented
 * translation to the DATA vocabulary ({@see Definition::DATA_TYPES}). That is
 * the whole contract: authoring surfaces read `$type`, the schema layer reads
 * this map, and nothing else invents a third reading of the word.
 *
 * ## Why a nullable answer exists
 *
 * {@see toDataType()} returns `null` for a word it does not know, and callers
 * must treat that as "I cannot tell you" rather than substituting a guess.
 * This is load-bearing: before the map existed, `mapTypeToSql()` answered TEXT
 * for every unrecognised word via its `default` arm, so a `float` field that
 * came back from the admin as `number` was indistinguishable from an author who
 * had genuinely asked for text -- and rebuilding on that reading took a DOUBLE
 * PRECISION column to TEXT. A `null` a caller has to handle cannot be mistaken
 * for a real answer the way `'string'` could.
 *
 * {@see toDataTypeOrString()} is the deliberate exception, for the one caller
 * that is CREATING a column rather than replacing one: there is nothing to
 * downgrade, TEXT holds any JSON scalar, and refusing would leave the field
 * with no column at all. Creating on a guess is recoverable; rebuilding on one
 * is not.
 *
 * ## Legacy spellings
 *
 * `$type` also still holds a handful of DATA words written before the axes were
 * separated -- `string`, `int`, `bool`, `float`, `datetime`. They map to
 * themselves so existing rows keep working, but they are NOT offered by the
 * schema editor: `text`, `integer`, `boolean`, `number` and `date` are their
 * canonical widget spellings, and Version20260807120000 rewrote the rows that
 * used them.
 */
final class FieldTypeMap
{
    /**
     * Widget word => the {@see Definition::DATA_TYPES} member it stores as.
     *
     * Non-obvious entries:
     *  - `number` => `float`, never `int`. The widget cannot tell them apart, and
     *    a DOUBLE PRECISION column holds every integer an INTEGER one would
     *    while the reverse truncates. `integer` exists precisely so an author
     *    who needs the exact type can say so. This matches the sibling map in
     *    {@see \App\Scaffolding\Infrastructure\Doctrine\Mapping\AttributeMappingDriver},
     *    which has resolved the same ambiguity the same way since the generator
     *    was written.
     *  - `date` => `datetime`, the only temporal member of DATA_TYPES. Note this
     *    does NOT mean a date field gets a TIMESTAMP column: PostgreSQL cannot
     *    build a temporal generated column at all, so its platform manager maps
     *    `datetime` to TEXT and says why. That is the platform's call to make,
     *    which is exactly why it is made there and not here -- this map states
     *    what the value IS, and the platform states what it can store it as.
     *  - `checkbox` and `boolean` are one type under two spellings. `checkbox`
     *    is the widget kind the front-end registry resolves (module field YAML
     *    writes it); `boolean` is the word the schema editor offers. Renaming
     *    either would break a live surface, so both stay legal here.
     *  - `tags`, `taxonomy` => `json`. Both hold an ARRAY in `extras`, so
     *    `extras->>'x'` yields serialised JSON text rather than prose. `json`
     *    maps to TEXT like `string` does -- the column is identical -- but the
     *    word records that the contents are not a sentence.
     *  - `relation`, `image`, `select` => `string`. Each stores one scalar
     *    reference: a UUID, a path, an option key.
     *
     * @var array<string, string>
     */
    public const array WIDGET_TO_DATA = [
        // Text-shaped widgets.
        'text' => 'string',
        'textarea' => 'string',
        'richtext' => 'string',
        'localizedText' => 'string',
        'localizedTextarea' => 'string',
        'select' => 'string',
        'relation' => 'string',
        'image' => 'string',
        // Structured payloads: an array or object lives under this key.
        'tags' => 'json',
        'taxonomy' => 'json',
        'json' => 'json',
        // Numeric.
        'number' => 'float',
        'integer' => 'int',
        'money' => 'money',
        // Boolean, under both live spellings.
        'boolean' => 'bool',
        'checkbox' => 'bool',
        // Temporal.
        'date' => 'datetime',
    ];

    /**
     * The subset of {@see WIDGET_TO_DATA} an operator may pick from -- the
     * schema editor's Type dropdown and `coolms:field:definition:create`.
     *
     * Smaller than the map because the map must keep accepting words the
     * authoring surfaces should not hand out:
     *  - `checkbox` -- the widget kind module field YAML writes and the
     *    front-end registry resolves; `boolean` is its picker spelling and the
     *    two store identically, so offering both would ask the operator to
     *    choose between synonyms.
     *  - the legacy DATA spellings (`string`, `int`, `bool`, `float`,
     *    `datetime`), which map to themselves for rows that predate the split.
     *
     * Order is the dropdown's order: commonest first, then numeric, temporal,
     * choice, and module-contributed widgets.
     *
     * @var list<string>
     */
    public const array AUTHORABLE = [
        'text', 'textarea', 'richtext', 'localizedText', 'localizedTextarea',
        'number', 'integer', 'money',
        'boolean', 'date',
        'select', 'relation',
        'tags', 'taxonomy', 'image', 'json',
    ];

    /**
     * The storage declaration for a `Definition::$type` word, or `null` when the
     * word belongs to neither vocabulary.
     *
     * A `null` means "this is not a storage declaration" -- NOT "store it as
     * text". Callers that would destroy something on a wrong answer (dropping
     * and rebuilding a column) must decline; see the class docblock.
     *
     * @return string|null one of {@see Definition::DATA_TYPES}
     */
    public static function toDataType(string $type): ?string
    {
        if (isset(self::WIDGET_TO_DATA[$type])) {
            return self::WIDGET_TO_DATA[$type];
        }

        // A legacy DATA word already in the column: it IS the answer.
        return in_array($type, Definition::DATA_TYPES, true) ? $type : null;
    }

    /**
     * As {@see toDataType()}, but falls back to `string` for an unknown word.
     *
     * ONLY for the path that creates a column that does not exist yet, where
     * the alternative is no column at all and there is nothing to downgrade.
     * Never use this to decide whether an EXISTING column should be replaced.
     */
    public static function toDataTypeOrString(string $type): string
    {
        return self::toDataType($type) ?? 'string';
    }
}
