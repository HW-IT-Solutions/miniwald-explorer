<?php declare(strict_types=1);

namespace InReNa\Content\Plant;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(PlantEntity $entity)
 * @method void set(string $key, PlantEntity $entity)
 * @method PlantEntity[] getIterator()
 * @method PlantEntity[] getElements()
 * @method PlantEntity|null get(string $key)
 * @method PlantEntity|null first()
 * @method PlantEntity|null last()
 */
class PlantCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PlantEntity::class;
    }
}
