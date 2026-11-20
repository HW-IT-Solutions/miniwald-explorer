<?php declare(strict_types=1);

namespace InReNa\Content\PlantMeasurement;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<PlantMeasurementEntity>
 *
 * @method void add(PlantMeasurementEntity $entity)
 * @method void set(string $key, PlantMeasurementEntity $entity)
 * @method PlantMeasurementEntity[] getIterator()
 * @method PlantMeasurementEntity[] getElements()
 * @method PlantMeasurementEntity|null get(string $key)
 * @method PlantMeasurementEntity|null first()
 * @method PlantMeasurementEntity|null last()
 */
class PlantMeasurementCollection extends EntityCollection
{
    /**
     * Get average height across all measurements.
     */
    public function getAverageHeightCm(): ?float
    {
        $heights = [];
        foreach ($this->getElements() as $measurement) {
            if ($measurement->getHeightCm() !== null) {
                $heights[] = $measurement->getHeightCm();
            }
        }

        if (empty($heights)) {
            return null;
        }

        return array_sum($heights) / count($heights);
    }

    /**
     * Get average trunk circumference across all measurements.
     */
    public function getAverageTrunkCircumferenceCm(): ?float
    {
        $circumferences = [];
        foreach ($this->getElements() as $measurement) {
            if ($measurement->getTrunkCircumferenceCm() !== null) {
                $circumferences[] = $measurement->getTrunkCircumferenceCm();
            }
        }

        if (empty($circumferences)) {
            return null;
        }

        return array_sum($circumferences) / count($circumferences);
    }

    /**
     * Filter measurements by plant ID.
     */
    public function filterByPlantId(string $plantId): self
    {
        return $this->filter(fn (PlantMeasurementEntity $measurement) => $measurement->getPlantId() === $plantId);
    }

    protected function getExpectedClass(): string
    {
        return PlantMeasurementEntity::class;
    }
}
