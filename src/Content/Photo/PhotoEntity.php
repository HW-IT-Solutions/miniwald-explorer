<?php declare(strict_types=1);

namespace InReNa\Content\Photo;

use InReNa\Content\ObservationItem\ObservationItemEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Photo Entity
 *
 * Stores photos attached to observation items.
 */
class PhotoEntity extends Entity
{
    use EntityIdTrait;

    protected string $observationItemId;

    protected string $mediaId;

    protected ?string $caption = null;

    protected int $sortOrder = 0;

    protected ?bool $isApproved = null;

    protected ?\DateTimeInterface $approvedAt = null;

    protected ?string $approvedBy = null;

    protected ?string $rejectionReason = null;

    // Associations
    protected ?ObservationItemEntity $observationItem = null;

    protected ?MediaEntity $media = null;

    public function getObservationItemId(): string
    {
        return $this->observationItemId;
    }

    public function setObservationItemId(string $observationItemId): void
    {
        $this->observationItemId = $observationItemId;
    }

    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    public function setMediaId(string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): void
    {
        $this->caption = $caption;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getIsApproved(): ?bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(?bool $isApproved): void
    {
        $this->isApproved = $isApproved;
    }

    /**
     * Check if photo is pending moderation.
     */
    public function isPending(): bool
    {
        return $this->isApproved === null;
    }

    /**
     * Check if photo was rejected.
     */
    public function isRejected(): bool
    {
        return $this->isApproved === false;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeInterface $approvedAt): void
    {
        $this->approvedAt = $approvedAt;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?string $approvedBy): void
    {
        $this->approvedBy = $approvedBy;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): void
    {
        $this->rejectionReason = $rejectionReason;
    }

    public function getObservationItem(): ?ObservationItemEntity
    {
        return $this->observationItem;
    }

    public function setObservationItem(?ObservationItemEntity $observationItem): void
    {
        $this->observationItem = $observationItem;
    }

    public function getMedia(): ?MediaEntity
    {
        return $this->media;
    }

    public function setMedia(?MediaEntity $media): void
    {
        $this->media = $media;
    }

    /**
     * Get the URL of the photo.
     */
    public function getUrl(): ?string
    {
        return $this->media?->getUrl();
    }

    /**
     * Get the thumbnail URL of the photo.
     */
    public function getThumbnailUrl(): ?string
    {
        $thumbnails = $this->media?->getThumbnails();
        if ($thumbnails === null || $thumbnails->count() === 0) {
            return $this->getUrl();
        }

        // Return the first (smallest) thumbnail
        return $thumbnails->first()?->getUrl();
    }
}
