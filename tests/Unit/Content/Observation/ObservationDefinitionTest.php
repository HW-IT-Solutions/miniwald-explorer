<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Observation;

use InReNa\Content\GroundMeasurement\GroundMeasurementDefinition;
use InReNa\Content\Miniwald\MiniwaldDefinition;
use InReNa\Content\Observation\ObservationCollection;
use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\ObservationItem\ObservationItemDefinition;
use InReNa\Content\PlantMeasurement\PlantMeasurementDefinition;
use InReNa\Content\SpeciesCategory\SpeciesCategoryDefinition;
use InReNa\Tests\Unit\Content\DefinitionTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
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

#[CoversClass(ObservationDefinition::class)]
class ObservationDefinitionTest extends TestCase
{
    use DefinitionTestTrait;

    private ObservationDefinition $definition;

    protected function setUp(): void
    {
        $registry = self::getRegistry();
        $this->definition = $registry->getByEntityName('miniwald_explorer_observation');
    }

    public function testEntityName(): void
    {
        static::assertSame('miniwald_explorer_observation', $this->definition->getEntityName());
        static::assertSame('miniwald_explorer_observation', ObservationDefinition::ENTITY_NAME);
    }

    public function testEntityClass(): void
    {
        static::assertSame(ObservationEntity::class, $this->definition->getEntityClass());
    }

    public function testCollectionClass(): void
    {
        static::assertSame(ObservationCollection::class, $this->definition->getCollectionClass());
    }

    public function testFieldsContainId(): void
    {
        $fields = $this->definition->getFields();
        $idField = $fields->get('id');

        static::assertInstanceOf(IdField::class, $idField);
        static::assertTrue($idField->is(PrimaryKey::class));
        static::assertTrue($idField->is(Required::class));
    }

    public function testFieldsContainCustomerId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('customerId');

        static::assertInstanceOf(FkField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainMiniwaldId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('miniwaldId');

        static::assertInstanceOf(FkField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainType(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('type');

        static::assertInstanceOf(StringField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testTypeConstants(): void
    {
        static::assertSame('animal', ObservationDefinition::TYPE_ANIMAL);
        static::assertSame('plant', ObservationDefinition::TYPE_PLANT);
        static::assertSame('ground', ObservationDefinition::TYPE_GROUND);
        static::assertSame(
            [ObservationDefinition::TYPE_ANIMAL, ObservationDefinition::TYPE_PLANT, ObservationDefinition::TYPE_GROUND],
            ObservationDefinition::TYPES
        );
    }

    public function testFieldsContainSpeciesCategoryId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('speciesCategoryId');

        static::assertInstanceOf(FkField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainSpeciesCategoryAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('speciesCategory');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(SpeciesCategoryDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainObservedAt(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('observedAt');

        static::assertInstanceOf(DateTimeField::class, $field);
        static::assertTrue($field->is(Required::class));
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainLatitude(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('latitude');

        static::assertInstanceOf(FloatField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainLongitude(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('longitude');

        static::assertInstanceOf(FloatField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainWeather(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('weather');

        static::assertInstanceOf(StringField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainWind(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('wind');

        static::assertInstanceOf(StringField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainRemarks(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('remarks');

        static::assertInstanceOf(LongTextField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainStatus(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('status');

        static::assertInstanceOf(StringField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainSubmittedAt(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('submittedAt');

        static::assertInstanceOf(DateTimeField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainCustomerAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('customer');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(CustomerDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainMiniwaldAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('miniwald');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(MiniwaldDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainItemsAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('items');

        static::assertInstanceOf(OneToManyAssociationField::class, $field);
        static::assertTrue($field->is(CascadeDelete::class));
        static::assertSame(ObservationItemDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainGroundMeasurementsAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('groundMeasurements');

        static::assertInstanceOf(OneToManyAssociationField::class, $field);
        static::assertTrue($field->is(CascadeDelete::class));
        static::assertSame(GroundMeasurementDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainPlantMeasurementAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('plantMeasurements');

        static::assertInstanceOf(OneToManyAssociationField::class, $field);
        static::assertTrue($field->is(CascadeDelete::class));
        static::assertSame(PlantMeasurementDefinition::class, $field->getReferenceDefinition()->getClass());
    }
}
