<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\PlantMeasurement;

use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Content\Plant\PlantDefinition;
use InReNa\Content\PlantMeasurement\PlantMeasurementCollection;
use InReNa\Content\PlantMeasurement\PlantMeasurementDefinition;
use InReNa\Content\PlantMeasurement\PlantMeasurementEntity;
use InReNa\Tests\Unit\Content\DefinitionTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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

#[CoversClass(PlantMeasurementDefinition::class)]
class PlantMeasurementDefinitionTest extends TestCase
{
    use DefinitionTestTrait;

    private PlantMeasurementDefinition $definition;

    protected function setUp(): void
    {
        $registry = self::getRegistry();
        $this->definition = $registry->getByEntityName('miniwald_explorer_plant_measurement');
    }

    public function testEntityName(): void
    {
        static::assertSame('miniwald_explorer_plant_measurement', $this->definition->getEntityName());
        static::assertSame('miniwald_explorer_plant_measurement', PlantMeasurementDefinition::ENTITY_NAME);
    }

    public function testEntityClass(): void
    {
        static::assertSame(PlantMeasurementEntity::class, $this->definition->getEntityClass());
    }

    public function testCollectionClass(): void
    {
        static::assertSame(PlantMeasurementCollection::class, $this->definition->getCollectionClass());
    }

    public function testFieldsContainId(): void
    {
        $fields = $this->definition->getFields();
        $idField = $fields->get('id');

        static::assertInstanceOf(IdField::class, $idField);
        static::assertTrue($idField->is(PrimaryKey::class));
        static::assertTrue($idField->is(Required::class));
    }

    public function testFieldsContainObservationId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('observationId');

        static::assertInstanceOf(FkField::class, $field);
        static::assertTrue($field->is(Required::class));
    }

    public function testFieldsContainPlantId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('plantId');

        static::assertInstanceOf(FkField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
        // plantId is optional
        static::assertFalse($field->is(Required::class));
    }

    public function testFieldsContainHeightCm(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(IntField::class, $fields->get('heightCm'));
    }

    public function testFieldsContainTrunkCircumferenceCm(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(FloatField::class, $fields->get('trunkCircumferenceCm'));
    }

    public function testFieldsContainCrownDiameterCm(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(IntField::class, $fields->get('crownDiameterCm'));
    }

    public function testFieldsContainCustomPlantName(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('customPlantName');

        static::assertInstanceOf(StringField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
        static::assertFalse($field->is(Required::class));
    }

    public function testFieldsContainRemarks(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(LongTextField::class, $fields->get('remarks'));
    }

    public function testFieldsContainObservationAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('observation');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(ObservationDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainPlantAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('plant');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(PlantDefinition::class, $field->getReferenceDefinition()->getClass());
    }
}
