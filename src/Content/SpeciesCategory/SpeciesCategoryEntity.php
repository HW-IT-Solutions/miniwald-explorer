<?php declare(strict_types=1);

namespace InReNa\Content\SpeciesCategory;

use InReNa\Content\Species\SpeciesCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class SpeciesCategoryEntity extends Entity
{
    use EntityIdTrait;

    protected ?EntityCollection $translations = null;

    protected ?string $name = null;

    protected ?string $description = null;

    protected ?string $imageId = null;

    protected ?int $position = null;

    protected ?bool $active = null;

    protected ?string $technicalName = null;

    protected ?MediaEntity $image = null;

    protected ?SpeciesCollection $species = null;

    public function getTranslations(): ?EntityCollection
    {
        return $this->translations;
    }

    public function setTranslations(?EntityCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getImageId(): ?string
    {
        return $this->imageId;
    }

    public function setImageId(?string $imageId): void
    {
        $this->imageId = $imageId;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): void
    {
        $this->active = $active;
    }

    public function getTechnicalName(): ?string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(?string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function getImage(): ?MediaEntity
    {
        return $this->image;
    }

    public function setImage(?MediaEntity $image): void
    {
        $this->image = $image;
    }

    public function getSpecies(): ?SpeciesCollection
    {
        return $this->species;
    }

    public function setSpecies(?SpeciesCollection $species): void
    {
        $this->species = $species;
    }
}
