<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content\PlantMeasurement;

use InReNa\Content\PlantMeasurement\PlantMeasurementDefinition;
use InReNa\Content\PlantMeasurement\PlantMeasurementEntity;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

#[CoversClass(PlantMeasurementDefinition::class)]
class PlantMeasurementRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private EntityRepository $repository;
    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository('miniwald_explorer_plant_measurement');
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testCreatePlantMeasurement(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createPlantMeasurement($this->ids, 'pm-1', 'obs', [
            'heightCm' => 350,
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('pm-1')]),
            $this->context
        )->first();

        static::assertInstanceOf(PlantMeasurementEntity::class, $entity);
        static::assertSame(350, $entity->getHeightCm());
    }

    public function testCreateWithAllFields(): void
    {
        $this->createPlant($this->ids, 'plant');
        $this->createObservation($this->ids, 'obs');
        $this->createPlantMeasurement($this->ids, 'pm-full', 'obs', [
            'plantId' => $this->ids->get('plant'),
            'heightCm' => 450,
            'trunkCircumferenceCm' => 95.5,
            'crownDiameterCm' => 600,
            'remarks' => 'Healthy growth',
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('pm-full')]),
            $this->context
        )->first();

        static::assertSame(450, $entity->getHeightCm());
        static::assertSame(95.5, $entity->getTrunkCircumferenceCm());
        static::assertSame(600, $entity->getCrownDiameterCm());
        static::assertSame('Healthy growth', $entity->getRemarks());
        static::assertSame($this->ids->get('plant'), $entity->getPlantId());
    }

    public function testCreateRequiresObservationId(): void
    {
        $this->expectException(\Throwable::class);

        $this->repository->create([
            [
                'id' => $this->ids->get('pm-no-obs'),
                'heightCm' => 100,
            ],
        ], $this->context);
    }

    public function testMultipleMeasurementsAllowed(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createPlantMeasurement($this->ids, 'pm-first', 'obs', ['heightCm' => 100]);
        $this->createPlantMeasurement($this->ids, 'pm-second', 'obs', ['heightCm' => 200]);

        $criteria = new Criteria();
        $criteria->addFilter(
            new \Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter(
                'observationId',
                $this->ids->get('obs')
            )
        );
        $result = $this->repository->search($criteria, $this->context);

        static::assertSame(2, $result->getTotal());
    }

    public function testMultipleMeasurementsPerObservation(): void
    {
        $this->createPlant($this->ids, 'plant-a');
        $this->createPlant($this->ids, 'plant-b');
        $this->createObservation($this->ids, 'obs');
        $this->createPlantMeasurement($this->ids, 'pm-a', 'obs', [
            'plantId' => $this->ids->get('plant-a'),
            'heightCm' => 150,
        ]);
        $this->createPlantMeasurement($this->ids, 'pm-b', 'obs', [
            'plantId' => $this->ids->get('plant-b'),
            'heightCm' => 300,
        ]);

        $entityA = $this->repository->search(
            new Criteria([$this->ids->get('pm-a')]),
            $this->context
        )->first();
        $entityB = $this->repository->search(
            new Criteria([$this->ids->get('pm-b')]),
            $this->context
        )->first();

        static::assertSame($this->ids->get('obs'), $entityA->getObservationId());
        static::assertSame($this->ids->get('obs'), $entityB->getObservationId());
        static::assertSame(150, $entityA->getHeightCm());
        static::assertSame(300, $entityB->getHeightCm());
    }

    public function testUpdatePlantMeasurement(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createPlantMeasurement($this->ids, 'pm-upd', 'obs', [
            'heightCm' => 300,
        ]);

        $this->repository->update([
            [
                'id' => $this->ids->get('pm-upd'),
                'heightCm' => 320,
                'trunkCircumferenceCm' => 80.0,
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('pm-upd')]),
            $this->context
        )->first();

        static::assertSame(320, $entity->getHeightCm());
        static::assertSame(80.0, $entity->getTrunkCircumferenceCm());
    }

    public function testDeletePlantMeasurement(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createPlantMeasurement($this->ids, 'pm-del', 'obs');

        $this->repository->delete([
            ['id' => $this->ids->get('pm-del')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('pm-del')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testCascadeDeleteFromObservation(): void
    {
        $this->createObservation($this->ids, 'obs-cascade');
        $this->createPlantMeasurement($this->ids, 'pm-cascade', 'obs-cascade');

        $this->getRepository('miniwald_explorer_observation')->delete([
            ['id' => $this->ids->get('obs-cascade')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('pm-cascade')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testPlantSetNullOnPlantDelete(): void
    {
        $this->createPlant($this->ids, 'plant-del');
        $this->createObservation($this->ids, 'obs');
        $this->createPlantMeasurement($this->ids, 'pm-plant-del', 'obs', [
            'plantId' => $this->ids->get('plant-del'),
        ]);

        $this->getRepository('miniwald_explorer_plant')->delete([
            ['id' => $this->ids->get('plant-del')],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('pm-plant-del')]),
            $this->context
        )->first();

        static::assertNotNull($entity);
        static::assertNull($entity->getPlantId());
    }

    public function testPlantAssociationLoading(): void
    {
        $this->createPlant($this->ids, 'plant', ['name' => 'Oak']);
        $this->createObservation($this->ids, 'obs');
        $this->createPlantMeasurement($this->ids, 'pm-assoc', 'obs', [
            'plantId' => $this->ids->get('plant'),
        ]);

        $criteria = new Criteria([$this->ids->get('pm-assoc')]);
        $criteria->addAssociation('plant');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getPlant());
        static::assertSame('Oak', $entity->getPlant()->getTranslation('name'));
    }
}
