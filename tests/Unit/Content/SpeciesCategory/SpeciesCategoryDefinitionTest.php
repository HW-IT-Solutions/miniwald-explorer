<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\SpeciesCategory;

use InReNa\Content\SpeciesCategory\SpeciesCategoryCollection;
use InReNa\Content\SpeciesCategory\SpeciesCategoryDefinition;
use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use InReNa\Tests\Unit\Content\DefinitionTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;

#[CoversClass(SpeciesCategoryDefinition::class)]
class SpeciesCategoryDefinitionTest extends TestCase
{
    use DefinitionTestTrait;

    private SpeciesCategoryDefinition $definition;

    protected function setUp(): void
    {
        $registry = self::getRegistry();
        $this->definition = $registry->getByEntityName('miniwald_explorer_species_category');
    }

    public function testEntityName(): void
    {
        static::assertSame('miniwald_explorer_species_category', $this->definition->getEntityName());
        static::assertSame('miniwald_explorer_species_category', SpeciesCategoryDefinition::ENTITY_NAME);
    }

    public function testEntityClass(): void
    {
        static::assertSame(SpeciesCategoryEntity::class, $this->definition->getEntityClass());
    }

    public function testCollectionClass(): void
    {
        static::assertSame(SpeciesCategoryCollection::class, $this->definition->getCollectionClass());
    }

    public function testFieldsContainId(): void
    {
        $fields = $this->definition->getFields();
        $idField = $fields->get('id');

        static::assertInstanceOf(IdField::class, $idField);
        static::assertTrue($idField->is(PrimaryKey::class));
        static::assertTrue($idField->is(Required::class));
        static::assertTrue($idField->is(ApiAware::class));
    }

    public function testFieldsContainTranslatedName(): void
    {
        $fields = $this->definition->getFields();
        $nameField = $fields->get('name');

        static::assertInstanceOf(TranslatedField::class, $nameField);
        static::assertTrue($nameField->is(ApiAware::class));
    }

    public function testFieldsContainTranslatedDescription(): void
    {
        $fields = $this->definition->getFields();
        $descField = $fields->get('description');

        static::assertInstanceOf(TranslatedField::class, $descField);
        static::assertTrue($descField->is(ApiAware::class));
    }

    public function testFieldsContainImageId(): void
    {
        $fields = $this->definition->getFields();
        $imageIdField = $fields->get('imageId');

        static::assertInstanceOf(FkField::class, $imageIdField);
        static::assertTrue($imageIdField->is(ApiAware::class));
    }

    public function testFieldsContainPosition(): void
    {
        $fields = $this->definition->getFields();
        $positionField = $fields->get('position');

        static::assertInstanceOf(IntField::class, $positionField);
        static::assertTrue($positionField->is(ApiAware::class));
    }

    public function testFieldsContainActive(): void
    {
        $fields = $this->definition->getFields();
        $activeField = $fields->get('active');

        static::assertInstanceOf(BoolField::class, $activeField);
        static::assertTrue($activeField->is(ApiAware::class));
    }

    public function testFieldsContainTechnicalName(): void
    {
        $fields = $this->definition->getFields();
        $technicalNameField = $fields->get('technicalName');

        static::assertInstanceOf(StringField::class, $technicalNameField);
        static::assertTrue($technicalNameField->is(ApiAware::class));
        static::assertFalse($technicalNameField->is(Required::class));
    }

    public function testFieldsContainImageAssociation(): void
    {
        $fields = $this->definition->getFields();
        $imageField = $fields->get('image');

        static::assertInstanceOf(ManyToOneAssociationField::class, $imageField);
        static::assertTrue($imageField->is(ApiAware::class));
    }

    public function testFieldsContainSpeciesAssociation(): void
    {
        $fields = $this->definition->getFields();
        $speciesField = $fields->get('species');

        static::assertInstanceOf(OneToManyAssociationField::class, $speciesField);
        static::assertTrue($speciesField->is(ApiAware::class));
        static::assertTrue($speciesField->is(CascadeDelete::class));
    }

    public function testFieldsContainTranslationsAssociation(): void
    {
        $fields = $this->definition->getFields();
        $translationsField = $fields->get('translations');

        static::assertInstanceOf(TranslationsAssociationField::class, $translationsField);
        static::assertTrue($translationsField->is(Required::class));
    }
}
