<?php declare(strict_types=1);

namespace InReNa\Content\ObservationSheet;

use InReNa\Content\SpeciesCategory\SpeciesCategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Entity definition for PDF observation sheets.
 *
 * Three types exist:
 * - `animal`  — one PDF per species category (linked via species_category_id)
 * - `plant`   — one fixed PDF for plant measurements
 * - `ground`  — one fixed PDF for ground/air measurements
 *
 * The `media_id` references the uploaded PDF in Shopware's media system.
 */
class ObservationSheetDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'miniwald_explorer_observation_sheet';

    /**
     * Animal observation sheet (one per species category)
     */
    public const TYPE_ANIMAL = 'animal';

    /**
     * Plant measurement sheet
     */
    public const TYPE_PLANT = 'plant';

    /**
     * Ground/air measurement sheet
     */
    public const TYPE_GROUND = 'ground';

    public const ALL_TYPES = [
        self::TYPE_ANIMAL,
        self::TYPE_PLANT,
        self::TYPE_GROUND,
    ];

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ObservationSheetEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ObservationSheetCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('type', 'type', 50))->addFlags(new Required()),
            new FkField('species_category_id', 'speciesCategoryId', SpeciesCategoryDefinition::class),
            new FkField('media_id', 'mediaId', MediaDefinition::class),
            new ManyToOneAssociationField('speciesCategory', 'species_category_id', SpeciesCategoryDefinition::class, 'id', false),
            new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', false),
        ]);
    }
}
