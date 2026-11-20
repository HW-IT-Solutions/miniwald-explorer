<?php declare(strict_types=1);

namespace InReNa\Content\Photo;

use InReNa\Content\ObservationItem\ObservationItemDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Photo Entity Definition
 *
 * Stores photos attached to observation items.
 * Photos are linked to ObservationItem (not directly to Observation).
 *
 * Fields:
 * - observation_item_id: FK to parent ObservationItem (required)
 * - media_id: FK to Shopware Media entity (required)
 * - caption: Optional caption/description
 * - sort_order: Display order
 * - is_approved: Moderation status (for photo moderation workflow)
 * - approved_at: When the photo was approved
 * - approved_by: Who approved the photo (admin user ID)
 * - rejection_reason: If rejected, why
 */
class PhotoDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'miniwald_explorer_photo';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PhotoEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PhotoCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),

            // Parent observation item (required)
            (new FkField('observation_item_id', 'observationItemId', ObservationItemDefinition::class))->addFlags(new ApiAware(), new Required()),

            // Shopware media reference (required)
            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new ApiAware(), new Required()),

            // Optional caption
            (new StringField('caption', 'caption', 500))->addFlags(new ApiAware()),

            // Display order
            (new IntField('sort_order', 'sortOrder', 0))->addFlags(new ApiAware()),

            // Moderation fields
            (new BoolField('is_approved', 'isApproved'))->addFlags(new ApiAware()),
            (new DateTimeField('approved_at', 'approvedAt'))->addFlags(new ApiAware()),
            (new StringField('approved_by', 'approvedBy', 255))->addFlags(new ApiAware()),
            (new LongTextField('rejection_reason', 'rejectionReason'))->addFlags(new ApiAware()),

            // Associations
            (new ManyToOneAssociationField('observationItem', 'observation_item_id', ObservationItemDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
        ]);
    }
}
