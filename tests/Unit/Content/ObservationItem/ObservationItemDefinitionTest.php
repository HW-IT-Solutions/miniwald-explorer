<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\ObservationItem;

use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Content\ObservationItem\ObservationItemCollection;
use InReNa\Content\ObservationItem\ObservationItemDefinition;
use InReNa\Content\ObservationItem\ObservationItemEntity;
use InReNa\Content\Photo\PhotoDefinition;
use InReNa\Content\Species\SpeciesDefinition;
use InReNa\Content\Subspecies\SubspeciesDefinition;
use InReNa\Tests\Unit\Content\DefinitionTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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

#[CoversClass(ObservationItemDefinition::class)]
class ObservationItemDefinitionTest extends TestCase
{
    use DefinitionTestTrait;

    private ObservationItemDefinition $definition;

    protected function setUp(): void
    {
        $registry = self::getRegistry();
        $this->definition = $registry->getByEntityName('miniwald_explorer_observation_item');
    }

    public function testEntityName(): void
    {
        static::assertSame('miniwald_explorer_observation_item', $this->definition->getEntityName());
        static::assertSame('miniwald_explorer_observation_item', ObservationItemDefinition::ENTITY_NAME);
    }

    public function testEntityClass(): void
    {
        static::assertSame(ObservationItemEntity::class, $this->definition->getEntityClass());
    }

    public function testCollectionClass(): void
    {
        static::assertSame(ObservationItemCollection::class, $this->definition->getCollectionClass());
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
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainSpeciesId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('speciesId');

        static::assertInstanceOf(FkField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
        // speciesId is NOT required (can use custom species name instead)
        static::assertFalse($field->is(Required::class));
    }

    public function testFieldsContainSubspeciesId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('subspeciesId');

        static::assertInstanceOf(FkField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
        static::assertFalse($field->is(Required::class));
    }

    public function testFieldsContainCustomSpeciesName(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('customSpeciesName');

        static::assertInstanceOf(StringField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainCount(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('count');

        static::assertInstanceOf(IntField::class, $field);
        static::assertTrue($field->is(Required::class));
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainRemarks(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('remarks');

        static::assertInstanceOf(LongTextField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainObservationAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('observation');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(ObservationDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainSpeciesAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('species');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(SpeciesDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainSubspeciesAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('subspecies');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(SubspeciesDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainPhotosAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('photos');

        static::assertInstanceOf(OneToManyAssociationField::class, $field);
        static::assertTrue($field->is(CascadeDelete::class));
        static::assertSame(PhotoDefinition::class, $field->getReferenceDefinition()->getClass());
    }
}
