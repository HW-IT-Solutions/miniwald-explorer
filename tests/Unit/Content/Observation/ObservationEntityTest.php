<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Observation;

use InReNa\Content\GroundMeasurement\GroundMeasurementCollection;
use InReNa\Content\GroundMeasurement\GroundMeasurementEntity;
use InReNa\Content\Miniwald\MiniwaldEntity;
use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\ObservationItem\ObservationItemCollection;
use InReNa\Content\PlantMeasurement\PlantMeasurementCollection;
use InReNa\Content\PlantMeasurement\PlantMeasurementEntity;
use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Uuid\Uuid;

#[CoversClass(ObservationEntity::class)]
class ObservationEntityTest extends TestCase
{
    private ObservationEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new ObservationEntity();
    }

    public function testDefaultValues(): void
    {
        static::assertNull($this->entity->getCustomerId());
        static::assertNull($this->entity->getMiniwaldId());
        static::assertNull($this->entity->getType());
        static::assertNull($this->entity->getSpeciesCategoryId());
        static::assertNull($this->entity->getSpeciesCategory());
        static::assertNull($this->entity->getObservedAt());
        static::assertNull($this->entity->getLatitude());
        static::assertNull($this->entity->getLongitude());
        static::assertNull($this->entity->getWeather());
        static::assertNull($this->entity->getWind());
        static::assertNull($this->entity->getRemarks());
        static::assertNull($this->entity->getStatus());
        static::assertNull($this->entity->getSubmittedAt());
        static::assertNull($this->entity->getCustomer());
        static::assertNull($this->entity->getMiniwald());
        static::assertNull($this->entity->getItems());
        static::assertNull($this->entity->getGroundMeasurements());
        static::assertNull($this->entity->getPlantMeasurements());
    }

    public function testGetSetCustomerId(): void
    {
        $uuid = 'customer-uuid-1234';
        $this->entity->setCustomerId($uuid);
        static::assertSame($uuid, $this->entity->getCustomerId());
    }

    public function testGetSetCustomerIdNull(): void
    {
        $this->entity->setCustomerId(null);
        static::assertNull($this->entity->getCustomerId());
    }

    public function testGetSetMiniwaldId(): void
    {
        $uuid = 'miniwald-uuid-5678';
        $this->entity->setMiniwaldId($uuid);
        static::assertSame($uuid, $this->entity->getMiniwaldId());
    }

    public function testGetSetMiniwaldIdNull(): void
    {
        $this->entity->setMiniwaldId(null);
        static::assertNull($this->entity->getMiniwaldId());
    }

    public function testGetSetType(): void
    {
        $typeValues = ['animal', 'plant', 'ground'];
        foreach ($typeValues as $type) {
            $this->entity->setType($type);
            static::assertSame($type, $this->entity->getType());
        }
    }

    public function testGetSetTypeNull(): void
    {
        $this->entity->setType(null);
        static::assertNull($this->entity->getType());
    }

    public function testGetSetSpeciesCategoryId(): void
    {
        $uuid = 'species-category-uuid-9012';
        $this->entity->setSpeciesCategoryId($uuid);
        static::assertSame($uuid, $this->entity->getSpeciesCategoryId());
    }

    public function testGetSetSpeciesCategoryIdNull(): void
    {
        $this->entity->setSpeciesCategoryId(null);
        static::assertNull($this->entity->getSpeciesCategoryId());
    }

    public function testGetSetSpeciesCategory(): void
    {
        $category = new SpeciesCategoryEntity();
        $category->setId(Uuid::randomHex());
        $this->entity->setSpeciesCategory($category);
        static::assertSame($category, $this->entity->getSpeciesCategory());
    }

    public function testGetSetSpeciesCategoryNull(): void
    {
        $this->entity->setSpeciesCategory(null);
        static::assertNull($this->entity->getSpeciesCategory());
    }

    public function testGetSetObservedAt(): void
    {
        $date = new \DateTimeImmutable('2026-04-21 14:30:00');
        $this->entity->setObservedAt($date);
        static::assertSame($date, $this->entity->getObservedAt());
    }

    public function testGetSetObservedAtNull(): void
    {
        $this->entity->setObservedAt(null);
        static::assertNull($this->entity->getObservedAt());
    }

    public function testGetSetLatitude(): void
    {
        $this->entity->setLatitude(50.9375);
        static::assertSame(50.9375, $this->entity->getLatitude());
    }

    public function testGetSetLatitudeNull(): void
    {
        $this->entity->setLatitude(null);
        static::assertNull($this->entity->getLatitude());
    }

    public function testGetSetLatitudeNegative(): void
    {
        $this->entity->setLatitude(-33.8688);
        static::assertSame(-33.8688, $this->entity->getLatitude());
    }

    public function testGetSetLongitude(): void
    {
        $this->entity->setLongitude(6.3603);
        static::assertSame(6.3603, $this->entity->getLongitude());
    }

    public function testGetSetLongitudeNull(): void
    {
        $this->entity->setLongitude(null);
        static::assertNull($this->entity->getLongitude());
    }

    public function testGetSetLongitudeNegative(): void
    {
        $this->entity->setLongitude(-73.9857);
        static::assertSame(-73.9857, $this->entity->getLongitude());
    }

    public function testGetSetWeather(): void
    {
        $this->entity->setWeather('sunny');
        static::assertSame('sunny', $this->entity->getWeather());
    }

    public function testGetSetWeatherAllValues(): void
    {
        $weatherValues = ['sunny', 'partly_cloudy', 'cloudy', 'rainy'];
        foreach ($weatherValues as $weather) {
            $this->entity->setWeather($weather);
            static::assertSame($weather, $this->entity->getWeather());
        }
    }

    public function testGetSetWeatherNull(): void
    {
        $this->entity->setWeather(null);
        static::assertNull($this->entity->getWeather());
    }

    public function testGetSetWind(): void
    {
        $this->entity->setWind('moderate');
        static::assertSame('moderate', $this->entity->getWind());
    }

    public function testGetSetWindAllValues(): void
    {
        $windValues = ['calm', 'light_wind', 'moderate_wind', 'strong_wind'];
        foreach ($windValues as $wind) {
            $this->entity->setWind($wind);
            static::assertSame($wind, $this->entity->getWind());
        }
    }

    public function testGetSetWindNull(): void
    {
        $this->entity->setWind(null);
        static::assertNull($this->entity->getWind());
    }

    public function testGetSetRemarks(): void
    {
        $remarks = 'Schöner Tag für Beobachtungen im Miniwald Inden';
        $this->entity->setRemarks($remarks);
        static::assertSame($remarks, $this->entity->getRemarks());
    }

    public function testGetSetRemarksNull(): void
    {
        $this->entity->setRemarks(null);
        static::assertNull($this->entity->getRemarks());
    }

    public function testGetSetStatus(): void
    {
        $this->entity->setStatus('draft');
        static::assertSame('draft', $this->entity->getStatus());
    }

    public function testGetSetStatusNull(): void
    {
        $this->entity->setStatus(null);
        static::assertNull($this->entity->getStatus());
    }

    public function testGetSetSubmittedAt(): void
    {
        $date = new \DateTimeImmutable('2026-04-21 15:00:00');
        $this->entity->setSubmittedAt($date);
        static::assertSame($date, $this->entity->getSubmittedAt());
    }

    public function testGetSetSubmittedAtNull(): void
    {
        $this->entity->setSubmittedAt(null);
        static::assertNull($this->entity->getSubmittedAt());
    }

    public function testGetSetCustomer(): void
    {
        $customer = new CustomerEntity();
        $customer->setId('customer-id');
        $this->entity->setCustomer($customer);
        static::assertSame($customer, $this->entity->getCustomer());
    }

    public function testGetSetCustomerNull(): void
    {
        $this->entity->setCustomer(null);
        static::assertNull($this->entity->getCustomer());
    }

    public function testGetSetMiniwald(): void
    {
        $miniwald = new MiniwaldEntity();
        $miniwald->setId('miniwald-id');
        $miniwald->setName('Miniwald Inden');
        $this->entity->setMiniwald($miniwald);
        static::assertSame($miniwald, $this->entity->getMiniwald());
        static::assertSame('Miniwald Inden', $this->entity->getMiniwald()->getName());
    }

    public function testGetSetMiniwaldNull(): void
    {
        $this->entity->setMiniwald(null);
        static::assertNull($this->entity->getMiniwald());
    }

    public function testGetSetItems(): void
    {
        $collection = new ObservationItemCollection();
        $this->entity->setItems($collection);
        static::assertSame($collection, $this->entity->getItems());
    }

    public function testGetSetItemsNull(): void
    {
        $this->entity->setItems(null);
        static::assertNull($this->entity->getItems());
    }

    public function testGetSetGroundMeasurements(): void
    {
        $measurement = new GroundMeasurementEntity();
        $measurement->setId(Uuid::randomHex());
        $collection = new GroundMeasurementCollection([$measurement]);
        $this->entity->setGroundMeasurements($collection);
        static::assertSame($collection, $this->entity->getGroundMeasurements());
    }

    public function testGetSetGroundMeasurementsNull(): void
    {
        $this->entity->setGroundMeasurements(null);
        static::assertNull($this->entity->getGroundMeasurements());
    }

    public function testGetSetPlantMeasurements(): void
    {
        $measurement = new PlantMeasurementEntity();
        $measurement->setId(Uuid::randomHex());
        $collection = new PlantMeasurementCollection([$measurement]);
        $this->entity->setPlantMeasurements($collection);
        static::assertSame($collection, $this->entity->getPlantMeasurements());
    }

    public function testGetSetPlantMeasurementsNull(): void
    {
        $this->entity->setPlantMeasurements(null);
        static::assertNull($this->entity->getPlantMeasurements());
    }
}
