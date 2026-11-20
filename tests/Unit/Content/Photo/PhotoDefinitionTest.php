<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Photo;

use InReNa\Content\ObservationItem\ObservationItemDefinition;
use InReNa\Content\Photo\PhotoCollection;
use InReNa\Content\Photo\PhotoDefinition;
use InReNa\Content\Photo\PhotoEntity;
use InReNa\Tests\Unit\Content\DefinitionTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

#[CoversClass(PhotoDefinition::class)]
class PhotoDefinitionTest extends TestCase
{
    use DefinitionTestTrait;

    private PhotoDefinition $definition;

    protected function setUp(): void
    {
        $registry = self::getRegistry();
        $this->definition = $registry->getByEntityName('miniwald_explorer_photo');
    }

    public function testEntityName(): void
    {
        static::assertSame('miniwald_explorer_photo', $this->definition->getEntityName());
        static::assertSame('miniwald_explorer_photo', PhotoDefinition::ENTITY_NAME);
    }

    public function testEntityClass(): void
    {
        static::assertSame(PhotoEntity::class, $this->definition->getEntityClass());
    }

    public function testCollectionClass(): void
    {
        static::assertSame(PhotoCollection::class, $this->definition->getCollectionClass());
    }

    public function testFieldsContainId(): void
    {
        $fields = $this->definition->getFields();
        $idField = $fields->get('id');

        static::assertInstanceOf(IdField::class, $idField);
        static::assertTrue($idField->is(PrimaryKey::class));
        static::assertTrue($idField->is(Required::class));
    }

    public function testFieldsContainObservationItemId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('observationItemId');

        static::assertInstanceOf(FkField::class, $field);
        static::assertTrue($field->is(Required::class));
    }

    public function testFieldsContainMediaId(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('mediaId');

        static::assertInstanceOf(FkField::class, $field);
        static::assertTrue($field->is(Required::class));
    }

    public function testFieldsContainCaption(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(StringField::class, $fields->get('caption'));
    }

    public function testFieldsContainSortOrder(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(IntField::class, $fields->get('sortOrder'));
    }

    public function testFieldsContainIsApproved(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(BoolField::class, $fields->get('isApproved'));
    }

    public function testFieldsContainApprovedAt(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(DateTimeField::class, $fields->get('approvedAt'));
    }

    public function testFieldsContainApprovedBy(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(StringField::class, $fields->get('approvedBy'));
    }

    public function testFieldsContainRejectionReason(): void
    {
        $fields = $this->definition->getFields();
        static::assertInstanceOf(LongTextField::class, $fields->get('rejectionReason'));
    }

    public function testFieldsContainObservationItemAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('observationItem');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(ObservationItemDefinition::class, $field->getReferenceDefinition()->getClass());
    }

    public function testFieldsContainMediaAssociation(): void
    {
        $fields = $this->definition->getFields();
        $field = $fields->get('media');

        static::assertInstanceOf(ManyToOneAssociationField::class, $field);
        static::assertSame(MediaDefinition::class, $field->getReferenceDefinition()->getClass());
    }
}
