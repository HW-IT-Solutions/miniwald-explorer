<?php declare(strict_types=1);

namespace InReNa\Content\Species;

use InReNa\Content\SpeciesCategory\SpeciesCategoryDefinition;
use InReNa\Content\Subspecies\SubspeciesDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class SpeciesDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'miniwald_explorer_species';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return SpeciesEntity::class;
    }

    public function getCollectionClass(): string
    {
        return SpeciesCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),

            (new FkField('species_category_id', 'speciesCategoryId', SpeciesCategoryDefinition::class))->addFlags(new Required(), new ApiAware()),

            (new TranslatedField('name'))->addFlags(new ApiAware()),
            (new StringField('scientific_name', 'scientificName'))->addFlags(new ApiAware()),
            (new StringField('taxon_id', 'taxonId'))->addFlags(new ApiAware()),
            (new TranslatedField('description'))->addFlags(new ApiAware()),
            (new TranslatedField('identificationTips'))->addFlags(new ApiAware()),

            (new FkField('image_id', 'imageId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new IntField('position', 'position'))->addFlags(new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),

            // Associations
            (new ManyToOneAssociationField('speciesCategory', 'species_category_id', SpeciesCategoryDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('image', 'image_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new OneToManyAssociationField('subspecies', SubspeciesDefinition::class, 'species_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new TranslationsAssociationField(SpeciesTranslationDefinition::class, 'miniwald_explorer_species_id'))->addFlags(new Required()),
        ]);
    }
}
