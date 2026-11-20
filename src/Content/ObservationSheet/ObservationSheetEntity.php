<?php declare(strict_types=1);

namespace InReNa\Content\ObservationSheet;

use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Entity class for a PDF observation sheet.
 *
 * Each instance represents one observation sheet:
 * - type=animal with a species_category_id → one PDF per species category
 * - type=plant → one fixed PDF for plant measurements
 * - type=ground → one fixed PDF for ground/air measurements
 */
class ObservationSheetEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $type = null;

    protected ?string $speciesCategoryId = null;

    protected ?SpeciesCategoryEntity $speciesCategory = null;

    protected ?string $mediaId = null;

    protected ?MediaEntity $media = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getSpeciesCategoryId(): ?string
    {
        return $this->speciesCategoryId;
    }

    public function setSpeciesCategoryId(?string $speciesCategoryId): void
    {
        $this->speciesCategoryId = $speciesCategoryId;
    }

    public function getSpeciesCategory(): ?SpeciesCategoryEntity
    {
        return $this->speciesCategory;
    }

    public function setSpeciesCategory(?SpeciesCategoryEntity $speciesCategory): void
    {
        $this->speciesCategory = $speciesCategory;
    }

    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    public function setMediaId(?string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    public function getMedia(): ?MediaEntity
    {
        return $this->media;
    }

    public function setMedia(?MediaEntity $media): void
    {
        $this->media = $media;
    }
}
