<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Plant;

use InReNa\Content\Miniwald\MiniwaldDefinition;
use InReNa\Content\Plant\PlantCollection;
use InReNa\Content\Plant\PlantDefinition;
use InReNa\Content\Plant\PlantEntity;
use InReNa\Content\Plant\PlantTranslationDefinition;
use InReNa\Tests\Unit\Content\DefinitionTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;

#[CoversClass(PlantDefinition::class)]
class PlantDefinitionTest extends TestCase
{
    use DefinitionTestTrait;

    private PlantDefinition $definition;

    protected function setUp(): void
    {
        $registry = self::getRegistry();
        $this->definition = $registry->getByEntityName('miniwald_explorer_plant');
    }

    public function testEntityName(): void
    {
        static::assertSame('miniwald_explorer_plant', $this->definition->getEntityName());
        static::assertSame('miniwald_explorer_plant', PlantDefinition::ENTITY_NAME);
    }

    public function testEntityClass(): void
    {
        static::assertSame(PlantEntity::class, $this->definition->getEntityClass());
    }

    public function testCollectionClass(): void
    {
        static::assertSame(PlantCollection::class, $this->definition->getCollectionClass());
    }

    public function testFieldsContainId(): void
    {
        $fields = $this->definition->getFields();
        $idField = $fields->get('id');

        static::assertInstanceOf(IdField::class, $idField);
        static::assertTrue($idField->is(PrimaryKey::class));
        static::assertTrue($idField->is(Required::class));
    }

    public function testFieldsContainTranslatedName(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(TranslatedField::class, $fields->get('name'));
    }

    public function testFieldsContainScientificName(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(StringField::class, $fields->get('scientificName'));
    }

    public function testFieldsContainImageId(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(FkField::class, $fields->get('imageId'));
    }

    public function testFieldsContainPosition(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('position');

        // Position is a StringField for grid positions like "A1", "B3"
        static::assertInstanceOf(StringField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainSortOrder(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(IntField::class, $fields->get('sortOrder'));
    }

    public function testFieldsContainActive(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(BoolField::class, $fields->get('active'));
    }

    public function testFieldsContainMiniwaldId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('miniwaldId');

        static::assertInstanceOf(FkField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainMiniwaldAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('miniwald');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(MiniwaldDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainImageAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('image');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(MediaDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainTranslationsAssociation(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(TranslationsAssociationField::class, $fields->get('translations'));
    }
}
