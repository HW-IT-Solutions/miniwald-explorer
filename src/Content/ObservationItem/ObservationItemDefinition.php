<?php declare(strict_types=1);

namespace InReNa\Content\ObservationItem;

use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Content\Photo\PhotoDefinition;
use InReNa\Content\Species\SpeciesDefinition;
use InReNa\Content\Subspecies\SubspeciesDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * ObservationItem Entity Definition
 *
 * Represents a single species observation within an Observation.
 * One Observation can have multiple ObservationItems (e.g., multiple species spotted).
 *
 * Fields:
 * - observation_id: FK to parent Observation (required)
 * - species_id: FK to Species (optional - can be custom species)
 * - subspecies_id: FK to Subspecies (optional)
 * - custom_species_name: For species not in the catalog (optional)
 * - count: Number of individuals observed (required)
 * - remarks: Additional notes about this observation item (optional)
 * - photos: OneToMany relationship to Photo entity
 */
class ObservationItemDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'miniwald_explorer_observation_item';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ObservationItemEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ObservationItemCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),

            // Parent observation (required)
            (new FkField('observation_id', 'observationId', ObservationDefinition::class))->addFlags(new ApiAware(), new Required()),

            // Species reference (optional - can use custom_species_name instead)
            (new FkField('species_id', 'speciesId', SpeciesDefinition::class))->addFlags(new ApiAware()),

            // Subspecies reference (optional)
            (new FkField('subspecies_id', 'subspeciesId', SubspeciesDefinition::class))->addFlags(new ApiAware()),

            // Custom species name for species not in catalog
            (new StringField('custom_species_name', 'customSpeciesName', 255))->addFlags(new ApiAware()),

            // Count of individuals observed
            (new IntField('count', 'count', 1))->addFlags(new ApiAware(), new Required()),

            // Additional remarks/notes
            (new LongTextField('remarks', 'remarks'))->addFlags(new ApiAware()),

            // Associations
            (new ManyToOneAssociationField('observation', 'observation_id', ObservationDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('species', 'species_id', SpeciesDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('subspecies', 'subspecies_id', SubspeciesDefinition::class, 'id', false))->addFlags(new ApiAware()),

            // Photos for this observation item
            (new OneToManyAssociationField('photos', PhotoDefinition::class, 'observation_item_id'))->addFlags(new ApiAware(), new CascadeDelete()),
        ]);
    }
}
