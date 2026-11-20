<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Subspecies;

use InReNa\Content\Species\SpeciesDefinition;
use InReNa\Content\Subspecies\SubspeciesCollection;
use InReNa\Content\Subspecies\SubspeciesDefinition;
use InReNa\Content\Subspecies\SubspeciesEntity;
use InReNa\Content\Subspecies\SubspeciesTranslationDefinition;
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

#[CoversClass(SubspeciesDefinition::class)]
class SubspeciesDefinitionTest extends TestCase
{
    use DefinitionTestTrait;

    private SubspeciesDefinition $definition;

    protected function setUp(): void
    {
        $registry = self::getRegistry();
        $this->definition = $registry->getByEntityName('miniwald_explorer_subspecies');
    }

    public function testEntityName(): void
    {
        static::assertSame('miniwald_explorer_subspecies', $this->definition->getEntityName());
        static::assertSame('miniwald_explorer_subspecies', SubspeciesDefinition::ENTITY_NAME);
    }

    public function testEntityClass(): void
    {
        static::assertSame(SubspeciesEntity::class, $this->definition->getEntityClass());
    }

    public function testCollectionClass(): void
    {
        static::assertSame(SubspeciesCollection::class, $this->definition->getCollectionClass());
    }

    public function testFieldsContainId(): void
    {
        $fields = $this->definition->getFields();
        $idField = $fields->get('id');

        static::assertInstanceOf(IdField::class, $idField);
        static::assertTrue($idField->is(PrimaryKey::class));
        static::assertTrue($idField->is(Required::class));
    }

    public function testFieldsContainSpeciesId(): void
    {
        $fields = $this->definition->getFields();
        $fkField = $fields->get('speciesId');

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
        static::assertInstanceOf(StringField::class, $fields->get('scientificName'));
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

    public function testFieldsContainSpeciesAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('species');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(SpeciesDefinition::class, $field->getReferenceDefinition()->getClass());
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
