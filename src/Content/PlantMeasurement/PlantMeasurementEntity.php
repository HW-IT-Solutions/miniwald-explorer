<?php declare(strict_types=1);

namespace InReNa\Content\PlantMeasurement;

use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\Plant\PlantEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * PlantMeasurement Entity
 *
 * Stores plant measurements for an observation.
 */
class PlantMeasurementEntity extends Entity
{
    use EntityIdTrait;

    protected string $observationId;

    protected ?string $plantId = null;

    protected ?string $customPlantName = null;

    protected ?int $heightCm = null;

    protected ?float $trunkCircumferenceCm = null;

    protected ?int $crownDiameterCm = null;

    protected ?string $remarks = null;

    // Associations
    protected ?ObservationEntity $observation = null;

    protected ?PlantEntity $plant = null;

    public function getObservationId(): string
    {
        return $this->observationId;
    }

    public function setObservationId(string $observationId): void
    {
        $this->observationId = $observationId;
    }

    public function getPlantId(): ?string
    {
        return $this->plantId;
    }

    public function setPlantId(?string $plantId): void
    {
        $this->plantId = $plantId;
    }

    public function getCustomPlantName(): ?string
    {
        return $this->customPlantName;
    }

    public function setCustomPlantName(?string $customPlantName): void
    {
        $this->customPlantName = $customPlantName;
    }

    public function getHeightCm(): ?int
    {
        return $this->heightCm;
    }

    public function setHeightCm(?int $heightCm): void
    {
        $this->heightCm = $heightCm;
    }

    /**
     * Get height in meters.
     */
    public function getHeightM(): ?float
    {
        if ($this->heightCm === null) {
            return null;
        }

        return $this->heightCm / 100;
    }

    public function getTrunkCircumferenceCm(): ?float
    {
        return $this->trunkCircumferenceCm;
    }

    public function setTrunkCircumferenceCm(?float $trunkCircumferenceCm): void
    {
        $this->trunkCircumferenceCm = $trunkCircumferenceCm;
    }

    /**
     * Calculate trunk diameter from circumference.
     */
    public function getTrunkDiameterCm(): ?float
    {
        if ($this->trunkCircumferenceCm === null) {
            return null;
        }

        return $this->trunkCircumferenceCm / M_PI;
    }

    public function getCrownDiameterCm(): ?int
    {
        return $this->crownDiameterCm;
    }

    public function setCrownDiameterCm(?int $crownDiameterCm): void
    {
        $this->crownDiameterCm = $crownDiameterCm;
    }

    /**
     * Get crown diameter in meters.
     */
    public function getCrownDiameterM(): ?float
    {
        if ($this->crownDiameterCm === null) {
            return null;
        }

        return $this->crownDiameterCm / 100;
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

    public function getPlant(): ?PlantEntity
    {
        return $this->plant;
    }

    public function setPlant(?PlantEntity $plant): void
    {
        $this->plant = $plant;
    }

    /**
     * Check if any measurement data has been recorded.
     */
    public function hasData(): bool
    {
        return $this->heightCm !== null
            || $this->trunkCircumferenceCm !== null
            || $this->crownDiameterCm !== null;
    }
}
