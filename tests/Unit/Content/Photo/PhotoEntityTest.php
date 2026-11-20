<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Photo;

use InReNa\Content\ObservationItem\ObservationItemEntity;
use InReNa\Content\Photo\PhotoEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailCollection;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailEntity;

#[CoversClass(PhotoEntity::class)]
class PhotoEntityTest extends TestCase
{
    private PhotoEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new PhotoEntity();
    }

    public function testDefaultValues(): void
    {
        static::assertNull($this->entity->getCaption());
        static::assertSame(0, $this->entity->getSortOrder());
        static::assertNull($this->entity->getIsApproved());
        static::assertNull($this->entity->getApprovedAt());
        static::assertNull($this->entity->getApprovedBy());
        static::assertNull($this->entity->getRejectionReason());
        static::assertNull($this->entity->getObservationItem());
        static::assertNull($this->entity->getMedia());
    }

    public function testGetSetObservationItemId(): void
    {
        $uuid = 'item-uuid-1234';
        $this->entity->setObservationItemId($uuid);
        static::assertSame($uuid, $this->entity->getObservationItemId());
    }

    public function testGetSetMediaId(): void
    {
        $uuid = 'media-uuid-5678';
        $this->entity->setMediaId($uuid);
        static::assertSame($uuid, $this->entity->getMediaId());
    }

    public function testGetSetCaption(): void
    {
        $caption = 'Siebenpunkt-Marienkäfer auf einem Blatt';
        $this->entity->setCaption($caption);
        static::assertSame($caption, $this->entity->getCaption());
    }

    public function testGetSetCaptionNull(): void
    {
        $this->entity->setCaption(null);
        static::assertNull($this->entity->getCaption());
    }

    public function testGetSetSortOrder(): void
    {
        $this->entity->setSortOrder(3);
        static::assertSame(3, $this->entity->getSortOrder());
    }

    public function testGetSetSortOrderZero(): void
    {
        $this->entity->setSortOrder(0);
        static::assertSame(0, $this->entity->getSortOrder());
    }

    public function testGetSetIsApproved(): void
    {
        $this->entity->setIsApproved(true);
        static::assertTrue($this->entity->getIsApproved());
    }

    public function testGetSetIsApprovedFalse(): void
    {
        $this->entity->setIsApproved(false);
        static::assertFalse($this->entity->getIsApproved());
    }

    public function testGetSetIsApprovedNull(): void
    {
        $this->entity->setIsApproved(null);
        static::assertNull($this->entity->getIsApproved());
    }

    public function testIsPendingWhenNull(): void
    {
        $this->entity->setIsApproved(null);
        static::assertTrue($this->entity->isPending());
    }

    public function testIsPendingWhenApproved(): void
    {
        $this->entity->setIsApproved(true);
        static::assertFalse($this->entity->isPending());
    }

    public function testIsPendingWhenRejected(): void
    {
        $this->entity->setIsApproved(false);
        static::assertFalse($this->entity->isPending());
    }

    public function testIsRejectedWhenFalse(): void
    {
        $this->entity->setIsApproved(false);
        static::assertTrue($this->entity->isRejected());
    }

    public function testIsRejectedWhenApproved(): void
    {
        $this->entity->setIsApproved(true);
        static::assertFalse($this->entity->isRejected());
    }

    public function testIsRejectedWhenNull(): void
    {
        $this->entity->setIsApproved(null);
        static::assertFalse($this->entity->isRejected());
    }

    public function testGetSetApprovedAt(): void
    {
        $date = new \DateTimeImmutable('2026-04-21 16:00:00');
        $this->entity->setApprovedAt($date);
        static::assertSame($date, $this->entity->getApprovedAt());
    }

    public function testGetSetApprovedAtNull(): void
    {
        $this->entity->setApprovedAt(null);
        static::assertNull($this->entity->getApprovedAt());
    }

    public function testGetSetApprovedBy(): void
    {
        $this->entity->setApprovedBy('admin-user-id');
        static::assertSame('admin-user-id', $this->entity->getApprovedBy());
    }

    public function testGetSetApprovedByNull(): void
    {
        $this->entity->setApprovedBy(null);
        static::assertNull($this->entity->getApprovedBy());
    }

    public function testGetSetRejectionReason(): void
    {
        $reason = 'Foto ist unscharf und nicht erkennbar';
        $this->entity->setRejectionReason($reason);
        static::assertSame($reason, $this->entity->getRejectionReason());
    }

    public function testGetSetRejectionReasonNull(): void
    {
        $this->entity->setRejectionReason(null);
        static::assertNull($this->entity->getRejectionReason());
    }

    public function testGetSetObservationItem(): void
    {
        $item = new ObservationItemEntity();
        $item->setId('item-id');
        $this->entity->setObservationItem($item);
        static::assertSame($item, $this->entity->getObservationItem());
    }

    public function testGetSetObservationItemNull(): void
    {
        $this->entity->setObservationItem(null);
        static::assertNull($this->entity->getObservationItem());
    }

    public function testGetSetMedia(): void
    {
        $media = new MediaEntity();
        $media->setId('media-id');
        $this->entity->setMedia($media);
        static::assertSame($media, $this->entity->getMedia());
    }

    public function testGetSetMediaNull(): void
    {
        $this->entity->setMedia(null);
        static::assertNull($this->entity->getMedia());
    }

    public function testGetUrlReturnsMediaUrl(): void
    {
        $media = $this->createStub(MediaEntity::class);
        $media->method('getUrl')->willReturn('https://example.com/photo.jpg');
        $this->entity->setMedia($media);
        static::assertSame('https://example.com/photo.jpg', $this->entity->getUrl());
    }

    public function testGetUrlReturnsNullWhenNoMedia(): void
    {
        static::assertNull($this->entity->getUrl());
    }

    public function testGetThumbnailUrlReturnsThumbnailUrl(): void
    {
        $thumbnail = $this->createStub(MediaThumbnailEntity::class);
        $thumbnail->method('getUrl')->willReturn('https://example.com/thumb.jpg');

        $thumbnails = new MediaThumbnailCollection([$thumbnail]);

        $media = $this->createStub(MediaEntity::class);
        $media->method('getThumbnails')->willReturn($thumbnails);
        $media->method('getUrl')->willReturn('https://example.com/photo.jpg');

        $this->entity->setMedia($media);
        static::assertSame('https://example.com/thumb.jpg', $this->entity->getThumbnailUrl());
    }

    public function testGetThumbnailUrlFallsBackToMainUrl(): void
    {
        $thumbnails = new MediaThumbnailCollection();

        $media = $this->createStub(MediaEntity::class);
        $media->method('getThumbnails')->willReturn($thumbnails);
        $media->method('getUrl')->willReturn('https://example.com/photo.jpg');

        $this->entity->setMedia($media);
        static::assertSame('https://example.com/photo.jpg', $this->entity->getThumbnailUrl());
    }

    public function testGetThumbnailUrlReturnsNullWhenNoMedia(): void
    {
        static::assertNull($this->entity->getThumbnailUrl());
    }

    public function testGetThumbnailUrlWhenThumbnailsNull(): void
    {
        $media = $this->createStub(MediaEntity::class);
        $media->method('getThumbnails')->willReturn(null);
        $media->method('getUrl')->willReturn('https://example.com/photo.jpg');

        $this->entity->setMedia($media);
        static::assertSame('https://example.com/photo.jpg', $this->entity->getThumbnailUrl());
    }

    public function testModerationWorkflow(): void
    {
        // Initially pending
        static::assertTrue($this->entity->isPending());
        static::assertFalse($this->entity->isRejected());

        // Approve
        $this->entity->setIsApproved(true);
        $this->entity->setApprovedAt(new \DateTimeImmutable('2026-04-21'));
        $this->entity->setApprovedBy('admin-123');
        static::assertFalse($this->entity->isPending());
        static::assertFalse($this->entity->isRejected());
        static::assertTrue($this->entity->getIsApproved());

        // Reject
        $this->entity->setIsApproved(false);
        $this->entity->setRejectionReason('Unscharf');
        static::assertFalse($this->entity->isPending());
        static::assertTrue($this->entity->isRejected());
        static::assertSame('Unscharf', $this->entity->getRejectionReason());
    }
}
