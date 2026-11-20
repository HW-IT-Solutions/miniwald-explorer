<?php declare(strict_types=1);

namespace InReNa\Content\Photo;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<PhotoEntity>
 *
 * @method void add(PhotoEntity $entity)
 * @method void set(string $key, PhotoEntity $entity)
 * @method PhotoEntity[] getIterator()
 * @method PhotoEntity[] getElements()
 * @method PhotoEntity|null get(string $key)
 * @method PhotoEntity|null first()
 * @method PhotoEntity|null last()
 */
class PhotoCollection extends EntityCollection
{
    /**
     * Get photos sorted by sort order.
     */
    public function getSorted(): self
    {
        $elements = $this->getElements();
        usort($elements, fn (PhotoEntity $a, PhotoEntity $b) => $a->getSortOrder() <=> $b->getSortOrder());

        $collection = new self();
        foreach ($elements as $element) {
            $collection->add($element);
        }

        return $collection;
    }

    /**
     * Get only approved photos.
     */
    public function getApproved(): self
    {
        return $this->filter(fn (PhotoEntity $photo) => $photo->getIsApproved() === true);
    }

    /**
     * Get photos pending moderation.
     */
    public function getPending(): self
    {
        return $this->filter(fn (PhotoEntity $photo) => $photo->isPending());
    }

    /**
     * Get rejected photos.
     */
    public function getRejected(): self
    {
        return $this->filter(fn (PhotoEntity $photo) => $photo->isRejected());
    }

    /**
     * Get all media IDs.
     */
    public function getMediaIds(): array
    {
        return array_values(array_unique(array_map(
            fn (PhotoEntity $photo) => $photo->getMediaId(),
            $this->getElements()
        )));
    }

    protected function getExpectedClass(): string
    {
        return PhotoEntity::class;
    }
}
