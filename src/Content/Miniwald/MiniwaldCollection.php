<?php declare(strict_types=1);

namespace InReNa\Content\Miniwald;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(MiniwaldEntity $entity)
 * @method void set(string $key, MiniwaldEntity $entity)
 * @method MiniwaldEntity[] getIterator()
 * @method MiniwaldEntity[] getElements()
 * @method MiniwaldEntity|null get(string $key)
 * @method MiniwaldEntity|null first()
 * @method MiniwaldEntity|null last()
 */
class MiniwaldCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return MiniwaldEntity::class;
    }
}
