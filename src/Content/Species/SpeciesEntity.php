<?php declare(strict_types=1);

namespace InReNa\Content\Species;

use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use InReNa\Content\Subspecies\SubspeciesCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class SpeciesEntity extends Entity
{
    use EntityIdTrait;

    protected ?EntityCollection $translations = null;

    protected ?string $speciesCategoryId = null;

    protected ?string $name = null;

    protected ?string $scientificName = null;

    protected ?string $taxonId = null;

    protected ?string $description = null;

    protected ?string $identificationTips = null;

    protected ?string $imageId = null;

    protected ?int $position = null;

    protected ?bool $active = null;

    protected ?SpeciesCategoryEntity $speciesCategory = null;

    protected ?MediaEntity $image = null;

    protected ?SubspeciesCollection $subspecies = null;

    public function getTranslations(): ?EntityCollection
    {
        return $this->translations;
    }

    public function setTranslations(?EntityCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getSpeciesCategoryId(): ?string
    {
        return $this->speciesCategoryId;
    }

    public function setSpeciesCategoryId(?string $speciesCategoryId): void
    {
        $this->speciesCategoryId = $speciesCategoryId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getScientificName(): ?string
    {
        return $this->scientificName;
    }

    public function setScientificName(?string $scientificName): void
    {
        $this->scientificName = $scientificName;
    }

    public function getTaxonId(): ?string
    {
        return $this->taxonId;
    }

    public function setTaxonId(?string $taxonId): void
    {
        $this->taxonId = $taxonId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getIdentificationTips(): ?string
    {
        return $this->identificationTips;
    }

    public function setIdentificationTips(?string $identificationTips): void
    {
        $this->identificationTips = $identificationTips;
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

    public function getSpeciesCategory(): ?SpeciesCategoryEntity
    {
        return $this->speciesCategory;
    }

    public function setSpeciesCategory(?SpeciesCategoryEntity $speciesCategory): void
    {
        $this->speciesCategory = $speciesCategory;
    }

    public function getImage(): ?MediaEntity
    {
        return $this->image;
    }

    public function setImage(?MediaEntity $image): void
    {
        $this->image = $image;
    }

    public function getSubspecies(): ?SubspeciesCollection
    {
        return $this->subspecies;
    }

    public function setSubspecies(?SubspeciesCollection $subspecies): void
    {
        $this->subspecies = $subspecies;
    }
}
