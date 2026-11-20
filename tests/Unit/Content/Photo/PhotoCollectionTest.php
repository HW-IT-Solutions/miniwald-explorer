<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Photo;

use InReNa\Content\Photo\PhotoCollection;
use InReNa\Content\Photo\PhotoEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhotoCollection::class)]
class PhotoCollectionTest extends TestCase
{
    public function testGetExpectedClass(): void
    {
        $collection = new PhotoCollection();
        $method = new \ReflectionMethod($collection, 'getExpectedClass');
        static::assertSame(PhotoEntity::class, $method->invoke($collection));
    }

    public function testGetSortedEmpty(): void
    {
        $collection = new PhotoCollection();
        $sorted = $collection->getSorted();
        static::assertCount(0, $sorted);
        static::assertInstanceOf(PhotoCollection::class, $sorted);
    }

    public function testGetSortedSingle(): void
    {
        $photo = new PhotoEntity();
        $photo->setId('p-1');
        $photo->setObservationItemId('item-1');
        $photo->setMediaId('media-1');
        $photo->setSortOrder(1);

        $collection = new PhotoCollection([$photo]);
        $sorted = $collection->getSorted();
        static::assertCount(1, $sorted);
    }

    public function testGetSortedMultiple(): void
    {
        $p1 = new PhotoEntity();
        $p1->setId('p-1');
        $p1->setObservationItemId('item-1');
        $p1->setMediaId('media-1');
        $p1->setSortOrder(3);

        $p2 = new PhotoEntity();
        $p2->setId('p-2');
        $p2->setObservationItemId('item-1');
        $p2->setMediaId('media-2');
        $p2->setSortOrder(1);

        $p3 = new PhotoEntity();
        $p3->setId('p-3');
        $p3->setObservationItemId('item-1');
        $p3->setMediaId('media-3');
        $p3->setSortOrder(2);

        $collection = new PhotoCollection([$p1, $p2, $p3]);
        $sorted = $collection->getSorted();

        $elements = array_values($sorted->getElements());
        static::assertCount(3, $elements);
        static::assertSame(1, $elements[0]->getSortOrder());
        static::assertSame(2, $elements[1]->getSortOrder());
        static::assertSame(3, $elements[2]->getSortOrder());
    }

    public function testGetApprovedEmpty(): void
    {
        $collection = new PhotoCollection();
        $approved = $collection->getApproved();
        static::assertCount(0, $approved);
    }

    public function testGetApproved(): void
    {
        $p1 = new PhotoEntity();
        $p1->setId('p-1');
        $p1->setObservationItemId('item-1');
        $p1->setMediaId('media-1');
        $p1->setIsApproved(true);

        $p2 = new PhotoEntity();
        $p2->setId('p-2');
        $p2->setObservationItemId('item-1');
        $p2->setMediaId('media-2');
        $p2->setIsApproved(false);

        $p3 = new PhotoEntity();
        $p3->setId('p-3');
        $p3->setObservationItemId('item-1');
        $p3->setMediaId('media-3');
        $p3->setIsApproved(true);

        $p4 = new PhotoEntity();
        $p4->setId('p-4');
        $p4->setObservationItemId('item-1');
        $p4->setMediaId('media-4');
        // null = pending

        $collection = new PhotoCollection([$p1, $p2, $p3, $p4]);
        $approved = $collection->getApproved();

        static::assertCount(2, $approved);
        static::assertInstanceOf(PhotoCollection::class, $approved);
    }

    public function testGetPendingEmpty(): void
    {
        $collection = new PhotoCollection();
        $pending = $collection->getPending();
        static::assertCount(0, $pending);
    }

    public function testGetPending(): void
    {
        $p1 = new PhotoEntity();
        $p1->setId('p-1');
        $p1->setObservationItemId('item-1');
        $p1->setMediaId('media-1');
        $p1->setIsApproved(true);

        $p2 = new PhotoEntity();
        $p2->setId('p-2');
        $p2->setObservationItemId('item-1');
        $p2->setMediaId('media-2');
        // null = pending

        $p3 = new PhotoEntity();
        $p3->setId('p-3');
        $p3->setObservationItemId('item-1');
        $p3->setMediaId('media-3');
        $p3->setIsApproved(false);

        $p4 = new PhotoEntity();
        $p4->setId('p-4');
        $p4->setObservationItemId('item-1');
        $p4->setMediaId('media-4');
        // null = pending

        $collection = new PhotoCollection([$p1, $p2, $p3, $p4]);
        $pending = $collection->getPending();

        static::assertCount(2, $pending);
        static::assertInstanceOf(PhotoCollection::class, $pending);
    }

