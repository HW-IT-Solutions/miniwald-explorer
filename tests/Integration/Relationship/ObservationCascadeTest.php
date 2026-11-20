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
 * Tests the observation tree cascade: Observation → Items/Measurements/Photos
 */
#[CoversClass(ObservationDefinition::class)]
class ObservationCascadeTest extends TestCase
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

    public function testDeleteObservationDeletesEverything(): void
    {
        // Create full observation tree
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-1', 'obs');
        $this->createObservationItem($this->ids, 'item-2', 'obs');
        $this->createGroundMeasurement($this->ids, 'gm', 'obs', ['airTemperature' => 20.0, 'locationType' => \InReNa\Content\GroundMeasurement\GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER]);
        $this->createPlantMeasurement($this->ids, 'pm', 'obs', ['heightCm' => 300]);
        $this->createMedia($this->ids, 'media-1');
        $this->createMedia($this->ids, 'media-2');
        $this->createPhoto($this->ids, 'photo-1', 'item-1', 'media-1');
        $this->createPhoto($this->ids, 'photo-2', 'item-2', 'media-2');

        // Delete the observation
        $this->getRepository('miniwald_explorer_observation')->delete([
            ['id' => $this->ids->get('obs')],
        ], $this->context);

        // Verify all items are gone
        $itemResult = $this->getRepository('miniwald_explorer_observation_item')->search(
            new Criteria([$this->ids->get('item-1'), $this->ids->get('item-2')]),
            $this->context
        );
        static::assertCount(0, $itemResult);

        // Verify ground measurement is gone
        $gmResult = $this->getRepository('miniwald_explorer_ground_measurement')->search(
            new Criteria([$this->ids->get('gm')]),
            $this->context
        );
        static::assertCount(0, $gmResult);

        // Verify plant measurement is gone
        $pmResult = $this->getRepository('miniwald_explorer_plant_measurement')->search(
            new Criteria([$this->ids->get('pm')]),
            $this->context
        );
        static::assertCount(0, $pmResult);

        // Verify photos are gone
        $photoResult = $this->getRepository('miniwald_explorer_photo')->search(
            new Criteria([$this->ids->get('photo-1'), $this->ids->get('photo-2')]),
            $this->context
        );
        static::assertCount(0, $photoResult);

        // Verify media still exists (not cascaded)
        $mediaResult = $this->getRepository('media')->search(
            new Criteria([$this->ids->get('media-1'), $this->ids->get('media-2')]),
            $this->context
        );
        static::assertCount(2, $mediaResult);
    }

    public function testDeleteObservationItemDeletesPhotos(): void
    {
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item-1', 'obs');
        $this->createObservationItem($this->ids, 'item-2', 'obs');
        $this->createMedia($this->ids, 'media-1');
        $this->createMedia($this->ids, 'media-2');
        $this->createPhoto($this->ids, 'photo-1', 'item-1', 'media-1');
        $this->createPhoto($this->ids, 'photo-2', 'item-2', 'media-2');

        // Delete only item-1
        $this->getRepository('miniwald_explorer_observation_item')->delete([
            ['id' => $this->ids->get('item-1')],
        ], $this->context);

        // Photo of item-1 should be gone
        $photo1Result = $this->getRepository('miniwald_explorer_photo')->search(
            new Criteria([$this->ids->get('photo-1')]),
            $this->context
        );
        static::assertCount(0, $photo1Result);

        // Photo of item-2 should still exist
        $photo2Result = $this->getRepository('miniwald_explorer_photo')->search(
            new Criteria([$this->ids->get('photo-2')]),
            $this->context
        );
        static::assertCount(1, $photo2Result);

        // Observation should still exist
        $obsResult = $this->getRepository('miniwald_explorer_observation')->search(
            new Criteria([$this->ids->get('obs')]),
            $this->context
        );
        static::assertCount(1, $obsResult);
    }

    public function testDeleteObservationPreservesSpeciesAndSubspecies(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat');
        $this->createSubspecies($this->ids, 'sub', 'sp');
        $this->createObservation($this->ids, 'obs');
        $this->createObservationItem($this->ids, 'item', 'obs', [
            'speciesId' => $this->ids->get('sp'),
            'subspeciesId' => $this->ids->get('sub'),
        ]);

        // Delete the observation
        $this->getRepository('miniwald_explorer_observation')->delete([
            ['id' => $this->ids->get('obs')],
        ], $this->context);

        // Species should still exist
        $speciesResult = $this->getRepository('miniwald_explorer_species')->search(
            new Criteria([$this->ids->get('sp')]),
            $this->context
        );
        static::assertCount(1, $speciesResult);

        // Subspecies should still exist
        $subspeciesResult = $this->getRepository('miniwald_explorer_subspecies')->search(
            new Criteria([$this->ids->get('sub')]),
            $this->context
        );
        static::assertCount(1, $subspeciesResult);
    }
}
