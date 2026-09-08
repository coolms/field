<?php

declare(strict_types=1);

namespace CoolMS\Field\Service;

use CoolMS\Entity\FieldDefinitionInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Converts a FieldDefinitionInterface validation rules map to Symfony Constraint objects.
 */
class ConstraintBuilder
{
    public function __construct(
        private readonly string $constraintNamespace = 'Symfony\\Component\\Validator\\Constraints',
    ) {
    }

    /**
     * Builds Symfony Constraint objects from a raw validation rules map.
     *
     * @param array<string, mixed> $rules
     *
     * @return Constraint[]
     */
    public function buildFromRules(array $rules): array
    {
        $constraints = [];
        foreach ($rules as $name => $options) {
            $class = $this->constraintNamespace . '\\' . $name;
            /** @var Constraint $constraint */
            $constraint = new $class($options ?? []);
            $constraints[] = $constraint;
        }

        return $constraints;
    }

    /**
     * Builds Symfony Constraint objects from the field's validationRules map.
     *
     * @return Constraint[]
     */
    public function build(FieldDefinitionInterface $field): array
    {
        return $this->buildFromRules($field->validationRules);
    }
}
