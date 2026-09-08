<?php

declare(strict_types=1);

namespace CoolMS\Field\Tests\Registry;

use CoolMS\Field\Entity\Definition;
use CoolMS\Field\Registry\FieldTypeMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The map is the single translation point between the two vocabularies
 * `Definition::$type` has held, so most of what matters here is structural: that
 * it lands inside `DATA_TYPES`, that the surfaces offering type words offer only
 * words it can place, and that a word it cannot place is reported as such
 * instead of defaulted to text.
 */
class FieldTypeMapTest extends TestCase
{
    /** @return iterable<string, array{non-empty-string, non-empty-string}> */
    public static function loadBearingTranslations(): iterable
    {
        yield 'checkbox is a boolean (noindex, hasPublishedVariant)' => ['checkbox', 'bool'];
        yield 'boolean is the same type under the picker spelling' => ['boolean', 'bool'];
        yield 'date is the only temporal type there is (publishDate)' => ['date', 'datetime'];
        yield 'number widens rather than truncating' => ['number', 'float'];
        yield 'integer is how an author asks for the exact type' => ['integer', 'int'];
        yield 'money stays fixed-point' => ['money', 'money'];
        yield 'textarea is a widget over a text column' => ['textarea', 'string'];
        yield 'relation stores one scalar reference' => ['relation', 'string'];
        yield 'tags holds an array, not prose' => ['tags', 'json'];
    }

    /** @return iterable<string, array{non-empty-string}> */
    public static function legacyDataWords(): iterable
    {
        foreach (Definition::DATA_TYPES as $word) {
            yield $word => [$word];
        }
    }

    /**
     * The invariant `mapTypeToSql()` depends on. Every translation must land on
     * a DATA_TYPES member; a value outside it would reach the platform's
     * `default` arm and silently become TEXT -- the failure the map exists to
     * stop, reintroduced from inside the fix.
     */
    public function testEveryTranslationLandsInsideTheDataVocabulary(): void
    {
        foreach (FieldTypeMap::WIDGET_TO_DATA as $widget => $data) {
            self::assertContains(
                $data,
                Definition::DATA_TYPES,
                sprintf('%s translates to "%s", which mapTypeToSql() does not honour.', $widget, $data),
            );
        }
    }

    /**
     * The drift guard between the vocabulary and what the authoring surfaces
     * hand out. A word offered but unmapped lands in `$type` as an unplaceable
     * declaration and the field gets a TEXT column whatever it meant.
     */
    public function testEveryAuthorableWordIsTranslatable(): void
    {
        foreach (FieldTypeMap::AUTHORABLE as $word) {
            self::assertNotNull(
                FieldTypeMap::toDataType($word),
                sprintf('The schema editor offers "%s" but the map cannot place it.', $word),
            );
        }
    }

    /**
     * AUTHORABLE is a subset by design, not by accident: `checkbox` is the
     * widget kind module YAML writes (`boolean` is its picker spelling) and the
     * legacy storage words map to themselves for old rows. Pin the exclusions so
     * a new widget added to the map without a decision about the dropdown shows
     * up here rather than being silently unreachable -- or silently offered.
     */
    public function testTheAuthorableSubsetExcludesOnlyTheKnownSynonyms(): void
    {
        $notOffered = array_values(array_diff(
            array_keys(FieldTypeMap::WIDGET_TO_DATA),
            FieldTypeMap::AUTHORABLE,
        ));

        self::assertSame(['checkbox'], $notOffered);
    }

    /**
     * The three rows measured wrong on the live database, plus the ambiguity
     * that made `number` dangerous. Spelled out one by one because each is a
     * decision, not a derivation: `number` widens to float rather than
     * truncating to int, `date` uses the only temporal member there is, and
     * `checkbox` is a boolean under its widget name.
     *
     * @param non-empty-string $widget
     * @param non-empty-string $expected
     */
    #[DataProvider('loadBearingTranslations')]
    public function testTranslatesTheLoadBearingWords(string $widget, string $expected): void
    {
        self::assertSame($expected, FieldTypeMap::toDataType($widget));
    }

    /**
     * The legacy storage spellings still in the column map to themselves, so a
     * row Version20260807120000 has not reached keeps working rather than
     * reporting its type as unplaceable and freezing its column.
     *
     * @param non-empty-string $legacy
     */
    #[DataProvider('legacyDataWords')]
    public function testALegacyStorageWordIsItsOwnTranslation(string $legacy): void
    {
        self::assertSame($legacy, FieldTypeMap::toDataType($legacy));
    }

    /**
     * A word in neither vocabulary must come back null. This is the whole reason
     * the return type is nullable: a caller that would drop a column has to be
     * able to tell "not a storage declaration" from "store it as text", and
     * before the map both arrived as TEXT.
     */
    public function testAWordInNeitherVocabularyTranslatesToNull(): void
    {
        self::assertNull(FieldTypeMap::toDataType('colourPicker'));
        self::assertNull(FieldTypeMap::toDataType(''));
        // Case matters -- `$type` is compared verbatim everywhere else too.
        self::assertNull(FieldTypeMap::toDataType('Checkbox'));
    }

    /**
     * The create-path variant guesses text, and ONLY it may: there is no column
     * to downgrade. Asserted separately from the nullable form so a caller can
     * never be refactored from one to the other without a test failing.
     */
    public function testTheDefaultingVariantGuessesTextOnlyForUnknownWords(): void
    {
        self::assertSame('string', FieldTypeMap::toDataTypeOrString('colourPicker'));
        // A word it CAN place is still translated, not defaulted.
        self::assertSame('bool', FieldTypeMap::toDataTypeOrString('checkbox'));
        self::assertSame('datetime', FieldTypeMap::toDataTypeOrString('date'));
    }
}
