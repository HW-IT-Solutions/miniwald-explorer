<?php declare(strict_types=1);

namespace InReNa\Content\PlantMeasurement;

use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Content\Plant\PlantDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * PlantMeasurement Entity Definition
 *
 * Stores plant measurements for an observation.
 * OneToOne relationship with Observation.
 *
 * Fields:
 * - observation_id: FK to parent Observation (required, unique)
 * - plant_id: FK to Plant being measured (optional)
 * - height_cm: Plant height in centimeters (integer)
 * - trunk_circumference_cm: Trunk circumference in centimeters (decimal)
 * - crown_diameter_cm: Crown diameter in centimeters (integer)
 * - remarks: Additional notes
 */
class PlantMeasurementDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'miniwald_explorer_plant_measurement';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PlantMeasurementEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PlantMeasurementCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),

            // Parent observation (required, unique for OneToOne)
            (new FkField('observation_id', 'observationId', ObservationDefinition::class))->addFlags(new ApiAware(), new Required()),

            // Plant being measured (optional - can measure without specific plant reference)
            (new FkField('plant_id', 'plantId', PlantDefinition::class))->addFlags(new ApiAware()),

            // Custom plant name for plants not in catalog
            (new StringField('custom_plant_name', 'customPlantName', 255))->addFlags(new ApiAware()),

            // Height measurement in centimeters
            (new IntField('height_cm', 'heightCm', 0))->addFlags(new ApiAware()),

            // Trunk circumference in centimeters (decimal for precision)
            (new FloatField('trunk_circumference_cm', 'trunkCircumferenceCm'))->addFlags(new ApiAware()),

            // Crown diameter in centimeters
            (new IntField('crown_diameter_cm', 'crownDiameterCm', 0))->addFlags(new ApiAware()),

            // Additional remarks
            (new LongTextField('remarks', 'remarks'))->addFlags(new ApiAware()),

            // Associations
            (new ManyToOneAssociationField('observation', 'observation_id', ObservationDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('plant', 'plant_id', PlantDefinition::class, 'id', false))->addFlags(new ApiAware()),
        ]);
    }
}
