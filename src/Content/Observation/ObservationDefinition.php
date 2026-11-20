<?php declare(strict_types=1);

namespace InReNa\Content\Observation;

use InReNa\Content\GroundMeasurement\GroundMeasurementDefinition;
use InReNa\Content\Miniwald\MiniwaldDefinition;
use InReNa\Content\ObservationItem\ObservationItemDefinition;
use InReNa\Content\PlantMeasurement\PlantMeasurementDefinition;
use InReNa\Content\SpeciesCategory\SpeciesCategoryDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ObservationDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'miniwald_explorer_observation';

    public const TYPE_ANIMAL = 'animal';
    public const TYPE_PLANT = 'plant';
    public const TYPE_GROUND = 'ground';

    public const TYPES = [self::TYPE_ANIMAL, self::TYPE_PLANT, self::TYPE_GROUND];

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ObservationEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ObservationCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),

            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new ApiAware()),
            (new FkField('miniwald_id', 'miniwaldId', MiniwaldDefinition::class))->addFlags(new ApiAware()),

            // Type enum: animal, plant, ground
            (new StringField('type', 'type', 20))->addFlags(new ApiAware()),
            // Species category the observation was recorded for (animal type only);
            // authoritative for items with only a custom species name
            (new FkField('species_category_id', 'speciesCategoryId', SpeciesCategoryDefinition::class))->addFlags(new ApiAware()),

            (new DateTimeField('observed_at', 'observedAt'))->addFlags(new Required(), new ApiAware()),

            (new FloatField('latitude', 'latitude'))->addFlags(new ApiAware()),
            (new FloatField('longitude', 'longitude'))->addFlags(new ApiAware()),

            // Weather enum: sunny, partly_cloudy, cloudy, rainy
            (new StringField('weather', 'weather'))->addFlags(new ApiAware()),
            // Wind enum: calm, light_wind, moderate_wind, strong_wind
            (new StringField('wind', 'wind'))->addFlags(new ApiAware()),

            (new LongTextField('remarks', 'remarks'))->addFlags(new ApiAware()),
            (new StringField('status', 'status'))->addFlags(new ApiAware()),
            (new DateTimeField('submitted_at', 'submittedAt'))->addFlags(new ApiAware()),

            // Associations
            (new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('miniwald', 'miniwald_id', MiniwaldDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('speciesCategory', 'species_category_id', SpeciesCategoryDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new OneToManyAssociationField('items', ObservationItemDefinition::class, 'observation_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new OneToManyAssociationField('groundMeasurements', GroundMeasurementDefinition::class, 'observation_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new OneToManyAssociationField('plantMeasurements', PlantMeasurementDefinition::class, 'observation_id'))->addFlags(new ApiAware(), new CascadeDelete()),
        ]);
    }
}
