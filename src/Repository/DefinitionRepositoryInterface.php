<?php

declare(strict_types=1);

namespace CoolMS\Field\Repository;

use CoolMS\Field\Entity\DefinitionInterface;
use CoolMS\Core\Repository\RepositoryInterface;

interface DefinitionRepositoryInterface extends RepositoryInterface
{
    /**
     * Return all Definitions belonging to a given entity type alias.
     *
     * @return DefinitionInterface[]
     */
    public function findByEntityAlias(string $entityAlias): array;

    /**
     * Return the highest sortOrder value among all Definitions for the given alias.
     * Returns 0 when no fields exist yet.
     */
    public function maxSortOrderByAlias(string $entityAlias): int;

    /**
     * Bulk-update sortOrder for a set of Definitions.
     *
     * @param array<string, int> $order UUID (string) => new sortOrder value
     */
    public function reorderBatch(array $order): void;
}
