<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\PlantMeasurement;

use InReNa\Content\PlantMeasurement\PlantMeasurementCollection;
use InReNa\Content\PlantMeasurement\PlantMeasurementEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlantMeasurementCollection::class)]
class PlantMeasurementCollectionTest extends TestCase
{
    public function testGetExpectedClass(): void
    {
        $collection = new PlantMeasurementCollection();
        $method = new \ReflectionMethod($collection, 'getExpectedClass');
        static::assertSame(PlantMeasurementEntity::class, $method->invoke($collection));
    }

    public function testGetAverageHeightCmEmpty(): void
    {
        $collection = new PlantMeasurementCollection();
        static::assertNull($collection->getAverageHeightCm());
    }

    public function testGetAverageHeightCmSingle(): void
    {
        $entity = new PlantMeasurementEntity();
        $entity->setId('pm-1');
        $entity->setHeightCm(350);

        $collection = new PlantMeasurementCollection([$entity]);
        static::assertSame(350.0, $collection->getAverageHeightCm());
    }

    public function testGetAverageHeightCmMultiple(): void
    {
        $e1 = new PlantMeasurementEntity();
        $e1->setId('pm-1');
        $e1->setHeightCm(200);

        $e2 = new PlantMeasurementEntity();
        $e2->setId('pm-2');
        $e2->setHeightCm(400);

        $e3 = new PlantMeasurementEntity();
        $e3->setId('pm-3');
        $e3->setHeightCm(600);

        $collection = new PlantMeasurementCollection([$e1, $e2, $e3]);
        static::assertSame(400.0, $collection->getAverageHeightCm());
    }

    public function testGetAverageHeightCmSkipsNull(): void
    {
        $e1 = new PlantMeasurementEntity();
        $e1->setId('pm-1');
        $e1->setHeightCm(200);

        $e2 = new PlantMeasurementEntity();
        $e2->setId('pm-2');
        $e2->setHeightCm(null);

        $e3 = new PlantMeasurementEntity();
        $e3->setId('pm-3');
        $e3->setHeightCm(400);

        $collection = new PlantMeasurementCollection([$e1, $e2, $e3]);
        static::assertSame(300.0, $collection->getAverageHeightCm());
    }

    public function testGetAverageHeightCmAllNull(): void
    {
        $e1 = new PlantMeasurementEntity();
        $e1->setId('pm-1');

        $collection = new PlantMeasurementCollection([$e1]);
        static::assertNull($collection->getAverageHeightCm());
    }

    public function testGetAverageTrunkCircumferenceCmEmpty(): void
    {
        $collection = new PlantMeasurementCollection();
        static::assertNull($collection->getAverageTrunkCircumferenceCm());
    }

    public function testGetAverageTrunkCircumferenceCmSingle(): void
    {
        $entity = new PlantMeasurementEntity();
        $entity->setId('pm-1');
        $entity->setTrunkCircumferenceCm(94.2);

        $collection = new PlantMeasurementCollection([$entity]);
        static::assertSame(94.2, $collection->getAverageTrunkCircumferenceCm());
    }

    public function testGetAverageTrunkCircumferenceCmMultiple(): void
    {
        $e1 = new PlantMeasurementEntity();
        $e1->setId('pm-1');
        $e1->setTrunkCircumferenceCm(80.0);

        $e2 = new PlantMeasurementEntity();
        $e2->setId('pm-2');
        $e2->setTrunkCircumferenceCm(120.0);

        $collection = new PlantMeasurementCollection([$e1, $e2]);
        static::assertSame(100.0, $collection->getAverageTrunkCircumferenceCm());
    }

    public function testGetAverageTrunkCircumferenceCmSkipsNull(): void
    {
        $e1 = new PlantMeasurementEntity();
        $e1->setId('pm-1');
        $e1->setTrunkCircumferenceCm(80.0);

        $e2 = new PlantMeasurementEntity();
        $e2->setId('pm-2');
        $e2->setTrunkCircumferenceCm(null);

        $collection = new PlantMeasurementCollection([$e1, $e2]);
        static::assertSame(80.0, $collection->getAverageTrunkCircumferenceCm());
    }

    public function testGetAverageTrunkCircumferenceCmAllNull(): void
    {
        $e1 = new PlantMeasurementEntity();
        $e1->setId('pm-1');

        $collection = new PlantMeasurementCollection([$e1]);
        static::assertNull($collection->getAverageTrunkCircumferenceCm());
    }

    public function testFilterByPlantId(): void
    {
        $e1 = new PlantMeasurementEntity();
        $e1->setId('pm-1');
        $e1->setPlantId('plant-a');

        $e2 = new PlantMeasurementEntity();
        $e2->setId('pm-2');
        $e2->setPlantId('plant-b');

        $e3 = new PlantMeasurementEntity();
        $e3->setId('pm-3');
        $e3->setPlantId('plant-a');

        $collection = new PlantMeasurementCollection([$e1, $e2, $e3]);
        $filtered = $collection->filterByPlantId('plant-a');

        static::assertCount(2, $filtered);
        static::assertInstanceOf(PlantMeasurementCollection::class, $filtered);
    }

    public function testFilterByPlantIdNoMatch(): void
    {
        $e1 = new PlantMeasurementEntity();
        $e1->setId('pm-1');
        $e1->setPlantId('plant-a');

        $collection = new PlantMeasurementCollection([$e1]);
        $filtered = $collection->filterByPlantId('plant-nonexistent');

        static::assertCount(0, $filtered);
    }

    public function testFilterByPlantIdEmpty(): void
    {
        $collection = new PlantMeasurementCollection();
        $filtered = $collection->filterByPlantId('plant-a');

        static::assertCount(0, $filtered);
    }
}
