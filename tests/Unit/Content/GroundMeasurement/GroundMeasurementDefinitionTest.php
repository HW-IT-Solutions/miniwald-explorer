<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\GroundMeasurement;

use InReNa\Content\GroundMeasurement\GroundMeasurementCollection;
use InReNa\Content\GroundMeasurement\GroundMeasurementDefinition;
use InReNa\Content\GroundMeasurement\GroundMeasurementEntity;
use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Tests\Unit\Content\DefinitionTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

#[CoversClass(GroundMeasurementDefinition::class)]
class GroundMeasurementDefinitionTest extends TestCase
{
    use DefinitionTestTrait;

    private GroundMeasurementDefinition $definition;

    protected function setUp(): void
    {
        $registry = self::getRegistry();
        $this->definition = $registry->getByEntityName('miniwald_explorer_ground_measurement');
    }

    public function testEntityName(): void
    {
        static::assertSame('miniwald_explorer_ground_measurement', $this->definition->getEntityName());
        static::assertSame('miniwald_explorer_ground_measurement', GroundMeasurementDefinition::ENTITY_NAME);
    }

    public function testEntityClass(): void
    {
        static::assertSame(GroundMeasurementEntity::class, $this->definition->getEntityClass());
    }

    public function testCollectionClass(): void
    {
        static::assertSame(GroundMeasurementCollection::class, $this->definition->getCollectionClass());
    }

    public function testLocationTypeConstants(): void
    {
        static::assertSame('wood_center', GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER);
        static::assertSame('wood_edge', GroundMeasurementDefinition::LOCATION_TYPE_WOOD_EDGE);
        static::assertSame('street_curb', GroundMeasurementDefinition::LOCATION_TYPE_STREET_CURB);
    }

    public function testLocationTypeValuesArray(): void
    {
        static::assertCount(3, GroundMeasurementDefinition::LOCATION_TYPE_VALUES);
        static::assertContains('wood_center', GroundMeasurementDefinition::LOCATION_TYPE_VALUES);
        static::assertContains('wood_edge', GroundMeasurementDefinition::LOCATION_TYPE_VALUES);
        static::assertContains('street_curb', GroundMeasurementDefinition::LOCATION_TYPE_VALUES);
    }

    public function testLocationTypeLabelsArray(): void
    {
        static::assertCount(3, GroundMeasurementDefinition::LOCATION_TYPE_LABELS);
        static::assertSame('Waldmitte', GroundMeasurementDefinition::LOCATION_TYPE_LABELS['wood_center']);
        static::assertSame('Waldrand', GroundMeasurementDefinition::LOCATION_TYPE_LABELS['wood_edge']);
        static::assertSame('Straßenrand', GroundMeasurementDefinition::LOCATION_TYPE_LABELS['street_curb']);
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

    public function testFieldsContainLocationType(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('locationType');

        static::assertInstanceOf(StringField::class, $field);
        static::assertTrue($field->is(Required::class));
    }

    public function testFieldsContainAirTemperature(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(FloatField::class, $fields->get('airTemperature'));
    }

    public function testFieldsContainGroundTemperature(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(FloatField::class, $fields->get('groundTemperature'));
    }

    public function testFieldsContainMoisture(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(FloatField::class, $fields->get('moisture'));
    }

    public function testFieldsContainHumidity(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(FloatField::class, $fields->get('humidity'));
    }

    public function testFieldsContainPhValue(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(FloatField::class, $fields->get('phValue'));
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
}
