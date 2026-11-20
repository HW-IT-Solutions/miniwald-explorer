<?php declare(strict_types=1);

namespace InReNa\Content\Miniwald;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class MiniwaldEntity extends Entity
{
    use EntityIdTrait;

    protected ?EntityCollection $translations = null;

    protected ?string $name = null;

    protected ?float $latitude = null;

    protected ?float $longitude = null;

    protected ?int $radiusMeters = null;

    protected ?string $imageId = null;

    protected ?string $photoCaption = null;

    protected ?string $address = null;

    protected ?string $description = null;

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

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getRadiusMeters(): ?int
    {
        return $this->radiusMeters;
    }

    public function setRadiusMeters(?int $radiusMeters): void
    {
        $this->radiusMeters = $radiusMeters;
    }

    public function getImageId(): ?string
    {
        return $this->imageId;
    }

    public function setImageId(?string $imageId): void
    {
        $this->imageId = $imageId;
    }

    public function getPhotoCaption(): ?string
    {
        return $this->photoCaption;
    }

    public function setPhotoCaption(?string $photoCaption): void
    {
        $this->photoCaption = $photoCaption;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
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
