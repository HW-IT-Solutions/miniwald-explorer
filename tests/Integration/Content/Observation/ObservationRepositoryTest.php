<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content\Observation;

use InReNa\Content\GroundMeasurement\GroundMeasurementDefinition;
use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Content\Observation\ObservationEntity;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

#[CoversClass(ObservationDefinition::class)]
class ObservationRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private EntityRepository $repository;
    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository('miniwald_explorer_observation');
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testCreateObservation(): void
    {
        $this->createObservation($this->ids, 'obs-1');

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('obs-1')]),
            $this->context
        )->first();

        static::assertInstanceOf(ObservationEntity::class, $entity);
        static::assertNotNull($entity->getObservedAt());
    }

    public function testCreateWithAllFields(): void
    {
        $this->createMiniwald($this->ids, 'mw');
        $this->createObservation($this->ids, 'obs-full', [
            'miniwaldId' => $this->ids->get('mw'),
            'latitude' => 50.9375,
            'longitude' => 6.9603,
            'weather' => 'sunny',
            'wind' => 'light',
            'remarks' => 'Beautiful day for observation',
            'status' => 'submitted',
            'submittedAt' => '2026-04-21 10:00:00.000',
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('obs-full')]),
            $this->context
        )->first();

        static::assertSame(50.9375, $entity->getLatitude());
        static::assertSame(6.9603, $entity->getLongitude());
        static::assertSame('sunny', $entity->getWeather());
        static::assertSame('light', $entity->getWind());
        static::assertSame('Beautiful day for observation', $entity->getRemarks());
        static::assertSame('submitted', $entity->getStatus());
        static::assertNotNull($entity->getSubmittedAt());
    }

    public function testCreateWithTypeAndSpeciesCategory(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createObservation($this->ids, 'obs-typed', [
            'type' => ObservationDefinition::TYPE_ANIMAL,
            'speciesCategoryId' => $this->ids->get('cat'),
        ]);

        $criteria = new Criteria([$this->ids->get('obs-typed')]);
        $criteria->addAssociation('speciesCategory');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertSame(ObservationDefinition::TYPE_ANIMAL, $entity->getType());
        static::assertSame($this->ids->get('cat'), $entity->getSpeciesCategoryId());
        static::assertNotNull($entity->getSpeciesCategory());
        static::assertSame($this->ids->get('cat'), $entity->getSpeciesCategory()->getId());
    }

    public function testSearchByType(): void
    {
        $this->createObservation($this->ids, 'obs-animal', [
            'type' => ObservationDefinition::TYPE_ANIMAL,
        ]);
        $this->createObservation($this->ids, 'obs-plant', [
            'type' => ObservationDefinition::TYPE_PLANT,
        ]);

        $criteria = new Criteria([
            $this->ids->get('obs-animal'),
            $this->ids->get('obs-plant'),
        ]);
        $criteria->addFilter(new EqualsFilter('type', ObservationDefinition::TYPE_PLANT));

        $result = $this->repository->search($criteria, $this->context);

        static::assertCount(1, $result);
        static::assertSame($this->ids->get('obs-plant'), $result->first()->getId());
    }

    public function testSpeciesCategorySetNullOnCategoryDelete(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-del');
        $this->createObservation($this->ids, 'obs-cat', [
            'type' => ObservationDefinition::TYPE_ANIMAL,
            'speciesCategoryId' => $this->ids->get('cat-del'),
        ]);

        $this->getRepository('miniwald_explorer_species_category')->delete([
            ['id' => $this->ids->get('cat-del')],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('obs-cat')]),
            $this->context
        )->first();

        static::assertNotNull($entity);
        static::assertNull($entity->getSpeciesCategoryId());
    }

    public function testUpdateObservation(): void
    {
        $this->createObservation($this->ids, 'obs-upd');

        $this->repository->update([
            [
                'id' => $this->ids->get('obs-upd'),
                'status' => 'submitted',
                'submittedAt' => '2026-04-21 12:00:00.000',
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('obs-upd')]),
            $this->context
        )->first();

        static::assertSame('submitted', $entity->getStatus());
        static::assertNotNull($entity->getSubmittedAt());
    }

    public function testDeleteObservation(): void
    {
        $this->createObservation($this->ids, 'obs-del');

        $this->repository->delete([
            ['id' => $this->ids->get('obs-del')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('obs-del')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testDefaultStatus(): void
    {
        $this->repository->create([
            [
                'id' => $this->ids->get('obs-default'),
                'observedAt' => '2026-04-21 10:00:00.000',
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('obs-default')]),
            $this->context
        )->first();

        static::assertSame('draft', $entity->getStatus());
    }

    public function testMiniwaldSetNullOnMiniwaldDelete(): void
    {
        $this->createMiniwald($this->ids, 'mw-del');
        $this->createObservation($this->ids, 'obs-mw', [
            'miniwaldId' => $this->ids->get('mw-del'),
        ]);

        $this->getRepository('miniwald_explorer_miniwald')->delete([
            ['id' => $this->ids->get('mw-del')],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('obs-mw')]),
            $this->context
        )->first();

        static::assertNotNull($entity);
        static::assertNull($entity->getMiniwaldId());
    }

    public function testItemsAssociationLoading(): void
    {
        $this->createObservation($this->ids, 'obs-items');
        $this->createObservationItem($this->ids, 'item-1', 'obs-items');
        $this->createObservationItem($this->ids, 'item-2', 'obs-items');

        $criteria = new Criteria([$this->ids->get('obs-items')]);
        $criteria->addAssociation('items');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getItems());
        static::assertCount(2, $entity->getItems());
    }

    public function testGroundMeasurementAssociationLoading(): void
    {
        $this->createObservation($this->ids, 'obs-gm');
        $this->createGroundMeasurement($this->ids, 'gm-1', 'obs-gm', [
            'locationType' => GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER,
            'airTemperature' => 22.5,
        ]);

        $criteria = new Criteria([$this->ids->get('obs-gm')]);
        $criteria->addAssociation('groundMeasurements');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getGroundMeasurements());
        static::assertCount(1, $entity->getGroundMeasurements());
        static::assertSame(22.5, $entity->getGroundMeasurements()->first()->getAirTemperature());
    }

    public function testPlantMeasurementAssociationLoading(): void
    {
        $this->createObservation($this->ids, 'obs-pm');
        $this->createPlantMeasurement($this->ids, 'pm-1', 'obs-pm', [
            'heightCm' => 350,
        ]);

        $criteria = new Criteria([$this->ids->get('obs-pm')]);
        $criteria->addAssociation('plantMeasurements');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getPlantMeasurements());
        static::assertCount(1, $entity->getPlantMeasurements());
        static::assertSame(350, $entity->getPlantMeasurements()->first()->getHeightCm());
    }

    public function testSearchByStatus(): void
    {
        $this->createObservation($this->ids, 'obs-draft', ['status' => 'draft']);
        $this->createObservation($this->ids, 'obs-submitted', ['status' => 'submitted']);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('status', 'submitted'));
        $criteria->addFilter(new EqualsFilter('id', $this->ids->get('obs-submitted')));

        $result = $this->repository->search($criteria, $this->context);

        static::assertCount(1, $result);
        static::assertSame('submitted', $result->first()->getStatus());
    }

    public function testSearchByObservedAtRange(): void
    {
        $this->createObservation($this->ids, 'obs-old', [
            'observedAt' => '2026-01-01 10:00:00.000',
        ]);
        $this->createObservation($this->ids, 'obs-new', [
            'observedAt' => '2026-06-15 10:00:00.000',
        ]);

        $criteria = new Criteria([
            $this->ids->get('obs-old'),
            $this->ids->get('obs-new'),
        ]);
        $criteria->addFilter(new RangeFilter('observedAt', [
            RangeFilter::GTE => '2026-06-01 00:00:00.000',
        ]));

        $result = $this->repository->search($criteria, $this->context);

        static::assertCount(1, $result);
        static::assertSame($this->ids->get('obs-new'), $result->first()->getId());
    }
}
