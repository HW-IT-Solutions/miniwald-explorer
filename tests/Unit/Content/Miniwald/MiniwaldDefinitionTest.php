<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Miniwald;

use InReNa\Content\Miniwald\MiniwaldCollection;
use InReNa\Content\Miniwald\MiniwaldDefinition;
use InReNa\Content\Miniwald\MiniwaldEntity;
use InReNa\Tests\Unit\Content\DefinitionTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

#[CoversClass(MiniwaldDefinition::class)]
class MiniwaldDefinitionTest extends TestCase
{
    use DefinitionTestTrait;

    private MiniwaldDefinition $definition;

    protected function setUp(): void
    {
        $registry = self::getRegistry();
        $this->definition = $registry->getByEntityName('miniwald_explorer_miniwald');
    }

    public function testEntityName(): void
    {
        static::assertSame('miniwald_explorer_miniwald', $this->definition->getEntityName());
        static::assertSame('miniwald_explorer_miniwald', MiniwaldDefinition::ENTITY_NAME);
    }

    public function testEntityClass(): void
    {
        static::assertSame(MiniwaldEntity::class, $this->definition->getEntityClass());
    }

    public function testCollectionClass(): void
    {
        static::assertSame(MiniwaldCollection::class, $this->definition->getCollectionClass());
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

    public function testFieldsContainName(): void
    {
        $fields = $this->definition->getFields();
        $nameField = $fields->get('name');

        static::assertInstanceOf(StringField::class, $nameField);
        static::assertTrue($nameField->is(Required::class));
        static::assertTrue($nameField->is(ApiAware::class));
    }

    public function testMinimalFieldCount(): void
    {
        $fields = $this->definition->getFields();
        // id, name, createdAt, updatedAt (auto-added by Shopware)
        static::assertGreaterThanOrEqual(2, $fields->count());
    }

}
