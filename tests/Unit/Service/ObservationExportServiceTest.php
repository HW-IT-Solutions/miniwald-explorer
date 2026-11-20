<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Service;

use InReNa\Content\GroundMeasurement\GroundMeasurementCollection;
use InReNa\Content\GroundMeasurement\GroundMeasurementEntity;
use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\ObservationItem\ObservationItemCollection;
use InReNa\Content\ObservationItem\ObservationItemEntity;
use InReNa\Content\PlantMeasurement\PlantMeasurementCollection;
use InReNa\Content\PlantMeasurement\PlantMeasurementEntity;
use InReNa\Service\ObservationExportService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \InReNa\Service\ObservationExportService
 */
class ObservationExportServiceTest extends TestCase
{
    private ObservationExportService $service;

    protected function setUp(): void
    {
        $this->service = new ObservationExportService(
            $this->createStub(EntityRepository::class)
        );
    }

    public function testPersistedTypeIsMappedToExportLabels(): void
    {
        $observation = new ObservationEntity();
        $observation->setId(Uuid::randomHex());

        $observation->setType('animal');
        $this->assertSame('animals', $this->service->getObservationType($observation));

        $observation->setType('plant');
        $this->assertSame('plants', $this->service->getObservationType($observation));

        $observation->setType('ground');
        $this->assertSame('ground', $this->service->getObservationType($observation));
    }

    public function testPersistedTypeWinsOverAssociations(): void
    {
        $observation = new ObservationEntity();
        $observation->setId(Uuid::randomHex());
        $observation->setType('ground');

        $item = new ObservationItemEntity();
        $item->setId(Uuid::randomHex());
        $observation->setItems(new ObservationItemCollection([$item]));

        $this->assertSame('ground', $this->service->getObservationType($observation));
    }

    public function testLegacyRowFallsBackToAssociations(): void
    {
        $observation = new ObservationEntity();
        $observation->setId(Uuid::randomHex());

        $item = new ObservationItemEntity();
        $item->setId(Uuid::randomHex());
        $observation->setItems(new ObservationItemCollection([$item]));
        $this->assertSame('animals', $this->service->getObservationType($observation));

        $observation->setItems(new ObservationItemCollection([]));
        $pm = new PlantMeasurementEntity();
        $pm->setId(Uuid::randomHex());
        $observation->setPlantMeasurements(new PlantMeasurementCollection([$pm]));
        $this->assertSame('plants', $this->service->getObservationType($observation));

        $observation->setPlantMeasurements(new PlantMeasurementCollection([]));
        $gm = new GroundMeasurementEntity();
        $gm->setId(Uuid::randomHex());
        $observation->setGroundMeasurements(new GroundMeasurementCollection([$gm]));
        $this->assertSame('ground', $this->service->getObservationType($observation));
    }

    public function testUnknownWhenNothingIsSet(): void
    {
        $observation = new ObservationEntity();
        $observation->setId(Uuid::randomHex());

        $this->assertSame('unknown', $this->service->getObservationType($observation));
    }

    public function testDateBoundariesAreNormalizedToWholeDays(): void
    {
        // full ISO timestamps from the admin datepicker
        $this->assertSame('2026-07-13 00:00:00', $this->normalizeDateBoundary('2026-07-13T12:45:00.000Z', false));
        $this->assertSame('2026-07-13 23:59:59', $this->normalizeDateBoundary('2026-07-13T12:45:00.000Z', true));

        // bare dates
        $this->assertSame('2026-07-13 00:00:00', $this->normalizeDateBoundary('2026-07-13', false));
        $this->assertSame('2026-07-13 23:59:59', $this->normalizeDateBoundary('2026-07-13', true));

        // unparseable values pass through unchanged
        $this->assertSame('not-a-date', $this->normalizeDateBoundary('not-a-date', true));
    }

    public function testOtherMiniwaldUsesNullIdentifierInExportCriteria(): void
    {
        $method = new \ReflectionMethod($this->service, 'buildCriteria');
        $criteria = $method->invoke($this->service, ['miniwaldId' => 'other'], 1, 200);

        $miniwaldFilters = array_values(array_filter(
            $criteria->getFilters(),
            static fn ($filter) => $filter instanceof EqualsFilter && $filter->getField() === 'miniwaldId'
        ));

        self::assertCount(1, $miniwaldFilters);
        self::assertNull($miniwaldFilters[0]->getValue());
    }

    private function normalizeDateBoundary(string $date, bool $endOfDay): string
    {
        $method = new \ReflectionMethod($this->service, 'normalizeDateBoundary');

        return $method->invoke($this->service, $date, $endOfDay);
    }
}
