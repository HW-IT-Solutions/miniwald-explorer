<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Service;

use InReNa\Content\Miniwald\MiniwaldEntity;
use InReNa\Content\Observation\ObservationCollection;
use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\ObservationItem\ObservationItemCollection;
use InReNa\Content\ObservationItem\ObservationItemEntity;
use InReNa\Content\PlantMeasurement\PlantMeasurementCollection;
use InReNa\Content\PlantMeasurement\PlantMeasurementEntity;
use InReNa\Content\Plant\PlantEntity;
use InReNa\Content\Species\SpeciesEntity;
use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use InReNa\Service\Observation\ObservationFilter;
use InReNa\Service\Observation\ObservationQueryService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \InReNa\Service\Observation\ObservationQueryService
 */
class ObservationQueryServiceTest extends TestCase
{
    private EntityRepository $repo;
    private ObservationQueryService $service;
    private Context $ctx;

    protected function setUp(): void
    {
        $this->repo = $this->createStub(EntityRepository::class);
        $this->service = new ObservationQueryService($this->repo);
        $this->ctx = Context::createDefaultContext();
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    /**
     * Wire the repository stub to return the given observations.
     */
    private function stubRepo(ObservationEntity ...$observations): void
    {
        $collection = new ObservationCollection($observations);

        $searchResult = $this->createStub(EntitySearchResult::class);
        $searchResult->method('getEntities')->willReturn($collection);

        $this->repo->method('search')->willReturn($searchResult);
    }

    /**
     * Build a minimal ObservationEntity with optional miniwald name.
     */
    private function makeObservation(?string $miniwaldName = null): ObservationEntity
    {
        $obs = new ObservationEntity();
        $obs->setId(Uuid::randomHex());
        $obs->setObservedAt(new \DateTime('2025-05-01 10:00:00'));

        if ($miniwaldName !== null) {
            $mw = new MiniwaldEntity();
            $mw->setId(Uuid::randomHex());
            $mw->setName($miniwaldName);
            $obs->setMiniwald($mw);
        }

        return $obs;
    }

    /**
     * Build an ObservationItemEntity for a given species (and optional category).
     */
    private function makeAnimalItem(
        string $speciesId,
        ?string $speciesCategoryId = null,
        ?string $categoryTechnicalName = null,
        int $count = 1,
    ): ObservationItemEntity {
        $item = new ObservationItemEntity();
        $item->setId(Uuid::randomHex());
        $item->setObservationId(Uuid::randomHex());
        $item->setCount($count);
        $item->setSpeciesId($speciesId);

        $species = new SpeciesEntity();
        $species->setId($speciesId);
        $species->setName('Species ' . substr($speciesId, 0, 4));

        if ($speciesCategoryId !== null) {
            $species->setSpeciesCategoryId($speciesCategoryId);

            $cat = new SpeciesCategoryEntity();
            $cat->setId($speciesCategoryId);
            $cat->setName('Category');
            if ($categoryTechnicalName !== null) {
                $cat->setTechnicalName($categoryTechnicalName);
            }
            $species->setSpeciesCategory($cat);
        }

        $item->setSpecies($species);

        return $item;
    }

    /**
     * Build an ObservationItemEntity with only a custom species name (no species).
     */
    private function makeCustomItem(string $customSpeciesName, int $count = 1): ObservationItemEntity
    {
        $item = new ObservationItemEntity();
        $item->setId(Uuid::randomHex());
        $item->setObservationId(Uuid::randomHex());
        $item->setCount($count);
        $item->setCustomSpeciesName($customSpeciesName);

        return $item;
    }

    /**
     * Build a PlantMeasurementEntity for a given plant.
     */
    private function makePlantMeasurement(string $plantId, ?string $plantName = null): PlantMeasurementEntity
    {
        $m = new PlantMeasurementEntity();
        $m->setId(Uuid::randomHex());
        $m->setObservationId(Uuid::randomHex());
        $m->setPlantId($plantId);
        $m->setHeightCm(120);

        $plant = new PlantEntity();
        $plant->setId($plantId);
        $plant->setName($plantName ?? 'Plant ' . substr($plantId, 0, 4));
        $m->setPlant($plant);

        return $m;
    }

    // ─── tests ───────────────────────────────────────────────────────────────

    public function testEmptyResultReturnsZeroTotalAndNoItems(): void
    {
        $this->stubRepo();

        $result = $this->service->query(new ObservationFilter(), $this->ctx);

        self::assertSame(0, $result->total);
        self::assertSame([], $result->items);
    }

    public function testSingleObservationWithTwoItemsReturnsTotalTwo(): void
    {
        $speciesId1 = Uuid::randomHex();
        $speciesId2 = Uuid::randomHex();

        $obs = $this->makeObservation('Miniwald A');
        $obs->setItems(new ObservationItemCollection([
            $this->makeAnimalItem($speciesId1),
            $this->makeAnimalItem($speciesId2),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(new ObservationFilter(), $this->ctx);

        self::assertSame(2, $result->total);
        self::assertCount(2, $result->items);
        self::assertSame('animal', $result->items[0]->type);
        self::assertSame('animal', $result->items[1]->type);
    }

    public function testThreeObservationsTwoItemsEachReturnsTotalSix(): void
    {
        $observations = [];
        for ($i = 0; $i < 3; $i++) {
            $obs = $this->makeObservation();
            $obs->setItems(new ObservationItemCollection([
                $this->makeAnimalItem(Uuid::randomHex()),
                $this->makeAnimalItem(Uuid::randomHex()),
            ]));
            $observations[] = $obs;
        }

        $this->stubRepo(...$observations);

        $result = $this->service->query(new ObservationFilter(), $this->ctx);

        self::assertSame(6, $result->total);
        self::assertCount(6, $result->items);
    }

    public function testPaginationLimitSlicesItems(): void
    {
        // 3 observations × 2 items = 6 total; limit=4 → page 1 has 4 items
        $observations = [];
        for ($i = 0; $i < 3; $i++) {
            $obs = $this->makeObservation();
            $obs->setItems(new ObservationItemCollection([
                $this->makeAnimalItem(Uuid::randomHex()),
                $this->makeAnimalItem(Uuid::randomHex()),
            ]));
            $observations[] = $obs;
        }

        $this->stubRepo(...$observations);

        $result = $this->service->query(new ObservationFilter(limit: 4, offset: 0), $this->ctx);

        self::assertSame(6, $result->total, 'Total must reflect all items, not just the page');
        self::assertCount(4, $result->items, 'Page must contain exactly limit items');
    }

    public function testPaginationOffsetSkipsItems(): void
    {
        // 6 total items; offset=4, limit=4 → page 2 has 2 items
        $observations = [];
        for ($i = 0; $i < 3; $i++) {
            $obs = $this->makeObservation();
            $obs->setItems(new ObservationItemCollection([
                $this->makeAnimalItem(Uuid::randomHex()),
                $this->makeAnimalItem(Uuid::randomHex()),
            ]));
            $observations[] = $obs;
        }

        $this->stubRepo(...$observations);

        $result = $this->service->query(new ObservationFilter(limit: 4, offset: 4), $this->ctx);

        self::assertSame(6, $result->total);
        self::assertCount(2, $result->items, 'Page 2 must contain the remaining 2 items');
    }

    public function testPaginationOffsetBeyondTotalReturnsEmptyItems(): void
    {
        $obs = $this->makeObservation();
        $obs->setItems(new ObservationItemCollection([
            $this->makeAnimalItem(Uuid::randomHex()),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(new ObservationFilter(limit: 20, offset: 20), $this->ctx);

        self::assertSame(1, $result->total);
        self::assertCount(0, $result->items);
    }

    public function testSpeciesIdFilterExcludesNonMatchingItems(): void
    {
        $targetSpeciesId = Uuid::randomHex();
        $otherSpeciesId = Uuid::randomHex();

        $matchingItem = $this->makeAnimalItem($targetSpeciesId);
        $otherItem = $this->makeAnimalItem($otherSpeciesId);

        // One observation with 2 items: one matching, one not
        $obs = $this->makeObservation();
        $obs->setItems(new ObservationItemCollection([
            $matchingItem,
            $otherItem,
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(
            new ObservationFilter(speciesId: $targetSpeciesId),
            $this->ctx
        );

        self::assertSame(1, $result->total, 'Only the matching species item must be counted');
        self::assertCount(1, $result->items);
        // The view id is the ObservationItem's own id, not the species id
        self::assertSame($matchingItem->getId(), $result->items[0]->id);
    }

    public function testSpeciesCategoryIdFilterExcludesNonMatchingItems(): void
    {
        $targetCatId = Uuid::randomHex();
        $otherCatId = Uuid::randomHex();

        $obs = $this->makeObservation();
        $obs->setItems(new ObservationItemCollection([
            $this->makeAnimalItem(Uuid::randomHex(), $targetCatId, 'birds'),
            $this->makeAnimalItem(Uuid::randomHex(), $otherCatId, 'insects'),
            $this->makeAnimalItem(Uuid::randomHex(), $targetCatId, 'birds'),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(
            new ObservationFilter(speciesCategoryId: $targetCatId),
            $this->ctx
        );

        self::assertSame(2, $result->total, 'Only items from the target category must be counted');
        self::assertCount(2, $result->items);
        foreach ($result->items as $view) {
            self::assertSame('bird', $view->subType);
        }
    }

    public function testSpeciesCategoryFilterExcludesPlantMeasurements(): void
    {
        $catId = Uuid::randomHex();

        $obs = $this->makeObservation();
        $obs->setItems(new ObservationItemCollection([
            $this->makeAnimalItem(Uuid::randomHex(), $catId, 'insects'),
        ]));
        $obs->setPlantMeasurements(new PlantMeasurementCollection([
            $this->makePlantMeasurement(Uuid::randomHex()),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(
            new ObservationFilter(speciesCategoryId: $catId),
            $this->ctx
        );

        // Plant measurements must be excluded when an animal category filter is active
        self::assertSame(1, $result->total);
        self::assertCount(1, $result->items);
        self::assertSame('animal', $result->items[0]->type);
    }

    public function testCustomEntrySubTypeFallsBackToObservationCategory(): void
    {
        $cat = new SpeciesCategoryEntity();
        $cat->setId(Uuid::randomHex());
        $cat->setName('Vögel');
        $cat->setTechnicalName('birds');

        $obs = $this->makeObservation();
        $obs->setSpeciesCategory($cat);
        $obs->setItems(new ObservationItemCollection([
            $this->makeCustomItem('Unbekannter Vogel'),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(new ObservationFilter(), $this->ctx);

        self::assertCount(1, $result->items);
        self::assertSame('bird', $result->items[0]->subType);
    }

    public function testSpeciesCategoryFilterIncludesCustomEntriesViaObservationCategory(): void
    {
        $catId = Uuid::randomHex();

        $obs = $this->makeObservation();
        $obs->setSpeciesCategoryId($catId);
        $obs->setItems(new ObservationItemCollection([
            $this->makeCustomItem('Unbekannter Vogel'),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(
            new ObservationFilter(speciesCategoryId: $catId),
            $this->ctx
        );

        self::assertSame(1, $result->total, 'Custom entries must match via the observation-level category');
        self::assertCount(1, $result->items);
    }

    public function testPlantIdFilterExcludesNonMatchingMeasurements(): void
    {
        $targetPlantId = Uuid::randomHex();
        $otherPlantId = Uuid::randomHex();

        $obs = $this->makeObservation();
        $obs->setPlantMeasurements(new PlantMeasurementCollection([
            $this->makePlantMeasurement($targetPlantId, 'Oak'),
            $this->makePlantMeasurement($otherPlantId, 'Birch'),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(
            new ObservationFilter(plantId: $targetPlantId),
            $this->ctx
        );

        self::assertSame(1, $result->total);
        self::assertCount(1, $result->items);
        self::assertSame('plant', $result->items[0]->type);
        self::assertSame('Oak', $result->items[0]->name);
    }

    public function testPlantsOnlyFilterExcludesAnimalItems(): void
    {
        $obs = $this->makeObservation();
        $obs->setItems(new ObservationItemCollection([
            $this->makeAnimalItem(Uuid::randomHex(), Uuid::randomHex(), 'birds'),
            $this->makeAnimalItem(Uuid::randomHex(), Uuid::randomHex(), 'insects'),
        ]));
        $obs->setPlantMeasurements(new PlantMeasurementCollection([
            $this->makePlantMeasurement(Uuid::randomHex(), 'Oak'),
            $this->makePlantMeasurement(Uuid::randomHex(), 'Birch'),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(
            new ObservationFilter(plantsOnly: true),
            $this->ctx
        );

        self::assertSame(2, $result->total, 'Only plant measurements must be counted');
        self::assertCount(2, $result->items);
        foreach ($result->items as $view) {
            self::assertSame('plant', $view->type);
        }
    }

    public function testPlantsOnlyFilterWithObservationWithoutPlantsReturnsEmpty(): void
    {
        $obs = $this->makeObservation();
        $obs->setItems(new ObservationItemCollection([
            $this->makeAnimalItem(Uuid::randomHex()),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(
            new ObservationFilter(plantsOnly: true),
            $this->ctx
        );

        self::assertSame(0, $result->total);
        self::assertSame([], $result->items);
    }

    public function testPlantMeasurementsAreIncludedWithoutFilter(): void
    {
        $obs = $this->makeObservation('Miniwald B');
        $obs->setItems(new ObservationItemCollection([
            $this->makeAnimalItem(Uuid::randomHex()),
        ]));
        $obs->setPlantMeasurements(new PlantMeasurementCollection([
            $this->makePlantMeasurement(Uuid::randomHex(), 'Birch'),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(new ObservationFilter(), $this->ctx);

        self::assertSame(2, $result->total);
        $types = array_map(fn ($v) => $v->type, $result->items);
        self::assertContains('animal', $types);
        self::assertContains('plant', $types);
    }

    public function testMiniwaldNameIsPopulatedOnViews(): void
    {
        $obs = $this->makeObservation('Miniwald Testwald');
        $obs->setItems(new ObservationItemCollection([
            $this->makeAnimalItem(Uuid::randomHex()),
        ]));

        $this->stubRepo($obs);

        $result = $this->service->query(new ObservationFilter(), $this->ctx);

        self::assertSame('Miniwald Testwald', $result->items[0]->miniwaldName);
    }

    public function testAnimalSubTypeIsMappedFromCategoryTechnicalName(): void
    {
        // Seeded categories use plural technical names; subType is singular
        // to match the icon names in the templates
        $expected = [
            'birds' => 'bird',
            'butterflies' => 'butterfly',
            'insects' => 'insect',
            'soil' => 'ground',
            'something_else' => 'unknown',
        ];

        foreach ($expected as $technicalName => $subType) {
            $obs = $this->makeObservation();
            $obs->setItems(new ObservationItemCollection([
                $this->makeAnimalItem(Uuid::randomHex(), Uuid::randomHex(), $technicalName),
            ]));

            $this->stubRepo($obs);

            $result = $this->service->query(new ObservationFilter(), $this->ctx);

            self::assertSame($subType, $result->items[0]->subType, "technicalName '{$technicalName}'");

            // Fresh repo stub per iteration — stubs cannot be rewired
            $this->setUp();
        }
    }

    public function testPlantViewHasHeightCm(): void
    {
        $obs = $this->makeObservation();
        $m = $this->makePlantMeasurement(Uuid::randomHex(), 'Pine');
        $m->setHeightCm(250);
        $obs->setPlantMeasurements(new PlantMeasurementCollection([$m]));

        $this->stubRepo($obs);

        $result = $this->service->query(new ObservationFilter(), $this->ctx);

        self::assertSame(250, $result->items[0]->heightCm);
    }

    public function testNoLimitReturnsAllItems(): void
    {
        $observations = [];
        for ($i = 0; $i < 5; $i++) {
            $obs = $this->makeObservation();
            $obs->setItems(new ObservationItemCollection([
                $this->makeAnimalItem(Uuid::randomHex()),
                $this->makeAnimalItem(Uuid::randomHex()),
                $this->makeAnimalItem(Uuid::randomHex()),
            ]));
            $observations[] = $obs;
        }

        $this->stubRepo(...$observations);

        $result = $this->service->query(new ObservationFilter(), $this->ctx);

        self::assertSame(15, $result->total);
        self::assertCount(15, $result->items);
    }

    public function testObservationWithNullItemsCollectionIsHandledGracefully(): void
    {
        $obs = $this->makeObservation();
        // items and plantMeasurements are null by default — must not throw

        $this->stubRepo($obs);

        $result = $this->service->query(new ObservationFilter(), $this->ctx);

        self::assertSame(0, $result->total);
        self::assertSame([], $result->items);
    }
}
