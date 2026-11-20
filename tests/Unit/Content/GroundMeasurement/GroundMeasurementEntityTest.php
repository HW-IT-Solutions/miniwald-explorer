<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\GroundMeasurement;

use InReNa\Content\GroundMeasurement\GroundMeasurementDefinition;
use InReNa\Content\GroundMeasurement\GroundMeasurementEntity;
use InReNa\Content\Observation\ObservationEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GroundMeasurementEntity::class)]
class GroundMeasurementEntityTest extends TestCase
{
    private GroundMeasurementEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new GroundMeasurementEntity();
    }

    public function testDefaultNullableValues(): void
    {
        static::assertNull($this->entity->getAirTemperature());
        static::assertNull($this->entity->getGroundTemperature());
        static::assertNull($this->entity->getMoisture());
        static::assertNull($this->entity->getHumidity());
        static::assertNull($this->entity->getPhValue());
        static::assertNull($this->entity->getRemarks());
        static::assertNull($this->entity->getObservation());
    }

    public function testDefaultLocationType(): void
    {
        static::assertSame(GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER, $this->entity->getLocationType());
    }

    public function testGetSetLocationType(): void
    {
        $this->entity->setLocationType(GroundMeasurementDefinition::LOCATION_TYPE_WOOD_EDGE);
        static::assertSame('wood_edge', $this->entity->getLocationType());
    }

    public function testGetSetLocationTypeStreetCurb(): void
    {
        $this->entity->setLocationType(GroundMeasurementDefinition::LOCATION_TYPE_STREET_CURB);
        static::assertSame('street_curb', $this->entity->getLocationType());
    }

    public function testGetLocationTypeLabel(): void
    {
        $this->entity->setLocationType(GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER);
        static::assertSame('Waldmitte', $this->entity->getLocationTypeLabel());

        $this->entity->setLocationType(GroundMeasurementDefinition::LOCATION_TYPE_WOOD_EDGE);
        static::assertSame('Waldrand', $this->entity->getLocationTypeLabel());

        $this->entity->setLocationType(GroundMeasurementDefinition::LOCATION_TYPE_STREET_CURB);
        static::assertSame('Straßenrand', $this->entity->getLocationTypeLabel());
    }

    public function testGetLocationTypeLabelUnknownValue(): void
    {
        $this->entity->setLocationType('unknown_type');
        static::assertSame('unknown_type', $this->entity->getLocationTypeLabel());
    }

    public function testGetSetObservationId(): void
    {
        $uuid = 'obs-uuid-1234';
        $this->entity->setObservationId($uuid);
        static::assertSame($uuid, $this->entity->getObservationId());
    }

    public function testGetSetAirTemperature(): void
    {
        $this->entity->setAirTemperature(22.5);
        static::assertSame(22.5, $this->entity->getAirTemperature());
    }

    public function testGetSetAirTemperatureNegative(): void
    {
        $this->entity->setAirTemperature(-5.3);
        static::assertSame(-5.3, $this->entity->getAirTemperature());
    }

    public function testGetSetAirTemperatureZero(): void
    {
        $this->entity->setAirTemperature(0.0);
        static::assertSame(0.0, $this->entity->getAirTemperature());
    }

    public function testGetSetAirTemperatureNull(): void
    {
        $this->entity->setAirTemperature(null);
        static::assertNull($this->entity->getAirTemperature());
    }

    public function testGetSetGroundTemperature(): void
    {
        $this->entity->setGroundTemperature(15.8);
        static::assertSame(15.8, $this->entity->getGroundTemperature());
    }

    public function testGetSetGroundTemperatureNegative(): void
    {
        $this->entity->setGroundTemperature(-2.1);
        static::assertSame(-2.1, $this->entity->getGroundTemperature());
    }

    public function testGetSetGroundTemperatureNull(): void
    {
        $this->entity->setGroundTemperature(null);
        static::assertNull($this->entity->getGroundTemperature());
    }

    public function testGetSetMoisture(): void
    {
        $this->entity->setMoisture(45.0);
        static::assertSame(45.0, $this->entity->getMoisture());
    }

    public function testGetSetMoistureZero(): void
    {
        $this->entity->setMoisture(0.0);
        static::assertSame(0.0, $this->entity->getMoisture());
    }

    public function testGetSetMoistureMax(): void
    {
        $this->entity->setMoisture(100.0);
        static::assertSame(100.0, $this->entity->getMoisture());
    }

    public function testGetSetMoistureNull(): void
    {
        $this->entity->setMoisture(null);
        static::assertNull($this->entity->getMoisture());
    }

    public function testGetSetHumidity(): void
    {
        $this->entity->setHumidity(72.0);
        static::assertSame(72.0, $this->entity->getHumidity());
    }

    public function testGetSetHumidityZero(): void
    {
        $this->entity->setHumidity(0.0);
        static::assertSame(0.0, $this->entity->getHumidity());
    }

    public function testGetSetHumidityMax(): void
    {
        $this->entity->setHumidity(100.0);
        static::assertSame(100.0, $this->entity->getHumidity());
    }

    public function testGetSetHumidityNull(): void
    {
        $this->entity->setHumidity(null);
        static::assertNull($this->entity->getHumidity());
    }

    public function testGetSetPhValue(): void
    {
        $this->entity->setPhValue(7.0);
        static::assertSame(7.0, $this->entity->getPhValue());
    }

    public function testGetSetPhValueDecimal(): void
    {
        $this->entity->setPhValue(6.5);
        static::assertSame(6.5, $this->entity->getPhValue());
    }

    public function testGetSetPhValueMin(): void
    {
        $this->entity->setPhValue(1.0);
        static::assertSame(1.0, $this->entity->getPhValue());
    }

    public function testGetSetPhValueMax(): void
    {
        $this->entity->setPhValue(14.0);
        static::assertSame(14.0, $this->entity->getPhValue());
    }

    public function testGetSetPhValueNull(): void
    {
        $this->entity->setPhValue(null);
        static::assertNull($this->entity->getPhValue());
    }

    public function testGetSetRemarks(): void
    {
        $remarks = 'Boden war sehr feucht nach dem Regen';
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

    public function testHasDataReturnsFalseWhenEmpty(): void
    {
        static::assertFalse($this->entity->hasData());
    }

    public function testHasDataReturnsTrueWithAirTemperature(): void
    {
        $this->entity->setAirTemperature(20.0);
        static::assertTrue($this->entity->hasData());
    }

    public function testHasDataReturnsTrueWithGroundTemperature(): void
    {
        $this->entity->setGroundTemperature(15.0);
        static::assertTrue($this->entity->hasData());
    }

    public function testHasDataReturnsTrueWithMoisture(): void
    {
        $this->entity->setMoisture(30);
        static::assertTrue($this->entity->hasData());
    }

    public function testHasDataReturnsTrueWithHumidity(): void
    {
        $this->entity->setHumidity(60);
        static::assertTrue($this->entity->hasData());
    }

    public function testHasDataReturnsTrueWithPhValue(): void
    {
        $this->entity->setPhValue(7.0);
        static::assertTrue($this->entity->hasData());
    }

    public function testHasDataReturnsFalseWithOnlyRemarks(): void
    {
        $this->entity->setRemarks('Some remarks');
        static::assertFalse($this->entity->hasData());
    }

    public function testHasDataReturnsTrueWithAllFields(): void
    {
        $this->entity->setAirTemperature(22.0);
        $this->entity->setGroundTemperature(15.0);
        $this->entity->setMoisture(45);
        $this->entity->setHumidity(72);
        $this->entity->setPhValue(7.0);
        static::assertTrue($this->entity->hasData());
    }
}
