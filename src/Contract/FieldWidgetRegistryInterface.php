<?php

declare(strict_types=1);

namespace CoolMS\Field\Contract;

use CoolMS\Core\Field\FieldWidgetProviderInterface;

/**
 * Reads the front-end widget descriptor a module contributes for a field type,
 * aggregated from every {@see FieldWidgetProviderInterface}.
 *
 * Consumers (e.g. the content field-panel resolver) depend on this interface
 * -- never the concrete registry -- so the module boundary stays at an interface
 * import.
 */
interface FieldWidgetRegistryInterface
{
    /**
     * The widget descriptor for the given field `type`, or `null` when no
     * installed module provides one (the field then uses the built-in input).
     *
     * The field's resolved schema config is forwarded to the contributing
     * provider so it can specialise the descriptor per field (e.g. a taxonomy
     * field's `widget: { tree: <code> }`).
     *
     * @param array<string, mixed> $fieldConfig the resolved schema config for the field
     *
     * @return array<string, mixed>|null
     */
    public function widgetForType(string $type, array $fieldConfig = []): ?array;
}
