<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Species;

use InReNa\Content\Species\SpeciesCollection;
use InReNa\Content\Species\SpeciesDefinition;
use InReNa\Content\Species\SpeciesEntity;
use InReNa\Content\Species\SpeciesTranslationDefinition;
use InReNa\Content\SpeciesCategory\SpeciesCategoryDefinition;
use InReNa\Content\Subspecies\SubspeciesDefinition;
use InReNa\Tests\Unit\Content\DefinitionTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaDefinition;
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

#[CoversClass(SpeciesDefinition::class)]
class SpeciesDefinitionTest extends TestCase
{
    use DefinitionTestTrait;

    private SpeciesDefinition $definition;

    protected function setUp(): void
    {
        $registry = self::getRegistry();
        $this->definition = $registry->getByEntityName('miniwald_explorer_species');
    }

    public function testEntityName(): void
    {
        static::assertSame('miniwald_explorer_species', $this->definition->getEntityName());
        static::assertSame('miniwald_explorer_species', SpeciesDefinition::ENTITY_NAME);
    }

    public function testEntityClass(): void
    {
        static::assertSame(SpeciesEntity::class, $this->definition->getEntityClass());
    }

    public function testCollectionClass(): void
    {
        static::assertSame(SpeciesCollection::class, $this->definition->getCollectionClass());
    }

    public function testFieldsContainId(): void
    {
        $fields = $this->definition->getFields();
        $idField = $fields->get('id');

        static::assertInstanceOf(IdField::class, $idField);
        static::assertTrue($idField->is(PrimaryKey::class));
        static::assertTrue($idField->is(Required::class));
    }

    public function testFieldsContainSpeciesCategoryId(): void
    {
        $fields = $this->definition->getFields();
        $fkField = $fields->get('speciesCategoryId');

        static::assertInstanceOf(FkField::class, $fkField);
        static::assertTrue($fkField->is(Required::class));
        static::assertTrue($fkField->is(ApiAware::class));
    }

    public function testFieldsContainTranslatedName(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(TranslatedField::class, $fields->get('name'));
    }

    public function testFieldsContainScientificName(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('scientificName');

        static::assertInstanceOf(StringField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainTaxonId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('taxonId');

        static::assertInstanceOf(StringField::class, $field);
        static::assertTrue($field->is(ApiAware::class));
    }

    public function testFieldsContainTranslatedDescription(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(TranslatedField::class, $fields->get('description'));
    }

    public function testFieldsContainImageId(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(FkField::class, $fields->get('imageId'));
    }

    public function testFieldsContainPosition(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(IntField::class, $fields->get('position'));
    }

    public function testFieldsContainActive(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(BoolField::class, $fields->get('active'));
    }

    public function testFieldsContainSpeciesCategoryAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('speciesCategory');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(SpeciesCategoryDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainImageAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('image');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(MediaDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainSubspeciesAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('subspecies');

        static::assertInstanceOf(OneToManyAssociationField::class, $field);
        static::assertTrue($field->is(CascadeDelete::class));
        static::assertSame(SubspeciesDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainTranslationsAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('translations');

        static::assertInstanceOf(TranslationsAssociationField::class, $field);
        static::assertTrue($field->is(Required::class));
    }
}
