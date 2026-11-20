<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\GroundMeasurement;

use InReNa\Content\GroundMeasurement\GroundMeasurementCollection;
use InReNa\Content\GroundMeasurement\GroundMeasurementEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GroundMeasurementCollection::class)]
class GroundMeasurementCollectionTest extends TestCase
{
    public function testGetExpectedClass(): void
    {
        $collection = new GroundMeasurementCollection();
        $method = new \ReflectionMethod($collection, 'getExpectedClass');
        static::assertSame(GroundMeasurementEntity::class, $method->invoke($collection));
    }

    public function testGetAverageAirTemperatureEmpty(): void
    {
        $collection = new GroundMeasurementCollection();
        static::assertNull($collection->getAverageAirTemperature());
    }

    public function testGetAverageAirTemperatureSingle(): void
    {
        $entity = new GroundMeasurementEntity();
        $entity->setId('m-1');
        $entity->setAirTemperature(22.5);

        $collection = new GroundMeasurementCollection([$entity]);
        static::assertSame(22.5, $collection->getAverageAirTemperature());
    }

    public function testGetAverageAirTemperatureMultiple(): void
    {
        $e1 = new GroundMeasurementEntity();
        $e1->setId('m-1');
        $e1->setAirTemperature(20.0);

        $e2 = new GroundMeasurementEntity();
        $e2->setId('m-2');
        $e2->setAirTemperature(25.0);

        $e3 = new GroundMeasurementEntity();
        $e3->setId('m-3');
        $e3->setAirTemperature(30.0);

        $collection = new GroundMeasurementCollection([$e1, $e2, $e3]);
        static::assertSame(25.0, $collection->getAverageAirTemperature());
    }

    public function testGetAverageAirTemperatureSkipsNull(): void
    {
        $e1 = new GroundMeasurementEntity();
        $e1->setId('m-1');
        $e1->setAirTemperature(20.0);

        $e2 = new GroundMeasurementEntity();
        $e2->setId('m-2');
        $e2->setAirTemperature(null);

        $e3 = new GroundMeasurementEntity();
        $e3->setId('m-3');
        $e3->setAirTemperature(30.0);

        $collection = new GroundMeasurementCollection([$e1, $e2, $e3]);
        static::assertSame(25.0, $collection->getAverageAirTemperature());
    }

    public function testGetAverageAirTemperatureAllNull(): void
    {
        $e1 = new GroundMeasurementEntity();
        $e1->setId('m-1');
        $e1->setAirTemperature(null);

        $e2 = new GroundMeasurementEntity();
        $e2->setId('m-2');
        $e2->setAirTemperature(null);

        $collection = new GroundMeasurementCollection([$e1, $e2]);
        static::assertNull($collection->getAverageAirTemperature());
    }

    public function testGetAverageGroundTemperatureEmpty(): void
    {
        $collection = new GroundMeasurementCollection();
        static::assertNull($collection->getAverageGroundTemperature());
    }

    public function testGetAverageGroundTemperatureSingle(): void
    {
        $entity = new GroundMeasurementEntity();
        $entity->setId('m-1');
        $entity->setGroundTemperature(15.0);

        $collection = new GroundMeasurementCollection([$entity]);
        static::assertSame(15.0, $collection->getAverageGroundTemperature());
    }

    public function testGetAverageGroundTemperatureMultiple(): void
    {
        $e1 = new GroundMeasurementEntity();
        $e1->setId('m-1');
        $e1->setGroundTemperature(10.0);

        $e2 = new GroundMeasurementEntity();
        $e2->setId('m-2');
        $e2->setGroundTemperature(20.0);

        $collection = new GroundMeasurementCollection([$e1, $e2]);
        static::assertSame(15.0, $collection->getAverageGroundTemperature());
    }

    public function testGetAverageGroundTemperatureSkipsNull(): void
    {
        $e1 = new GroundMeasurementEntity();
        $e1->setId('m-1');
        $e1->setGroundTemperature(10.0);

        $e2 = new GroundMeasurementEntity();
        $e2->setId('m-2');
        $e2->setGroundTemperature(null);

        $collection = new GroundMeasurementCollection([$e1, $e2]);
        static::assertSame(10.0, $collection->getAverageGroundTemperature());
    }

    public function testGetAverageGroundTemperatureAllNull(): void
    {
        $e1 = new GroundMeasurementEntity();
        $e1->setId('m-1');

        $collection = new GroundMeasurementCollection([$e1]);
        static::assertNull($collection->getAverageGroundTemperature());
    }

    public function testGetAveragePhValueEmpty(): void
    {
        $collection = new GroundMeasurementCollection();
        static::assertNull($collection->getAveragePhValue());
    }

    public function testGetAveragePhValueSingle(): void
    {
        $entity = new GroundMeasurementEntity();
        $entity->setId('m-1');
        $entity->setPhValue(7);

        $collection = new GroundMeasurementCollection([$entity]);
        static::assertSame(7.0, $collection->getAveragePhValue());
    }

    public function testGetAveragePhValueMultiple(): void
    {
        $e1 = new GroundMeasurementEntity();
        $e1->setId('m-1');
        $e1->setPhValue(6);

        $e2 = new GroundMeasurementEntity();
        $e2->setId('m-2');
        $e2->setPhValue(8);

        $collection = new GroundMeasurementCollection([$e1, $e2]);
        static::assertSame(7.0, $collection->getAveragePhValue());
    }

    public function testGetAveragePhValueSkipsNull(): void
    {
        $e1 = new GroundMeasurementEntity();
        $e1->setId('m-1');
        $e1->setPhValue(7);

        $e2 = new GroundMeasurementEntity();
        $e2->setId('m-2');
        $e2->setPhValue(null);

        $e3 = new GroundMeasurementEntity();
        $e3->setId('m-3');
        $e3->setPhValue(5);

        $collection = new GroundMeasurementCollection([$e1, $e2, $e3]);
        static::assertSame(6.0, $collection->getAveragePhValue());
    }

    public function testGetAveragePhValueAllNull(): void
    {
        $e1 = new GroundMeasurementEntity();
        $e1->setId('m-1');

        $collection = new GroundMeasurementCollection([$e1]);
        static::assertNull($collection->getAveragePhValue());
    }

    public function testGetAveragePhValueNonInteger(): void
    {
        $e1 = new GroundMeasurementEntity();
        $e1->setId('m-1');
        $e1->setPhValue(6);

        $e2 = new GroundMeasurementEntity();
        $e2->setId('m-2');
        $e2->setPhValue(7);

        $e3 = new GroundMeasurementEntity();
        $e3->setId('m-3');
        $e3->setPhValue(8);

        $collection = new GroundMeasurementCollection([$e1, $e2, $e3]);
        static::assertSame(7.0, $collection->getAveragePhValue());
    }
}
