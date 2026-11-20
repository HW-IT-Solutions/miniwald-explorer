<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content\ObservationItem;

use InReNa\Content\ObservationItem\ObservationItemDefinition;
use InReNa\Content\ObservationItem\ObservationItemEntity;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

#[CoversClass(ObservationItemDefinition::class)]
class ObservationItemRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private EntityRepository $repository;
    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository('miniwald_explorer_observation_item');
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testCreateObservationItem(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-1', 'obs', [
            'count' => 3,
            'remarks' => 'Spotted near the pond',
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('item-1')]),
            $this->context
        )->first();

        static::assertInstanceOf(ObservationItemEntity::class, $entity);
        static::assertSame(3, $entity->getCount());
        static::assertSame('Spotted near the pond', $entity->getRemarks());
    }

    public function testCreateWithSpecies(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat');
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-sp', 'obs', [
            'speciesId' => $this->ids->get('sp'),
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('item-sp')]),
            $this->context
        )->first();

        static::assertSame($this->ids->get('sp'), $entity->getSpeciesId());
    }

    public function testCreateWithSubspecies(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat');
        $this->createSubspecies($this->ids, 'sub', 'sp');
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-sub', 'obs', [
            'speciesId' => $this->ids->get('sp'),
            'subspeciesId' => $this->ids->get('sub'),
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('item-sub')]),
            $this->context
        )->first();

        static::assertSame($this->ids->get('sub'), $entity->getSubspeciesId());
    }

    public function testCreateWithCustomSpeciesName(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-custom', 'obs', [
            'customSpeciesName' => 'Unknown butterfly',
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('item-custom')]),
            $this->context
        )->first();

        static::assertSame('Unknown butterfly', $entity->getCustomSpeciesName());
    }

    public function testCreateRequiresObservationId(): void
    {
        $this->expectException(\Throwable::class);

        $this->repository->create([
            [
                'id' => $this->ids->get('item-no-obs'),
                'count' => 1,
            ],
        ], $this->context);
    }

    public function testUpdateObservationItem(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-upd', 'obs', ['count' => 1]);

        $this->repository->update([
            [
                'id' => $this->ids->get('item-upd'),
                'count' => 5,
                'remarks' => 'Updated remarks',
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('item-upd')]),
            $this->context
        )->first();

        static::assertSame(5, $entity->getCount());
        static::assertSame('Updated remarks', $entity->getRemarks());
    }

    public function testDeleteObservationItem(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-del', 'obs');

        $this->repository->delete([
            ['id' => $this->ids->get('item-del')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('item-del')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testCascadeDeleteFromObservation(): void
    {
        $this->createObservation($this->ids, 'obs-cascade');
        $this->createObservationItem($this->ids, 'item-cascade', 'obs-cascade');

        $this->getRepository('miniwald_explorer_observation')->delete([
            ['id' => $this->ids->get('obs-cascade')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('item-cascade')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testSpeciesSetNullOnSpeciesDelete(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp-del', 'cat');
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-sp-del', 'obs', [
            'speciesId' => $this->ids->get('sp-del'),
        ]);

        $this->getRepository('miniwald_explorer_species')->delete([
            ['id' => $this->ids->get('sp-del')],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('item-sp-del')]),
            $this->context
        )->first();

        static::assertNotNull($entity);
        static::assertNull($entity->getSpeciesId());
    }

    public function testSubspeciesSetNullOnSubspeciesDelete(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat');
        $this->createSubspecies($this->ids, 'sub-del', 'sp');
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-sub-del', 'obs', [
            'speciesId' => $this->ids->get('sp'),
            'subspeciesId' => $this->ids->get('sub-del'),
        ]);

        $this->getRepository('miniwald_explorer_subspecies')->delete([
            ['id' => $this->ids->get('sub-del')],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('item-sub-del')]),
            $this->context
        )->first();

        static::assertNotNull($entity);
        static::assertNull($entity->getSubspeciesId());
    }

    public function testPhotosAssociationLoading(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-photos', 'obs');
        $this->createMedia($this->ids, 'media-1');
        $this->createMedia($this->ids, 'media-2');
        $this->createPhoto($this->ids, 'photo-1', 'item-photos', 'media-1');
        $this->createPhoto($this->ids, 'photo-2', 'item-photos', 'media-2');

        $criteria = new Criteria([$this->ids->get('item-photos')]);
        $criteria->addAssociation('photos');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getPhotos());
        static::assertCount(2, $entity->getPhotos());
    }

    public function testCountIsRequired(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->repository->create([
            [
                'id' => $this->ids->get('item-count'),
                'observationId' => $this->ids->get('obs'),
                'count' => 5,
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('item-count')]),
            $this->context
        )->first();

        static::assertSame(5, $entity->getCount());
    }
}
