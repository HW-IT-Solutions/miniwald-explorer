<?php declare(strict_types=1);

namespace InReNa\Content\Miniwald;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MiniwaldDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'miniwald_explorer_miniwald';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return MiniwaldEntity::class;
    }

    public function getCollectionClass(): string
    {
        return MiniwaldCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),
            (new StringField('name', 'name'))->addFlags(new Required(), new ApiAware()),
            (new FloatField('latitude', 'latitude'))->addFlags(new ApiAware()),
            (new FloatField('longitude', 'longitude'))->addFlags(new ApiAware()),
            (new IntField('radius_meters', 'radiusMeters'))->addFlags(new ApiAware()),
            (new FkField('image_id', 'imageId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new StringField('photo_caption', 'photoCaption', 500))->addFlags(new ApiAware()),
            (new StringField('address', 'address', 500))->addFlags(new ApiAware()),

            // Translatable fields
            (new TranslatedField('description'))->addFlags(new ApiAware()),

            // Associations
            (new ManyToOneAssociationField('image', 'image_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(MiniwaldTranslationDefinition::class, 'miniwald_explorer_miniwald_id'))->addFlags(new Required()),
        ]);
    }
}
