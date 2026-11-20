<?php declare(strict_types=1);

namespace InReNa\Content\Species;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(SpeciesEntity $entity)
 * @method void set(string $key, SpeciesEntity $entity)
 * @method SpeciesEntity[] getIterator()
 * @method SpeciesEntity[] getElements()
 * @method SpeciesEntity|null get(string $key)
 * @method SpeciesEntity|null first()
 * @method SpeciesEntity|null last()
 */
class SpeciesCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SpeciesEntity::class;
    }
}
