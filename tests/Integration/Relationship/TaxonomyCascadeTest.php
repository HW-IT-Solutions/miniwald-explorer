<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Relationship;

use InReNa\Content\SpeciesCategory\SpeciesCategoryDefinition;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

/**
 * Tests the full taxonomy cascade: SpeciesCategory → Species → Subspecies
 */
#[CoversClass(SpeciesCategoryDefinition::class)]
class TaxonomyCascadeTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testDeleteCategoryDeletesAllSpeciesAndSubspecies(): void
    {
        // Create: Category → 2 Species → 2 Subspecies each
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp-1', 'cat');
        $this->createSpecies($this->ids, 'sp-2', 'cat');
        $this->createSubspecies($this->ids, 'sub-1a', 'sp-1');
        $this->createSubspecies($this->ids, 'sub-1b', 'sp-1');
        $this->createSubspecies($this->ids, 'sub-2a', 'sp-2');
        $this->createSubspecies($this->ids, 'sub-2b', 'sp-2');

        // Delete the category
        $this->getRepository('miniwald_explorer_species_category')->delete([
            ['id' => $this->ids->get('cat')],
        ], $this->context);

        // Verify all species are gone
        $speciesResult = $this->getRepository('miniwald_explorer_species')->search(
            new Criteria([
                $this->ids->get('sp-1'),
                $this->ids->get('sp-2'),
            ]),
            $this->context
        );
        static::assertCount(0, $speciesResult);

        // Verify all subspecies are gone
        $subspeciesResult = $this->getRepository('miniwald_explorer_subspecies')->search(
            new Criteria([
                $this->ids->get('sub-1a'),
                $this->ids->get('sub-1b'),
                $this->ids->get('sub-2a'),
                $this->ids->get('sub-2b'),
            ]),
            $this->context
        );
        static::assertCount(0, $subspeciesResult);
    }

    public function testDeleteSpeciesDeletesSubspeciesButNotCategory(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp-1', 'cat');
        $this->createSpecies($this->ids, 'sp-2', 'cat');
        $this->createSubspecies($this->ids, 'sub-1a', 'sp-1');
        $this->createSubspecies($this->ids, 'sub-1b', 'sp-1');

        // Delete only sp-1
        $this->getRepository('miniwald_explorer_species')->delete([
            ['id' => $this->ids->get('sp-1')],
        ], $this->context);

        // Subspecies of sp-1 should be gone
        $subspeciesResult = $this->getRepository('miniwald_explorer_subspecies')->search(
            new Criteria([
                $this->ids->get('sub-1a'),
                $this->ids->get('sub-1b'),
            ]),
            $this->context
        );
        static::assertCount(0, $subspeciesResult);

        // Category should still exist
        $categoryResult = $this->getRepository('miniwald_explorer_species_category')->search(
            new Criteria([$this->ids->get('cat')]),
            $this->context
        );
        static::assertCount(1, $categoryResult);

        // Sibling species should still exist
        $siblingResult = $this->getRepository('miniwald_explorer_species')->search(
            new Criteria([$this->ids->get('sp-2')]),
            $this->context
        );
        static::assertCount(1, $siblingResult);
    }

    public function testDeleteCategoryCascadesToTranslations(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat', ['name' => 'Mammals', 'description' => 'Warm-blooded']);
        $this->createSpecies($this->ids, 'sp', 'cat', ['name' => 'Fox', 'description' => 'Cunning']);
        $this->createSubspecies($this->ids, 'sub', 'sp', ['name' => 'Red Fox', 'description' => 'Common']);

        // Delete the category
        $this->getRepository('miniwald_explorer_species_category')->delete([
            ['id' => $this->ids->get('cat')],
        ], $this->context);

        // Verify species and subspecies are gone (translations cascade with them)
        $speciesResult = $this->getRepository('miniwald_explorer_species')->search(
            new Criteria([$this->ids->get('sp')]),
            $this->context
        );
        static::assertCount(0, $speciesResult);

        $subspeciesResult = $this->getRepository('miniwald_explorer_subspecies')->search(
            new Criteria([$this->ids->get('sub')]),
            $this->context
        );
        static::assertCount(0, $subspeciesResult);
    }
}
