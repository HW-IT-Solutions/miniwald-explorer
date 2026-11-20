<?php declare(strict_types=1);

namespace InReNa\Content\Subspecies;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(SubspeciesEntity $entity)
 * @method void set(string $key, SubspeciesEntity $entity)
 * @method SubspeciesEntity[] getIterator()
 * @method SubspeciesEntity[] getElements()
 * @method SubspeciesEntity|null get(string $key)
 * @method SubspeciesEntity|null first()
 * @method SubspeciesEntity|null last()
 */
class SubspeciesCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SubspeciesEntity::class;
    }
}
