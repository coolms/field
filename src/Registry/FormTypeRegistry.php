<?php

declare(strict_types=1);

namespace CoolMS\Field\Registry;

use CoolMS\Core\Field\FormTypeProviderInterface;

/**
 * Aggregates all registered Symfony form types from tagged providers.
 *
 * Wired in the Field bundle's DI extension
 * with TaggedIteratorArgument('coolms.field.form_type').
 */
final class FormTypeRegistry
{
    /** @param iterable<FormTypeProviderInterface> $providers */
    public function __construct(private readonly iterable $providers)
    {
    }

    /**
     * Returns all registered form type options, preserving insertion order.
     *
     * @return array<array{value: string, label: string}>
     */
    public function all(): array
    {
        $result = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getFormTypeOptions() as $option) {
                $result[] = $option;
            }
        }

        return $result;
    }
}
