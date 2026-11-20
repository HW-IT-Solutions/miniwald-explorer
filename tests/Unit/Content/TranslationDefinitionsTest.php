<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content;

use InReNa\Content\Plant\PlantDefinition;
use InReNa\Content\Plant\PlantTranslationDefinition;
use InReNa\Content\Species\SpeciesDefinition;
use InReNa\Content\Species\SpeciesTranslationDefinition;
use InReNa\Content\SpeciesCategory\SpeciesCategoryDefinition;
use InReNa\Content\SpeciesCategory\SpeciesCategoryTranslationDefinition;
use InReNa\Content\Subspecies\SubspeciesDefinition;
use InReNa\Content\Subspecies\SubspeciesTranslationDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

#[CoversClass(SpeciesCategoryTranslationDefinition::class)]
#[CoversClass(SpeciesTranslationDefinition::class)]
#[CoversClass(SubspeciesTranslationDefinition::class)]
#[CoversClass(PlantTranslationDefinition::class)]
class TranslationDefinitionsTest extends TestCase
{
    use DefinitionTestTrait;

    // --- SpeciesCategory Translation ---

    public function testSpeciesCategoryTranslationEntityName(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_species_category_translation');
        static::assertSame('miniwald_explorer_species_category_translation', $definition->getEntityName());
        static::assertSame('miniwald_explorer_species_category_translation', SpeciesCategoryTranslationDefinition::ENTITY_NAME);
    }

    public function testSpeciesCategoryTranslationParentDefinition(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_species_category_translation');
        static::assertSame(SpeciesCategoryDefinition::class, $definition->getParentDefinitionClass());
    }

    public function testSpeciesCategoryTranslationFields(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_species_category_translation');
        $fields = $definition->getFields();

        $nameField = $fields->get('name');
        static::assertInstanceOf(StringField::class, $nameField);
        static::assertTrue($nameField->is(Required::class));
        static::assertTrue($nameField->is(ApiAware::class));

        $descField = $fields->get('description');
        static::assertInstanceOf(LongTextField::class, $descField);
        static::assertTrue($descField->is(ApiAware::class));
        static::assertFalse($descField->is(Required::class));
    }

    // --- Species Translation ---

    public function testSpeciesTranslationEntityName(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_species_translation');
        static::assertSame('miniwald_explorer_species_translation', $definition->getEntityName());
        static::assertSame('miniwald_explorer_species_translation', SpeciesTranslationDefinition::ENTITY_NAME);
    }

    public function testSpeciesTranslationParentDefinition(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_species_translation');
        static::assertSame(SpeciesDefinition::class, $definition->getParentDefinitionClass());
    }

    public function testSpeciesTranslationFields(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_species_translation');
        $fields = $definition->getFields();

        $nameField = $fields->get('name');
        static::assertInstanceOf(StringField::class, $nameField);
        static::assertTrue($nameField->is(Required::class));
        static::assertTrue($nameField->is(ApiAware::class));

        $descField = $fields->get('description');
        static::assertInstanceOf(LongTextField::class, $descField);
        static::assertTrue($descField->is(ApiAware::class));
    }

    // --- Subspecies Translation ---

    public function testSubspeciesTranslationEntityName(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_subspecies_translation');
        static::assertSame('miniwald_explorer_subspecies_translation', $definition->getEntityName());
        static::assertSame('miniwald_explorer_subspecies_translation', SubspeciesTranslationDefinition::ENTITY_NAME);
    }

    public function testSubspeciesTranslationParentDefinition(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_subspecies_translation');
        static::assertSame(SubspeciesDefinition::class, $definition->getParentDefinitionClass());
    }

    public function testSubspeciesTranslationFields(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_subspecies_translation');
        $fields = $definition->getFields();

        $nameField = $fields->get('name');
        static::assertInstanceOf(StringField::class, $nameField);
        static::assertTrue($nameField->is(Required::class));
        static::assertTrue($nameField->is(ApiAware::class));

        $descField = $fields->get('description');
        static::assertInstanceOf(LongTextField::class, $descField);
        static::assertTrue($descField->is(ApiAware::class));
    }

    // --- Plant Translation ---

    public function testPlantTranslationEntityName(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_plant_translation');
        static::assertSame('miniwald_explorer_plant_translation', $definition->getEntityName());
        static::assertSame('miniwald_explorer_plant_translation', PlantTranslationDefinition::ENTITY_NAME);
    }

    public function testPlantTranslationParentDefinition(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_plant_translation');
        static::assertSame(PlantDefinition::class, $definition->getParentDefinitionClass());
    }

    public function testPlantTranslationFields(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_plant_translation');
        $fields = $definition->getFields();

        $nameField = $fields->get('name');
        static::assertInstanceOf(StringField::class, $nameField);
        static::assertTrue($nameField->is(Required::class));
        static::assertTrue($nameField->is(ApiAware::class));
    }

    public function testPlantTranslationHasNoDescription(): void
    {
        $definition = self::getRegistry()->getByEntityName('miniwald_explorer_plant_translation');
        $fields = $definition->getFields();

        // Plant translation only has name, no description
        static::assertNull($fields->get('description'));
    }

    // --- Cross-cutting tests ---

    #[DataProvider('translationDefinitionProvider')]
    public function testAllTranslationDefinitionsHaveRequiredNameField(string $entityName): void
    {
        $definition = self::getRegistry()->getByEntityName($entityName);
        $fields = $definition->getFields();

        $nameField = $fields->get('name');
        static::assertNotNull($nameField, sprintf('Translation definition %s must have a name field', $entityName));
        static::assertTrue($nameField->is(Required::class), sprintf('Name field in %s must be required', $entityName));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function translationDefinitionProvider(): array
    {
        return [
            'SpeciesCategory' => ['miniwald_explorer_species_category_translation'],
            'Species' => ['miniwald_explorer_species_translation'],
            'Subspecies' => ['miniwald_explorer_subspecies_translation'],
            'Plant' => ['miniwald_explorer_plant_translation'],
        ];
    }
}
