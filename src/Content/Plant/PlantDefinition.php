<?php declare(strict_types=1);

namespace InReNa\Content\Plant;

use InReNa\Content\Miniwald\MiniwaldDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PlantDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'miniwald_explorer_plant';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PlantEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PlantCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),

            (new FkField('miniwald_id', 'miniwaldId', MiniwaldDefinition::class))->addFlags(new ApiAware()),

            (new TranslatedField('name'))->addFlags(new ApiAware()),
            (new StringField('scientific_name', 'scientificName'))->addFlags(new ApiAware()),

            (new FkField('image_id', 'imageId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new StringField('position', 'position'))->addFlags(new ApiAware()), // Grid position e.g. "A1", "B3"
            (new IntField('sort_order', 'sortOrder'))->addFlags(new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),

            // Associations
            (new ManyToOneAssociationField('miniwald', 'miniwald_id', MiniwaldDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('image', 'image_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(PlantTranslationDefinition::class, 'miniwald_explorer_plant_id'))->addFlags(new Required()),
        ]);
    }
}
