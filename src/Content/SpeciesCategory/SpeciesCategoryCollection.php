<?php declare(strict_types=1);

namespace InReNa\Content\SpeciesCategory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(SpeciesCategoryEntity $entity)
 * @method void set(string $key, SpeciesCategoryEntity $entity)
 * @method SpeciesCategoryEntity[] getIterator()
 * @method SpeciesCategoryEntity[] getElements()
 * @method SpeciesCategoryEntity|null get(string $key)
 * @method SpeciesCategoryEntity|null first()
 * @method SpeciesCategoryEntity|null last()
 */
class SpeciesCategoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SpeciesCategoryEntity::class;
    }
}
