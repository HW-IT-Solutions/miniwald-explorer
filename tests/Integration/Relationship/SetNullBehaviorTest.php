<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Relationship;

use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

/**
 * Tests all SET NULL foreign key behaviors.
 */
#[CoversClass(ObservationDefinition::class)]
class SetNullBehaviorTest extends TestCase
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

    public function testDeleteMiniwaldSetsObservationMiniwaldIdNull(): void
    {
        $this->createMiniwald($this->ids, 'mw');
        $this->createObservation($this->ids, 'obs', [
            'miniwaldId' => $this->ids->get('mw'),
        ]);

        $this->getRepository('miniwald_explorer_miniwald')->delete([
            ['id' => $this->ids->get('mw')],
        ], $this->context);

        $entity = $this->getRepository('miniwald_explorer_observation')->search(
            new Criteria([$this->ids->get('obs')]),
            $this->context
        )->first();

        static::assertNotNull($entity);
        static::assertNull($entity->getMiniwaldId());
    }

    public function testDeleteSpeciesSetsObservationItemSpeciesIdNull(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat');
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs', [
            'speciesId' => $this->ids->get('sp'),
        ]);

        $this->getRepository('miniwald_explorer_species')->delete([
            ['id' => $this->ids->get('sp')],
        ], $this->context);

        $entity = $this->getRepository('miniwald_explorer_observation_item')->search(
            new Criteria([$this->ids->get('item')]),
            $this->context
        )->first();

        static::assertNotNull($entity);
        static::assertNull($entity->getSpeciesId());
    }

    public function testDeleteSubspeciesSetsObservationItemSubspeciesIdNull(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat');
        $this->createSubspecies($this->ids, 'sub', 'sp');
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs', [
            'speciesId' => $this->ids->get('sp'),
            'subspeciesId' => $this->ids->get('sub'),
        ]);

        $this->getRepository('miniwald_explorer_subspecies')->delete([
            ['id' => $this->ids->get('sub')],
        ], $this->context);

        $entity = $this->getRepository('miniwald_explorer_observation_item')->search(
            new Criteria([$this->ids->get('item')]),
            $this->context
        )->first();

        static::assertNotNull($entity);
        static::assertNull($entity->getSubspeciesId());
        // Species should still be set
        static::assertSame($this->ids->get('sp'), $entity->getSpeciesId());
    }

    public function testDeleteMediaSetsImageIdNullOnMultipleEntities(): void
    {
        $this->createMedia($this->ids, 'media');
        $this->createSpeciesCategory($this->ids, 'cat', [
            'imageId' => $this->ids->get('media'),
        ]);
        $this->createSpecies($this->ids, 'sp', 'cat', [
            'imageId' => $this->ids->get('media'),
        ]);
        $this->createSubspecies($this->ids, 'sub', 'sp', [
            'imageId' => $this->ids->get('media'),
        ]);

        $this->getRepository('media')->delete([
            ['id' => $this->ids->get('media')],
        ], $this->context);

        $category = $this->getRepository('miniwald_explorer_species_category')->search(
            new Criteria([$this->ids->get('cat')]),
            $this->context
        )->first();
        static::assertNull($category->getImageId());

        $species = $this->getRepository('miniwald_explorer_species')->search(
            new Criteria([$this->ids->get('sp')]),
            $this->context
        )->first();
        static::assertNull($species->getImageId());

        $subspecies = $this->getRepository('miniwald_explorer_subspecies')->search(
            new Criteria([$this->ids->get('sub')]),
            $this->context
        )->first();
        static::assertNull($subspecies->getImageId());
    }

    public function testDeletePlantSetsPlantMeasurementPlantIdNull(): void
    {
        $this->createPlant($this->ids, 'plant');
        $this->createObservation($this->ids, 'obs');
        $this->createPlantMeasurement($this->ids, 'pm', 'obs', [
            'plantId' => $this->ids->get('plant'),
        ]);

        $this->getRepository('miniwald_explorer_plant')->delete([
            ['id' => $this->ids->get('plant')],
        ], $this->context);

        $entity = $this->getRepository('miniwald_explorer_plant_measurement')->search(
            new Criteria([$this->ids->get('pm')]),
            $this->context
        )->first();

        static::assertNotNull($entity);
        static::assertNull($entity->getPlantId());
    }

    public function testDeleteMiniwaldSetsObservationMiniwaldIdNullButPreservesItems(): void
    {
        $this->createMiniwald($this->ids, 'mw');
        $this->createObservation($this->ids, 'obs', [
            'miniwaldId' => $this->ids->get('mw'),
        ]);
        $this->createObservationItem($this->ids, 'item', 'obs');

        $this->getRepository('miniwald_explorer_miniwald')->delete([
            ['id' => $this->ids->get('mw')],
        ], $this->context);

        // Observation should still exist with null miniwaldId
        $obs = $this->getRepository('miniwald_explorer_observation')->search(
            new Criteria([$this->ids->get('obs')]),
            $this->context
        )->first();
        static::assertNotNull($obs);
        static::assertNull($obs->getMiniwaldId());

        // Items should still exist
        $item = $this->getRepository('miniwald_explorer_observation_item')->search(
            new Criteria([$this->ids->get('item')]),
            $this->context
        )->first();
        static::assertNotNull($item);
    }
}
