<?php declare(strict_types=1);

namespace InReNa\Content\GroundMeasurement;

use InReNa\Content\Observation\ObservationEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * GroundMeasurement Entity
 *
 * Stores ground/soil measurements for an observation.
 * One observation can have 1–3 items, one per location type.
 */
class GroundMeasurementEntity extends Entity
{
    use EntityIdTrait;

    protected string $observationId;

    protected string $locationType = GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER;

    protected ?float $airTemperature = null;

    protected ?float $groundTemperature = null;

    protected ?float $moisture = null;

    protected ?float $humidity = null;

    protected ?float $phValue = null;

    protected ?string $lightConditions = null;

    protected ?string $remarks = null;

    // Association
    protected ?ObservationEntity $observation = null;

    public function getObservationId(): string
    {
        return $this->observationId;
    }

    public function setObservationId(string $observationId): void
    {
        $this->observationId = $observationId;
    }

    public function getLocationType(): string
    {
        return $this->locationType;
    }

    public function setLocationType(string $locationType): void
    {
        $this->locationType = $locationType;
    }

    /**
     * Get the human-readable location type label.
     */
    public function getLocationTypeLabel(): string
    {
        return GroundMeasurementDefinition::LOCATION_TYPE_LABELS[$this->locationType] ?? $this->locationType;
    }

    public function getAirTemperature(): ?float
    {
        return $this->airTemperature;
    }

    public function setAirTemperature(?float $airTemperature): void
    {
        $this->airTemperature = $airTemperature;
    }

    public function getGroundTemperature(): ?float
    {
        return $this->groundTemperature;
    }

    public function setGroundTemperature(?float $groundTemperature): void
    {
        $this->groundTemperature = $groundTemperature;
    }

    public function getMoisture(): ?float
    {
        return $this->moisture;
    }

    public function setMoisture(?float $moisture): void
    {
        $this->moisture = $moisture;
    }

    public function getHumidity(): ?float
    {
        return $this->humidity;
    }

    public function setHumidity(?float $humidity): void
    {
        $this->humidity = $humidity;
    }

    public function getPhValue(): ?float
    {
        return $this->phValue;
    }

    public function setPhValue(?float $phValue): void
    {
        $this->phValue = $phValue;
    }

    public function getLightConditions(): ?string
    {
        return $this->lightConditions;
    }

    public function setLightConditions(?string $lightConditions): void
    {
        $this->lightConditions = $lightConditions;
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

    /**
     * Check if any measurement data has been recorded.
     */
    public function hasData(): bool
    {
        return $this->airTemperature !== null
            || $this->groundTemperature !== null
            || $this->moisture !== null
            || $this->humidity !== null
            || $this->phValue !== null;
    }
}
