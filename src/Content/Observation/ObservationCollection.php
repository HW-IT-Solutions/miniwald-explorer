<?php declare(strict_types=1);

namespace InReNa\Content\Observation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(ObservationEntity $entity)
 * @method void set(string $key, ObservationEntity $entity)
 * @method ObservationEntity[] getIterator()
 * @method ObservationEntity[] getElements()
 * @method ObservationEntity|null get(string $key)
 * @method ObservationEntity|null first()
 * @method ObservationEntity|null last()
 */
class ObservationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ObservationEntity::class;
    }
}
