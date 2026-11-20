<?php declare(strict_types=1);

namespace InReNa\Content\Observation;

use InReNa\Content\GroundMeasurement\GroundMeasurementCollection;
use InReNa\Content\Miniwald\MiniwaldEntity;
use InReNa\Content\ObservationItem\ObservationItemCollection;
use InReNa\Content\PlantMeasurement\PlantMeasurementCollection;
use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ObservationEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $customerId = null;

    protected ?string $miniwaldId = null;

    protected ?string $type = null;

    protected ?string $speciesCategoryId = null;

    protected ?\DateTimeInterface $observedAt = null;

    protected ?float $latitude = null;

    protected ?float $longitude = null;

    protected ?string $weather = null;

    protected ?string $wind = null;

    protected ?string $remarks = null;

    protected ?string $status = null;

    protected ?\DateTimeInterface $submittedAt = null;

    protected ?CustomerEntity $customer = null;

    protected ?MiniwaldEntity $miniwald = null;

    protected ?SpeciesCategoryEntity $speciesCategory = null;

    protected ?ObservationItemCollection $items = null;

    protected ?GroundMeasurementCollection $groundMeasurements = null;

    protected ?PlantMeasurementCollection $plantMeasurements = null;

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getMiniwaldId(): ?string
    {
        return $this->miniwaldId;
    }

    public function setMiniwaldId(?string $miniwaldId): void
    {
        $this->miniwaldId = $miniwaldId;
    }

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

    public function getObservedAt(): ?\DateTimeInterface
    {
        return $this->observedAt;
    }

    public function setObservedAt(?\DateTimeInterface $observedAt): void
    {
        $this->observedAt = $observedAt;
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

    public function getWeather(): ?string
    {
        return $this->weather;
    }

    public function setWeather(?string $weather): void
    {
        $this->weather = $weather;
    }

    public function getWind(): ?string
    {
        return $this->wind;
    }

    public function setWind(?string $wind): void
    {
        $this->wind = $wind;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function setRemarks(?string $remarks): void
    {
        $this->remarks = $remarks;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getSubmittedAt(): ?\DateTimeInterface
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeInterface $submittedAt): void
    {
        $this->submittedAt = $submittedAt;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getMiniwald(): ?MiniwaldEntity
    {
        return $this->miniwald;
    }

    public function setMiniwald(?MiniwaldEntity $miniwald): void
    {
        $this->miniwald = $miniwald;
    }

    public function getSpeciesCategory(): ?SpeciesCategoryEntity
    {
        return $this->speciesCategory;
    }

    public function setSpeciesCategory(?SpeciesCategoryEntity $speciesCategory): void
    {
        $this->speciesCategory = $speciesCategory;
    }

    public function getItems(): ?ObservationItemCollection
    {
        return $this->items;
    }

    public function setItems(?ObservationItemCollection $items): void
    {
        $this->items = $items;
    }

    public function getGroundMeasurements(): ?GroundMeasurementCollection
    {
        return $this->groundMeasurements;
    }

    public function setGroundMeasurements(?GroundMeasurementCollection $groundMeasurements): void
    {
        $this->groundMeasurements = $groundMeasurements;
    }

    public function getPlantMeasurements(): ?PlantMeasurementCollection
    {
        return $this->plantMeasurements;
    }

    public function setPlantMeasurements(?PlantMeasurementCollection $plantMeasurements): void
    {
        $this->plantMeasurements = $plantMeasurements;
    }
}
