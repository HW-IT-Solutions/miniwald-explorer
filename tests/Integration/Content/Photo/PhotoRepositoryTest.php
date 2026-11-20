<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content\Photo;

use InReNa\Content\Photo\PhotoDefinition;
use InReNa\Content\Photo\PhotoEntity;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

#[CoversClass(PhotoDefinition::class)]
class PhotoRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private EntityRepository $repository;
    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository('miniwald_explorer_photo');
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testCreatePhoto(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs');
        $this->createMedia($this->ids, 'media');
        $this->createPhoto($this->ids, 'photo-1', 'item', 'media');

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('photo-1')]),
            $this->context
        )->first();

        static::assertInstanceOf(PhotoEntity::class, $entity);
        static::assertSame($this->ids->get('item'), $entity->getObservationItemId());
        static::assertSame($this->ids->get('media'), $entity->getMediaId());
    }

    public function testCreateWithAllFields(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs');
        $this->createMedia($this->ids, 'media');
        $this->createPhoto($this->ids, 'photo-full', 'item', 'media', [
            'caption' => 'A beautiful butterfly',
            'sortOrder' => 3,
            'isApproved' => true,
            'approvedAt' => '2026-04-21 10:00:00.000',
            'approvedBy' => 'admin@example.com',
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('photo-full')]),
            $this->context
        )->first();

        static::assertSame('A beautiful butterfly', $entity->getCaption());
        static::assertSame(3, $entity->getSortOrder());
        static::assertTrue($entity->getIsApproved());
        static::assertNotNull($entity->getApprovedAt());
        static::assertSame('admin@example.com', $entity->getApprovedBy());
    }

    public function testCreateRequiresObservationItemId(): void
    {
        $this->createMedia($this->ids, 'media');

        $this->expectException(\Throwable::class);

        $this->repository->create([
            [
                'id' => $this->ids->get('photo-no-item'),
                'mediaId' => $this->ids->get('media'),
            ],
        ], $this->context);
    }

    public function testCreateRequiresMediaId(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs');

        $this->expectException(\Throwable::class);

        $this->repository->create([
            [
                'id' => $this->ids->get('photo-no-media'),
                'observationItemId' => $this->ids->get('item'),
            ],
        ], $this->context);
    }

    public function testUpdatePhoto(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs');
        $this->createMedia($this->ids, 'media');
        $this->createPhoto($this->ids, 'photo-upd', 'item', 'media');

        $this->repository->update([
            [
                'id' => $this->ids->get('photo-upd'),
                'isApproved' => true,
                'approvedAt' => '2026-04-21 12:00:00.000',
                'approvedBy' => 'moderator@example.com',
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('photo-upd')]),
            $this->context
        )->first();

        static::assertTrue($entity->getIsApproved());
        static::assertSame('moderator@example.com', $entity->getApprovedBy());
    }

    public function testDeletePhoto(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs');
        $this->createMedia($this->ids, 'media');
        $this->createPhoto($this->ids, 'photo-del', 'item', 'media');

        $this->repository->delete([
            ['id' => $this->ids->get('photo-del')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('photo-del')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testCascadeDeleteFromObservationItem(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-cascade', 'obs');
        $this->createMedia($this->ids, 'media');
        $this->createPhoto($this->ids, 'photo-cascade', 'item-cascade', 'media');

        $this->getRepository('miniwald_explorer_observation_item')->delete([
            ['id' => $this->ids->get('item-cascade')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('photo-cascade')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testCascadeDeleteFromMedia(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs');
        $this->createMedia($this->ids, 'media-cascade');
        $this->createPhoto($this->ids, 'photo-media-cascade', 'item', 'media-cascade');

        $this->getRepository('media')->delete([
            ['id' => $this->ids->get('media-cascade')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('photo-media-cascade')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testCascadeDeleteFromObservation(): void
    {
        $this->createObservation($this->ids, 'obs-cascade');
        $this->createObservationItem($this->ids, 'item', 'obs-cascade');
        $this->createMedia($this->ids, 'media');
        $this->createPhoto($this->ids, 'photo-obs-cascade', 'item', 'media');

        $this->getRepository('miniwald_explorer_observation')->delete([
            ['id' => $this->ids->get('obs-cascade')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('photo-obs-cascade')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testDefaultValues(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs');
        $this->createMedia($this->ids, 'media');

        $this->repository->create([
            [
                'id' => $this->ids->get('photo-defaults'),
                'observationItemId' => $this->ids->get('item'),
                'mediaId' => $this->ids->get('media'),
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('photo-defaults')]),
            $this->context
        )->first();

        static::assertSame(0, $entity->getSortOrder());
        static::assertNull($entity->getIsApproved());
    }

    public function testMediaAssociationLoading(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs');
        $this->createMedia($this->ids, 'media');
        $this->createPhoto($this->ids, 'photo-media', 'item', 'media');

        $criteria = new Criteria([$this->ids->get('photo-media')]);
        $criteria->addAssociation('media');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getMedia());
    }

    public function testObservationItemAssociationLoading(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs');
        $this->createMedia($this->ids, 'media');
        $this->createPhoto($this->ids, 'photo-item', 'item', 'media');

        $criteria = new Criteria([$this->ids->get('photo-item')]);
        $criteria->addAssociation('observationItem');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getObservationItem());
        static::assertSame($this->ids->get('item'), $entity->getObservationItem()->getId());
    }
}
