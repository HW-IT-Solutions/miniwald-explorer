<?php declare(strict_types=1);

namespace InReNa\Content\GroundMeasurement;

use InReNa\Content\Observation\ObservationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * GroundMeasurement Entity Definition
 *
 * Stores ground/soil measurements for an observation.
 * ManyToOne relationship with Observation (1–3 items per observation, one per location type).
 *
 * Fields:
 * - observation_id: FK to parent Observation (required)
 * - location_type: where the measurement was taken (wood_center, wood_edge, street_curb)
 * - air_temperature: Air temperature in °C (decimal)
 * - ground_temperature: Ground/soil temperature in °C (decimal)
 * - moisture: Soil moisture in percent, integer 0–100
 * - humidity: Air humidity in percent, integer 0–100
 * - ph_value: Soil pH value (float 1.0–14.0, one decimal)
 * - light_conditions: sunny or shady
 * - remarks: Additional notes
 */
class GroundMeasurementDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'miniwald_explorer_ground_measurement';

    // Light conditions enum values
    public const LIGHT_SUNNY = 'sunny';
    public const LIGHT_SHADY = 'shady';

    public const LIGHT_VALUES = [
        self::LIGHT_SUNNY,
        self::LIGHT_SHADY,
    ];

    public const LIGHT_LABELS = [
        self::LIGHT_SUNNY => 'Sonnig',
        self::LIGHT_SHADY => 'Schattig',
    ];

    // Location type enum values
    public const LOCATION_TYPE_WOOD_CENTER = 'wood_center';
    public const LOCATION_TYPE_WOOD_EDGE = 'wood_edge';
    public const LOCATION_TYPE_STREET_CURB = 'street_curb';

    public const LOCATION_TYPE_VALUES = [
        self::LOCATION_TYPE_WOOD_CENTER,
        self::LOCATION_TYPE_WOOD_EDGE,
        self::LOCATION_TYPE_STREET_CURB,
    ];

    public const LOCATION_TYPE_LABELS = [
        self::LOCATION_TYPE_WOOD_CENTER => 'Waldmitte',
        self::LOCATION_TYPE_WOOD_EDGE => 'Waldrand',
        self::LOCATION_TYPE_STREET_CURB => 'Straßenrand',
    ];

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return GroundMeasurementEntity::class;
    }

    public function getCollectionClass(): string
    {
        return GroundMeasurementCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),

            // Parent observation (required)
            (new FkField('observation_id', 'observationId', ObservationDefinition::class))->addFlags(new ApiAware(), new Required()),

            // Location type: where the measurement was taken (required)
            (new StringField('location_type', 'locationType', 30))->addFlags(new ApiAware(), new Required()),

            // Temperature measurements (decimal for precision)
            (new FloatField('air_temperature', 'airTemperature'))->addFlags(new ApiAware()),
            (new FloatField('ground_temperature', 'groundTemperature'))->addFlags(new ApiAware()),

            // Soil moisture in percent (float, e.g. 67.5%)
            (new FloatField('moisture', 'moisture'))->addFlags(new ApiAware()),

            // Air humidity in percent (float, e.g. 45.2%)
            (new FloatField('humidity', 'humidity'))->addFlags(new ApiAware()),

            // pH value (float 1.0–14.0, one decimal place)
            (new FloatField('ph_value', 'phValue'))->addFlags(new ApiAware()),

            // Light conditions (enum: sunny, shady)
            (new StringField('light_conditions', 'lightConditions', 20))->addFlags(new ApiAware()),

            // Additional remarks
            (new LongTextField('remarks', 'remarks'))->addFlags(new ApiAware()),

            // Association back to observation
            (new ManyToOneAssociationField('observation', 'observation_id', ObservationDefinition::class, 'id', false))->addFlags(new ApiAware()),
        ]);
    }
}
