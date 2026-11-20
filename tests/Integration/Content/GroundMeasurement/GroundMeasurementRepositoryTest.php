<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content\GroundMeasurement;

use InReNa\Content\GroundMeasurement\GroundMeasurementDefinition;
use InReNa\Content\GroundMeasurement\GroundMeasurementEntity;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

#[CoversClass(GroundMeasurementDefinition::class)]
class GroundMeasurementRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private EntityRepository $repository;
    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository('miniwald_explorer_ground_measurement');
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testCreateGroundMeasurement(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createGroundMeasurement($this->ids, 'gm-1', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
            'airTemperature' => 22.5,
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('gm-1')]),
            $this->context
        )->first();

        static::assertInstanceOf(GroundMeasurementEntity::class, $entity);
        static::assertSame(22.5, $entity->getAirTemperature());
        static::assertSame(GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER, $entity->getLocationType());
    }

    public function testCreateWithAllFields(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createGroundMeasurement($this->ids, 'gm-full', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_EDGE,
            'airTemperature' => 22.5,
            'groundTemperature' => 18.3,
            'moisture' => 45,
            'humidity' => 72,
            'phValue' => 7.0,
            'remarks' => 'Soil is damp after rain',
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('gm-full')]),
            $this->context
        )->first();

        static::assertSame(GroundMeasurementDefinition::LOCATION_TYPE_WOOD_EDGE, $entity->getLocationType());
        static::assertSame(22.5, $entity->getAirTemperature());
        static::assertSame(18.3, $entity->getGroundTemperature());
        static::assertSame(45.0, $entity->getMoisture());
        static::assertSame(72.0, $entity->getHumidity());
        static::assertSame(7.0, $entity->getPhValue());
        static::assertSame('Soil is damp after rain', $entity->getRemarks());
    }

    public function testCreateRequiresObservationId(): void
    {
        $this->expectException(\Throwable::class);

        $this->repository->create([
            [
                'id' => $this->ids->get('gm-no-obs'),
                'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
                'airTemperature' => 20.0,
            ],
        ], $this->context);
    }

    public function testMultipleItemsPerObservation(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createGroundMeasurement($this->ids, 'gm-center', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
        ]);
        $this->createGroundMeasurement($this->ids, 'gm-edge', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_EDGE,
        ]);
        $this->createGroundMeasurement($this->ids, 'gm-curb', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_STREET_CURB,
        ]);

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter(
                'observationId',
                $this->ids->get('obs')
            )
        );

        $result = $this->repository->search($criteria, $this->context);
        static::assertCount(3, $result);
    }

    public function testUniqueLocationTypePerObservation(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createGroundMeasurement($this->ids, 'gm-first', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
        ]);

        $this->expectException(\Throwable::class);

        // Same observation + same location_type → unique constraint violation
        $this->createGroundMeasurement($this->ids, 'gm-duplicate', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
        ]);
    }

    public function testDifferentLocationTypesAllowed(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createGroundMeasurement($this->ids, 'gm-center', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
        ]);
        // Different location type for same observation — must not throw
        $this->createGroundMeasurement($this->ids, 'gm-edge', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_EDGE,
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('gm-edge')]),
            $this->context
        )->first();

        static::assertInstanceOf(GroundMeasurementEntity::class, $entity);
        static::assertSame(GroundMeasurementDefinition::LOCATION_TYPE_WOOD_EDGE, $entity->getLocationType());
    }

    public function testUpdateGroundMeasurement(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createGroundMeasurement($this->ids, 'gm-upd', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
            'airTemperature' => 20.0,
        ]);

        $this->repository->update([
            [
                'id' => $this->ids->get('gm-upd'),
                'airTemperature' => 25.0,
                'phValue' => 6.0,
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('gm-upd')]),
            $this->context
        )->first();

        static::assertSame(25.0, $entity->getAirTemperature());
        static::assertSame(6.0, $entity->getPhValue());
    }

    public function testDeleteGroundMeasurement(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createGroundMeasurement($this->ids, 'gm-del', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
        ]);

        $this->repository->delete([
            ['id' => $this->ids->get('gm-del')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('gm-del')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testCascadeDeleteFromObservation(): void
    {
        $this->createObservation($this->ids, 'obs-cascade');
        $this->createGroundMeasurement($this->ids, 'gm-cascade', 'obs-cascade', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
        ]);

        $this->getRepository('miniwald_explorer_observation')->delete([
            ['id' => $this->ids->get('obs-cascade')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('gm-cascade')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testObservationAssociationLoading(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createGroundMeasurement($this->ids, 'gm-assoc', 'obs', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
        ]);

        $criteria = new Criteria([$this->ids->get('gm-assoc')]);
        $criteria->addAssociation('observation');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getObservation());
        static::assertSame($this->ids->get('obs'), $entity->getObservation()->getId());
    }
}
