<?php

declare(strict_types=1);

namespace CoolMS\Field\Registry;

use CoolMS\Core\Field\FieldWidgetProviderInterface;
use CoolMS\Field\Contract\FieldWidgetRegistryInterface;

/**
 * Aggregates the front-end widget descriptors every module contributes for a
 * field type (first provider wins per type).
 *
 * Wired in the Field bundle's DI extension with
 * a TaggedIteratorArgument('coolms.field.widget_provider) -- the same explicit
 * Domain/Registry registration as {@see FormTypeRegistry} (Domain/Registry is
 * excluded from the `App\:` glob, so the tagged-iterator argument can't be
 * overwritten; ADR-118).
 */
final class FieldWidgetRegistry implements FieldWidgetRegistryInterface
{
    /** @var array<string, FieldWidgetProviderInterface>|null lazily built type => provider map */
    private ?array $byType = null;

    /** @param iterable<FieldWidgetProviderInterface> $providers */
    public function __construct(private readonly iterable $providers)
    {
    }

    public function widgetForType(string $type, array $fieldConfig = []): ?array
    {
        return ($this->map()[$type] ?? null)?->widget($fieldConfig);
    }

    /**
     * Type => first contributing provider. The provider set is static so it is
     * cached, but the descriptor itself is built per call (via `widget()`) so it
     * can reflect the per-field config the caller passes in.
     *
     * @return array<string, FieldWidgetProviderInterface>
     */
    private function map(): array
    {
        if (null === $this->byType) {
            $this->byType = [];
            foreach ($this->providers as $provider) {
                $this->byType[$provider->fieldType()] ??= $provider;
            }
        }

        return $this->byType;
    }
}
