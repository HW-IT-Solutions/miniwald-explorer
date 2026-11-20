<?php declare(strict_types=1);

namespace InReNa\Content\ObservationItem;

use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\Photo\PhotoCollection;
use InReNa\Content\Species\SpeciesEntity;
use InReNa\Content\Subspecies\SubspeciesEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * ObservationItem Entity
 *
 * Represents a single species observation within an Observation.
 */
class ObservationItemEntity extends Entity
{
    use EntityIdTrait;

    protected string $observationId;

    protected ?string $speciesId = null;

    protected ?string $subspeciesId = null;

    protected ?string $customSpeciesName = null;

    protected int $count;

    protected ?string $remarks = null;

    // Associations
    protected ?ObservationEntity $observation = null;

    protected ?SpeciesEntity $species = null;

    protected ?SubspeciesEntity $subspecies = null;

    protected ?PhotoCollection $photos = null;

    public function getObservationId(): string
    {
        return $this->observationId;
    }

    public function setObservationId(string $observationId): void
    {
        $this->observationId = $observationId;
    }

    public function getSpeciesId(): ?string
    {
        return $this->speciesId;
    }

    public function setSpeciesId(?string $speciesId): void
    {
        $this->speciesId = $speciesId;
    }

    public function getSubspeciesId(): ?string
    {
        return $this->subspeciesId;
    }

    public function setSubspeciesId(?string $subspeciesId): void
    {
        $this->subspeciesId = $subspeciesId;
    }

    public function getCustomSpeciesName(): ?string
    {
        return $this->customSpeciesName;
    }

    public function setCustomSpeciesName(?string $customSpeciesName): void
    {
        $this->customSpeciesName = $customSpeciesName;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function setRemarks(?string $remarks): void
    {
        $this->remarks = $remarks;
    }

    public function getObservation(): ?ObservationEntity
    {
        return $this->observation;
    }

    public function setObservation(?ObservationEntity $observation): void
    {
        $this->observation = $observation;
    }

    public function getSpecies(): ?SpeciesEntity
    {
        return $this->species;
    }

    public function setSpecies(?SpeciesEntity $species): void
    {
        $this->species = $species;
    }

    public function getSubspecies(): ?SubspeciesEntity
    {
        return $this->subspecies;
    }

    public function setSubspecies(?SubspeciesEntity $subspecies): void
    {
        $this->subspecies = $subspecies;
    }

    public function getPhotos(): ?PhotoCollection
    {
        return $this->photos;
    }

    public function setPhotos(?PhotoCollection $photos): void
    {
        $this->photos = $photos;
    }

    /**
     * Get the display name for this observation item.
     * Returns species name, subspecies name, or custom species name.
     */
    public function getDisplayName(): string
    {
        if ($this->subspecies !== null) {
            return $this->subspecies->getTranslation('name') ?? $this->subspecies->getName();
        }

        if ($this->species !== null) {
            return $this->species->getTranslation('name') ?? $this->species->getName();
        }

        return $this->customSpeciesName ?? 'Unknown Species';
    }
}
