<?php declare(strict_types=1);

namespace InReNa\Content\ObservationItem;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ObservationItemEntity>
 *
 * @method void add(ObservationItemEntity $entity)
 * @method void set(string $key, ObservationItemEntity $entity)
 * @method ObservationItemEntity[] getIterator()
 * @method ObservationItemEntity[] getElements()
 * @method ObservationItemEntity|null get(string $key)
 * @method ObservationItemEntity|null first()
 * @method ObservationItemEntity|null last()
 */
class ObservationItemCollection extends EntityCollection
{
    /**
     * Get total count of all individuals observed across all items.
     */
    public function getTotalCount(): int
    {
        $total = 0;
        foreach ($this->getElements() as $item) {
            $total += $item->getCount();
        }

        return $total;
    }

    /**
     * Filter items by species ID.
     */
    public function filterBySpeciesId(string $speciesId): self
    {
        return $this->filter(fn (ObservationItemEntity $item) => $item->getSpeciesId() === $speciesId);
    }

    /**
     * Get items with custom species (not from catalog).
     */
    public function getCustomSpeciesItems(): self
    {
        return $this->filter(fn (ObservationItemEntity $item) => $item->getSpeciesId() === null && $item->getCustomSpeciesName() !== null);
    }

    protected function getExpectedClass(): string
    {
        return ObservationItemEntity::class;
    }
}
