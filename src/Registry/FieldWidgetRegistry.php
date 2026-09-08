<?php

declare(strict_types=1);

namespace CoolMS\Field\Registry;

use CoolMS\Core\Field\FieldWidgetProviderInterface;
use CoolMS\Field\Contract\FieldWidgetRegistryInterface;

/**
 * Aggregates the front-end widget descriptors every module contributes for a
 * field type (first provider wins per type).
 *
 * Registered explicitly by the Field bundle's DI extension, with a tagged
 * iterator over `coolms.field.widget_provider` -- the same treatment as
 * {@see FormTypeRegistry}.
 *
 * !! The registration has to be explicit. A host application that autowires
 * its own namespaces by glob will re-register any class it can see and
 * overwrite the tagged-iterator argument with an autowired one, leaving the
 * registry with no providers and no error. Registering it here means the
 * argument is set by the module that knows what belongs in it.
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
