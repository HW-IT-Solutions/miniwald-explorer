<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\PlantMeasurement;

use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\Plant\PlantEntity;
use InReNa\Content\PlantMeasurement\PlantMeasurementEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlantMeasurementEntity::class)]
class PlantMeasurementEntityTest extends TestCase
{
    private PlantMeasurementEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new PlantMeasurementEntity();
    }

    public function testDefaultNullableValues(): void
    {
        static::assertNull($this->entity->getPlantId());
        static::assertNull($this->entity->getCustomPlantName());
        static::assertNull($this->entity->getHeightCm());
        static::assertNull($this->entity->getTrunkCircumferenceCm());
        static::assertNull($this->entity->getCrownDiameterCm());
        static::assertNull($this->entity->getRemarks());
        static::assertNull($this->entity->getObservation());
        static::assertNull($this->entity->getPlant());
    }

    public function testGetSetObservationId(): void
    {
        $uuid = 'obs-uuid-1234';
        $this->entity->setObservationId($uuid);
        static::assertSame($uuid, $this->entity->getObservationId());
    }

    public function testGetSetPlantId(): void
    {
        $uuid = 'plant-uuid-5678';
        $this->entity->setPlantId($uuid);
        static::assertSame($uuid, $this->entity->getPlantId());
    }

    public function testGetSetPlantIdNull(): void
    {
        $this->entity->setPlantId(null);
        static::assertNull($this->entity->getPlantId());
    }

    public function testGetSetCustomPlantName(): void
    {
        $this->entity->setCustomPlantName('Stieleiche');
        static::assertSame('Stieleiche', $this->entity->getCustomPlantName());
    }

    public function testGetSetCustomPlantNameNull(): void
    {
        $this->entity->setCustomPlantName(null);
        static::assertNull($this->entity->getCustomPlantName());
    }

    public function testGetSetHeightCm(): void
    {
        $this->entity->setHeightCm(350);
        static::assertSame(350, $this->entity->getHeightCm());
    }

    public function testGetSetHeightCmZero(): void
    {
        $this->entity->setHeightCm(0);
        static::assertSame(0, $this->entity->getHeightCm());
    }

    public function testGetSetHeightCmNull(): void
    {
        $this->entity->setHeightCm(null);
        static::assertNull($this->entity->getHeightCm());
    }

    public function testGetHeightM(): void
    {
        $this->entity->setHeightCm(350);
        static::assertSame(3.5, $this->entity->getHeightM());
    }

    public function testGetHeightMSmallValue(): void
    {
        $this->entity->setHeightCm(50);
        static::assertSame(0.5, $this->entity->getHeightM());
    }

    public function testGetHeightMZero(): void
    {
        $this->entity->setHeightCm(0);
        static::assertSame(0.0, $this->entity->getHeightM());
    }

    public function testGetHeightMNull(): void
    {
        $this->entity->setHeightCm(null);
        static::assertNull($this->entity->getHeightM());
    }

    public function testGetHeightMLargeValue(): void
    {
        $this->entity->setHeightCm(2500);
        static::assertSame(25.0, $this->entity->getHeightM());
    }

    public function testGetSetTrunkCircumferenceCm(): void
    {
        $this->entity->setTrunkCircumferenceCm(94.2);
        static::assertSame(94.2, $this->entity->getTrunkCircumferenceCm());
    }

    public function testGetSetTrunkCircumferenceCmNull(): void
    {
        $this->entity->setTrunkCircumferenceCm(null);
        static::assertNull($this->entity->getTrunkCircumferenceCm());
    }

    public function testGetTrunkDiameterCm(): void
    {
        // circumference = π * diameter, so diameter = circumference / π
        $circumference = 94.2;
        $this->entity->setTrunkCircumferenceCm($circumference);
        $expectedDiameter = $circumference / M_PI;
        static::assertEqualsWithDelta($expectedDiameter, $this->entity->getTrunkDiameterCm(), 0.001);
    }

    public function testGetTrunkDiameterCmKnownValue(): void
    {
        // A tree with 30cm diameter has circumference of ~94.25cm
        $this->entity->setTrunkCircumferenceCm(M_PI * 30);
        static::assertEqualsWithDelta(30.0, $this->entity->getTrunkDiameterCm(), 0.001);
    }

    public function testGetTrunkDiameterCmNull(): void
    {
        $this->entity->setTrunkCircumferenceCm(null);
        static::assertNull($this->entity->getTrunkDiameterCm());
    }

    public function testGetSetCrownDiameterCm(): void
    {
        $this->entity->setCrownDiameterCm(500);
        static::assertSame(500, $this->entity->getCrownDiameterCm());
    }

    public function testGetSetCrownDiameterCmNull(): void
    {
        $this->entity->setCrownDiameterCm(null);
        static::assertNull($this->entity->getCrownDiameterCm());
    }

    public function testGetCrownDiameterM(): void
    {
        $this->entity->setCrownDiameterCm(500);
        static::assertSame(5.0, $this->entity->getCrownDiameterM());
    }

    public function testGetCrownDiameterMSmallValue(): void
    {
        $this->entity->setCrownDiameterCm(150);
        static::assertSame(1.5, $this->entity->getCrownDiameterM());
    }

    public function testGetCrownDiameterMZero(): void
    {
        $this->entity->setCrownDiameterCm(0);
        static::assertSame(0.0, $this->entity->getCrownDiameterM());
    }

    public function testGetCrownDiameterMNull(): void
    {
        $this->entity->setCrownDiameterCm(null);
        static::assertNull($this->entity->getCrownDiameterM());
    }

    public function testGetSetRemarks(): void
    {
        $remarks = 'Baum zeigt Anzeichen von Trockenheit';
        $this->entity->setRemarks($remarks);
        static::assertSame($remarks, $this->entity->getRemarks());
    }

    public function testGetSetRemarksNull(): void
    {
        $this->entity->setRemarks(null);
        static::assertNull($this->entity->getRemarks());
    }

    public function testGetSetObservation(): void
    {
        $observation = new ObservationEntity();
        $observation->setId('obs-id');
        $this->entity->setObservation($observation);
        static::assertSame($observation, $this->entity->getObservation());
    }

    public function testGetSetObservationNull(): void
    {
        $this->entity->setObservation(null);
        static::assertNull($this->entity->getObservation());
    }

    public function testGetSetPlant(): void
    {
        $plant = new PlantEntity();
        $plant->setId('plant-id');
        $plant->setScientificName('Quercus robur');
        $this->entity->setPlant($plant);
        static::assertSame($plant, $this->entity->getPlant());
        static::assertSame('Quercus robur', $this->entity->getPlant()->getScientificName());
    }

    public function testGetSetPlantNull(): void
    {
        $this->entity->setPlant(null);
        static::assertNull($this->entity->getPlant());
    }

    public function testHasDataReturnsFalseWhenEmpty(): void
    {
        static::assertFalse($this->entity->hasData());
    }

    public function testHasDataReturnsTrueWithHeightCm(): void
    {
        $this->entity->setHeightCm(350);
        static::assertTrue($this->entity->hasData());
    }

    public function testHasDataReturnsTrueWithTrunkCircumference(): void
    {
        $this->entity->setTrunkCircumferenceCm(94.2);
        static::assertTrue($this->entity->hasData());
    }

    public function testHasDataReturnsTrueWithCrownDiameter(): void
    {
        $this->entity->setCrownDiameterCm(500);
        static::assertTrue($this->entity->hasData());
    }

    public function testHasDataReturnsFalseWithOnlyRemarks(): void
    {
        $this->entity->setRemarks('Some remarks');
        static::assertFalse($this->entity->hasData());
    }

    public function testHasDataReturnsFalseWithOnlyPlantId(): void
    {
        $this->entity->setPlantId('plant-uuid');
        static::assertFalse($this->entity->hasData());
    }

    public function testHasDataReturnsTrueWithAllMeasurements(): void
    {
        $this->entity->setHeightCm(350);
        $this->entity->setTrunkCircumferenceCm(94.2);
        $this->entity->setCrownDiameterCm(500);
        static::assertTrue($this->entity->hasData());
    }
}
