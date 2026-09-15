<?php

declare(strict_types=1);

namespace CoolMS\Field\Tests\Entity;

use CoolMS\Field\Entity\Definition;
use PHPUnit\Framework\TestCase;

class FieldDefinitionTest extends TestCase
{
    // -------------------------------------------------------------------------
    // label
    // -------------------------------------------------------------------------

    public function testLabelFallsBackToUcfirstName(): void
    {
        $field = $this->makeField(['name' => 'my_field']);
        $this->assertSame('My_field', $field->label);
    }

    public function testLabelReturnsOptionsLabelWhenSet(): void
    {
        $field = $this->makeField(['options' => ['label' => 'Custom Label']]);
        $this->assertSame('Custom Label', $field->label);
    }

    public function testLabelSetterStoresInOptions(): void
    {
        $field = $this->makeField();
        $field->label = 'My Label';
        $this->assertSame('My Label', $field->options['label']);
        $this->assertSame('My Label', $field->label);
    }

    // -------------------------------------------------------------------------
    // isRequired
    // -------------------------------------------------------------------------

    public function testIsRequiredFalseByDefault(): void
    {
        $field = $this->makeField();
        $this->assertFalse($field->isRequired);
    }

    public function testIsRequiredTrueWhenNotBlankPresent(): void
    {
        $field = $this->makeField(['validationRules' => ['NotBlank' => null]]);
        $this->assertTrue($field->isRequired);
    }

    // -------------------------------------------------------------------------
    // normalizationGroups / denormalizationGroups
    // -------------------------------------------------------------------------

    public function testNormalizationGroupsDefaultEmpty(): void
    {
        $field = $this->makeField();
        $this->assertSame([], $field->normalizationGroups);
    }

    public function testNormalizationGroupsStored(): void
    {
        $field = $this->makeField(['normalizationGroups' => ['Entity:read', 'Entity:list']]);
        $this->assertSame(['Entity:read', 'Entity:list'], $field->normalizationGroups);
    }

    public function testDenormalizationGroupsDefaultEmpty(): void
    {
        $field = $this->makeField();
        $this->assertSame([], $field->denormalizationGroups);
    }

    public function testDenormalizationGroupsStored(): void
    {
        $field = $this->makeField(['denormalizationGroups' => ['Entity:write']]);
        $this->assertSame(['Entity:write'], $field->denormalizationGroups);
    }

    // -------------------------------------------------------------------------
    // serializerConfig / computed hooks
    // -------------------------------------------------------------------------

    public function testSerializerConfigDefaultEmpty(): void
    {
        $field = $this->makeField();
        $this->assertSame([], $field->serializerConfig);
    }

    public function testSerializedNameNullWhenNotSet(): void
    {
        $field = $this->makeField();
        $this->assertNull($field->serializedName);
    }

    public function testSerializedNameReturnsValue(): void
    {
        $field = $this->makeField(['serializerConfig' => ['serialized_name' => 'myField']]);
        $this->assertSame('myField', $field->serializedName);
    }

    public function testIgnoreFalseByDefault(): void
    {
        $field = $this->makeField();
        $this->assertFalse($field->ignore);
    }

    public function testIgnoreTrueWhenSet(): void
    {
        $field = $this->makeField(['serializerConfig' => ['ignore' => true]]);
        $this->assertTrue($field->ignore);
    }

    // -------------------------------------------------------------------------
    // formConfig / computed hooks
    // -------------------------------------------------------------------------

    public function testFormConfigDefaultEmpty(): void
    {
        $field = $this->makeField();
        $this->assertSame([], $field->formConfig);
    }

    public function testFormTypeNullWhenNotSet(): void
    {
        $field = $this->makeField();
        $this->assertNull($field->formType);
    }

    public function testFormTypeReturnsValue(): void
    {
        $field = $this->makeField([
            'formConfig' => ['form_type' => 'Symfony\Component\Form\Extension\Core\Type\TextType'],
        ]);
        $this->assertSame('Symfony\Component\Form\Extension\Core\Type\TextType', $field->formType);
    }

    public function testFormOptionsEmptyByDefault(): void
    {
        $field = $this->makeField();
        $this->assertSame([], $field->formOptions);
    }

    public function testFormOptionsReturnsValue(): void
    {
        $field = $this->makeField(['formConfig' => ['form_options' => ['attr' => ['class' => 'fancy']]]]);
        $this->assertSame(['attr' => ['class' => 'fancy']], $field->formOptions);
    }

    public function testShowInFormTrueByDefault(): void
    {
        $field = $this->makeField();
        $this->assertTrue($field->showInForm);
    }

    public function testShowInFormFalseWhenSet(): void
    {
        $field = $this->makeField(['formConfig' => ['show_in_form' => false]]);
        $this->assertFalse($field->showInForm);
    }

    // -------------------------------------------------------------------------
    // apiConfig / computed hooks
    // -------------------------------------------------------------------------

    public function testApiConfigDefaultEmpty(): void
    {
        $field = $this->makeField();
        $this->assertSame([], $field->apiConfig);
    }

    public function testIsFilterableFalseByDefault(): void
    {
        $field = $this->makeField();
        $this->assertFalse($field->isFilterable);
    }

    public function testIsFilterableTrueWhenSet(): void
    {
        $field = $this->makeField(['apiConfig' => ['filterable' => true]]);
        $this->assertTrue($field->isFilterable);
    }

    public function testIsSortableFalseByDefault(): void
    {
        $field = $this->makeField();
        $this->assertFalse($field->isSortable);
    }

    public function testIsSortableTrueWhenSet(): void
    {
        $field = $this->makeField(['apiConfig' => ['sortable' => true]]);
        $this->assertTrue($field->isSortable);
    }

    public function testIsSearchableFalseByDefault(): void
    {
        $field = $this->makeField();
        $this->assertFalse($field->isSearchable);
    }

    public function testIsSearchableTrueWhenSet(): void
    {
        $field = $this->makeField(['apiConfig' => ['searchable' => true]]);
        $this->assertTrue($field->isSearchable);
    }

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Definition
    {
        $field = new Definition();
        $field->entityAlias = $overrides['entityAlias'] ?? 'product';
        $field->name = $overrides['name'] ?? 'my_field';
        $field->type = $overrides['type'] ?? 'string';

        if (isset($overrides['validationRules'])) {
            $field->validationRules = $overrides['validationRules'];
        }
        if (isset($overrides['options'])) {
            $field->options = $overrides['options'];
        }
        if (isset($overrides['normalizationGroups'])) {
            $field->normalizationGroups = $overrides['normalizationGroups'];
        }
        if (isset($overrides['denormalizationGroups'])) {
            $field->denormalizationGroups = $overrides['denormalizationGroups'];
        }
        if (isset($overrides['serializerConfig'])) {
            $field->serializerConfig = $overrides['serializerConfig'];
        }
        if (isset($overrides['formConfig'])) {
            $field->formConfig = $overrides['formConfig'];
        }
        if (isset($overrides['apiConfig'])) {
            $field->apiConfig = $overrides['apiConfig'];
        }

        return $field;
    }
}
