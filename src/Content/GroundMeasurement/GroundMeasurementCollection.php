<?php declare(strict_types=1);

namespace InReNa\Content\GroundMeasurement;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(GroundMeasurementEntity $entity)
 * @method void set(string $key, GroundMeasurementEntity $entity)
 * @method GroundMeasurementEntity[] getIterator()
 * @method GroundMeasurementEntity[] getElements()
 * @method GroundMeasurementEntity|null get(string $key)
 * @method GroundMeasurementEntity|null first()
 * @method GroundMeasurementEntity|null last()
 */
class GroundMeasurementCollection extends EntityCollection
{
    /**
     * Get average air temperature across all measurements.
     */
    public function getAverageAirTemperature(): ?float
    {
        $temps = [];
        foreach ($this->getElements() as $measurement) {
            if ($measurement->getAirTemperature() !== null) {
                $temps[] = $measurement->getAirTemperature();
            }
        }

        if (empty($temps)) {
            return null;
        }

        return array_sum($temps) / count($temps);
    }

    /**
     * Get average ground temperature across all measurements.
     */
    public function getAverageGroundTemperature(): ?float
    {
        $temps = [];
        foreach ($this->getElements() as $measurement) {
            if ($measurement->getGroundTemperature() !== null) {
                $temps[] = $measurement->getGroundTemperature();
            }
        }

        if (empty($temps)) {
            return null;
        }

        return array_sum($temps) / count($temps);
    }

    /**
     * Get average pH value across all measurements.
     */
    public function getAveragePhValue(): ?float
    {
        $values = [];
        foreach ($this->getElements() as $measurement) {
            if ($measurement->getPhValue() !== null) {
                $values[] = $measurement->getPhValue();
            }
        }

        if (empty($values)) {
            return null;
        }

        return array_sum($values) / count($values);
    }

    protected function getExpectedClass(): string
    {
        return GroundMeasurementEntity::class;
    }
}
