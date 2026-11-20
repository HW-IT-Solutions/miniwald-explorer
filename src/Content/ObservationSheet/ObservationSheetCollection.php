<?php declare(strict_types=1);

namespace InReNa\Content\ObservationSheet;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Collection class for ObservationSheetEntity.
 *
 * @method void add(ObservationSheetEntity $entity)
 * @method void set(string $key, ObservationSheetEntity $entity)
 * @method ObservationSheetEntity[] getIterator()
 * @method ObservationSheetEntity[] getElements()
 * @method ObservationSheetEntity|null get(string $key)
 * @method ObservationSheetEntity|null first()
 * @method ObservationSheetEntity|null last()
 *
 * @extends EntityCollection<ObservationSheetEntity>
 */
class ObservationSheetCollection extends EntityCollection
{
    /**
     * Get a sheet by its type slug.
     */
    public function getByType(string $type): ?ObservationSheetEntity
    {
        foreach ($this->getElements() as $element) {
            if ($element->getType() === $type) {
                return $element;
            }
        }

        return null;
    }

    protected function getExpectedClass(): string
    {
        return ObservationSheetEntity::class;
    }
}