    public function testGetRejectedEmpty(): void
    {
        $collection = new PhotoCollection();
        $rejected = $collection->getRejected();
        static::assertCount(0, $rejected);
    }

    public function testGetRejected(): void
    {
        $p1 = new PhotoEntity();
        $p1->setId('p-1');
        $p1->setObservationItemId('item-1');
        $p1->setMediaId('media-1');
        $p1->setIsApproved(true);

        $p2 = new PhotoEntity();
        $p2->setId('p-2');
        $p2->setObservationItemId('item-1');
        $p2->setMediaId('media-2');
        $p2->setIsApproved(false);

        $p3 = new PhotoEntity();
        $p3->setId('p-3');
        $p3->setObservationItemId('item-1');
        $p3->setMediaId('media-3');
        // null = pending

        $collection = new PhotoCollection([$p1, $p2, $p3]);
        $rejected = $collection->getRejected();

        static::assertCount(1, $rejected);
        static::assertInstanceOf(PhotoCollection::class, $rejected);
    }

    public function testGetMediaIds(): void
    {
        $p1 = new PhotoEntity();
        $p1->setId('p-1');
        $p1->setObservationItemId('item-1');
        $p1->setMediaId('media-aaa');

        $p2 = new PhotoEntity();
        $p2->setId('p-2');
        $p2->setObservationItemId('item-1');
        $p2->setMediaId('media-bbb');

        $p3 = new PhotoEntity();
        $p3->setId('p-3');
        $p3->setObservationItemId('item-1');
        $p3->setMediaId('media-ccc');

        $collection = new PhotoCollection([$p1, $p2, $p3]);
        $mediaIds = $collection->getMediaIds();

        static::assertCount(3, $mediaIds);
        static::assertContains('media-aaa', $mediaIds);
        static::assertContains('media-bbb', $mediaIds);
        static::assertContains('media-ccc', $mediaIds);
    }

    public function testGetMediaIdsEmpty(): void
    {
        $collection = new PhotoCollection();
        $mediaIds = $collection->getMediaIds();
        static::assertCount(0, $mediaIds);
    }

    public function testGetMediaIdsDeduplicates(): void
    {
        $p1 = new PhotoEntity();
        $p1->setId('p-1');
        $p1->setObservationItemId('item-1');
        $p1->setMediaId('media-same');

        $p2 = new PhotoEntity();
        $p2->setId('p-2');
        $p2->setObservationItemId('item-1');
        $p2->setMediaId('media-same');

        $p3 = new PhotoEntity();
        $p3->setId('p-3');
        $p3->setObservationItemId('item-1');
        $p3->setMediaId('media-different');

        $collection = new PhotoCollection([$p1, $p2, $p3]);
        $mediaIds = $collection->getMediaIds();

        static::assertCount(2, $mediaIds);
        static::assertContains('media-same', $mediaIds);
        static::assertContains('media-different', $mediaIds);
    }

    public function testGetMediaIdsReturnsIndexedArray(): void
    {
        $p1 = new PhotoEntity();
        $p1->setId('p-1');
        $p1->setObservationItemId('item-1');
        $p1->setMediaId('media-aaa');

        $collection = new PhotoCollection([$p1]);
        $mediaIds = $collection->getMediaIds();

        // Should be a sequential array (array_values)
        static::assertSame([0], array_keys($mediaIds));
    }

    public function testModerationFiltersCombined(): void
    {
        $approved = new PhotoEntity();
        $approved->setId('p-approved');
        $approved->setObservationItemId('item-1');
        $approved->setMediaId('media-1');
        $approved->setIsApproved(true);

        $rejected = new PhotoEntity();
        $rejected->setId('p-rejected');
        $rejected->setObservationItemId('item-1');
        $rejected->setMediaId('media-2');
        $rejected->setIsApproved(false);

        $pending = new PhotoEntity();
        $pending->setId('p-pending');
        $pending->setObservationItemId('item-1');
        $pending->setMediaId('media-3');
        // isApproved = null (pending)

        $collection = new PhotoCollection([$approved, $rejected, $pending]);

        static::assertCount(1, $collection->getApproved());
        static::assertCount(1, $collection->getRejected());
        static::assertCount(1, $collection->getPending());

        // Total should be 3
        static::assertCount(3, $collection);
    }
}
