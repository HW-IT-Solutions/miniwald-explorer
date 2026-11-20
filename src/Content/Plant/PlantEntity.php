<?php declare(strict_types=1);

namespace InReNa\Content\Plant;

use InReNa\Content\Miniwald\MiniwaldEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PlantEntity extends Entity
{
    use EntityIdTrait;

    protected ?EntityCollection $translations = null;

    protected ?string $miniwaldId = null;

    protected ?MiniwaldEntity $miniwald = null;

    protected ?string $name = null;

    protected ?string $scientificName = null;

    protected ?string $imageId = null;

    protected ?string $position = null;

    protected ?int $sortOrder = null;

    protected ?bool $active = null;

    protected ?MediaEntity $image = null;

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

    public function getScientificName(): ?string
    {
        return $this->scientificName;
    }

    public function setScientificName(?string $scientificName): void
    {
        $this->scientificName = $scientificName;
    }

    public function getImageId(): ?string
    {
        return $this->imageId;
    }

    public function setImageId(?string $imageId): void
    {
        $this->imageId = $imageId;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): void
    {
        $this->position = $position;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): void
    {
        $this->active = $active;
    }

    public function getMiniwaldId(): ?string
    {
        return $this->miniwaldId;
    }

    public function setMiniwaldId(?string $miniwaldId): void
    {
        $this->miniwaldId = $miniwaldId;
    }

    public function getMiniwald(): ?MiniwaldEntity
    {
        return $this->miniwald;
    }

    public function setMiniwald(?MiniwaldEntity $miniwald): void
    {
        $this->miniwald = $miniwald;
    }

    public function getImage(): ?MediaEntity
    {
        return $this->image;
    }

    public function setImage(?MediaEntity $image): void
    {
        $this->image = $image;
    }
}
