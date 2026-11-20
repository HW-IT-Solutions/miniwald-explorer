<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Service;

use InReNa\Content\GroundMeasurement\GroundMeasurementCollection;
use InReNa\Content\GroundMeasurement\GroundMeasurementDefinition;
use InReNa\Content\GroundMeasurement\GroundMeasurementEntity;
use InReNa\Content\Miniwald\MiniwaldEntity;
use InReNa\Content\Observation\ObservationCollection;
use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\ObservationItem\ObservationItemCollection;
use InReNa\Content\ObservationItem\ObservationItemEntity;
use InReNa\Content\Photo\PhotoCollection;
use InReNa\Content\Photo\PhotoEntity;
use InReNa\Content\Plant\PlantEntity;
use InReNa\Content\PlantMeasurement\PlantMeasurementCollection;
use InReNa\Content\PlantMeasurement\PlantMeasurementEntity;
use InReNa\Content\Species\SpeciesEntity;
use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use InReNa\Service\CsvExportService;
use InReNa\Service\ObservationExportService;
use InReNa\Service\PhotoAuthTokenService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \InReNa\Service\CsvExportService
 */
class CsvExportServiceTest extends TestCase
{
    private CsvExportService $service;
    private ObservationExportService $exportService;
    private PhotoAuthTokenService $tokenService;

    protected function setUp(): void
    {
        $this->exportService = $this->createStub(ObservationExportService::class);
        $this->tokenService = $this->createStub(PhotoAuthTokenService::class);
        $this->service = new CsvExportService($this->exportService, $this->tokenService);
    }

    public function testHeadersContainExpectedColumns(): void
    {
        $rows = $this->captureRows(new ObservationCollection([]));

        $this->assertCount(1, $rows, 'Only header row expected for empty collection');

        $headers = $rows[0];
        $this->assertContains('observation_id', $headers);
        $this->assertContains('type', $headers);
        $this->assertContains('date', $headers);
        $this->assertContains('time', $headers);
        $this->assertContains('observer_name', $headers);
        $this->assertContains('miniwald', $headers);
        $this->assertContains('latitude', $headers);
        $this->assertContains('longitude', $headers);
        $this->assertContains('weather', $headers);
        $this->assertContains('wind', $headers);
        $this->assertContains('general_remarks', $headers);
        $this->assertContains('species_category', $headers);
        $this->assertContains('species_name', $headers);
        $this->assertContains('scientific_name', $headers);
        $this->assertContains('subspecies_name', $headers);
        $this->assertContains('subspecies_scientific_name', $headers);
        $this->assertContains('custom_species_name', $headers);
        $this->assertContains('count', $headers);
        $this->assertContains('item_remarks', $headers);
        $this->assertContains('photo_urls', $headers);
        $this->assertContains('plant_name', $headers);
        $this->assertContains('plant_scientific_name', $headers);
        $this->assertContains('height_cm', $headers);
        $this->assertContains('trunk_circumference_cm', $headers);
        $this->assertContains('crown_diameter_cm', $headers);
        $this->assertContains('plant_remarks', $headers);
        $this->assertContains('air_temperature', $headers);
        $this->assertContains('ground_temperature', $headers);
        $this->assertContains('humidity', $headers);
        $this->assertContains('moisture', $headers);
        $this->assertContains('ph_value', $headers);
        $this->assertContains('ground_remarks', $headers);
    }

    public function testAnimalObservationProducesOneRowPerItem(): void
    {
        $species1 = $this->buildSpecies('Vulpes vulpes');
        $species2 = $this->buildSpecies('Capreolus capreolus');

        $item1 = $this->buildAnimalItem(count: 1, species: $species1);
        $item2 = $this->buildAnimalItem(count: 2, species: $species2);

        $observation = $this->buildObservation();
        $observation->setItems(new ObservationItemCollection([$item1, $item2]));

        $this->exportService->method('getObservationType')->willReturn('animals');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        // header + 2 data rows
        $this->assertCount(3, $rows);

        $headerIndex = array_flip($rows[0]);
        $this->assertSame('1', $rows[1][$headerIndex['count']]);
        $this->assertSame('2', $rows[2][$headerIndex['count']]);
    }

    public function testAnimalObservationWithNoItemsProducesOneEmptyRow(): void
    {
        $observation = $this->buildObservation();
        $observation->setItems(new ObservationItemCollection([]));

        $this->exportService->method('getObservationType')->willReturn('animals');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        // header + 1 empty data row
        $this->assertCount(2, $rows);

        $headerIndex = array_flip($rows[0]);
        // count field should be empty
        $this->assertSame('', $rows[1][$headerIndex['count']]);
    }

    public function testPlantObservationProducesOneRowWithEmptyAnimalFields(): void
    {
        $plant = new PlantEntity();
        $plant->setId(Uuid::randomHex());
        $plant->setScientificName('Quercus robur');

        $measurement = new PlantMeasurementEntity();
        $measurement->setId(Uuid::randomHex());
        $measurement->setObservationId(Uuid::randomHex());
        $measurement->setPlant($plant);
        $measurement->setHeightCm(250);

        $observation = $this->buildObservation();
        $observation->setPlantMeasurements(new PlantMeasurementCollection([$measurement]));

        $this->exportService->method('getObservationType')->willReturn('plants');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        // header + 1 data row
        $this->assertCount(2, $rows);

        $headerIndex = array_flip($rows[0]);
        // animal fields should be empty
        $this->assertSame('', $rows[1][$headerIndex['count']]);
        $this->assertSame('', $rows[1][$headerIndex['species_name']]);
        // plant fields should be populated
        $this->assertSame('250', $rows[1][$headerIndex['height_cm']]);
    }

    public function testPlantObservationWithCustomPlantNameUsedWhenNoPlantEntity(): void
    {
        $measurement = new PlantMeasurementEntity();
        $measurement->setId(Uuid::randomHex());
        $measurement->setObservationId(Uuid::randomHex());
        $measurement->setCustomPlantName('Unbekannte Eiche');
        $measurement->setHeightCm(180);

        $observation = $this->buildObservation();
        $observation->setPlantMeasurements(new PlantMeasurementCollection([$measurement]));

        $this->exportService->method('getObservationType')->willReturn('plants');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        $this->assertCount(2, $rows);
        $headerIndex = array_flip($rows[0]);
        $this->assertSame('Unbekannte Eiche', $rows[1][$headerIndex['plant_name']]);
        $this->assertSame('180', $rows[1][$headerIndex['height_cm']]);
    }

    public function testPlantObservationWithMultiplePlantsProducesOneRowPerPlant(): void
    {
        $plant1 = new PlantEntity();
        $plant1->setId(Uuid::randomHex());
        $plant1->setScientificName('Quercus robur');

        $measurement1 = new PlantMeasurementEntity();
        $measurement1->setId(Uuid::randomHex());
        $measurement1->setObservationId(Uuid::randomHex());
        $measurement1->setPlant($plant1);
        $measurement1->setHeightCm(250);

        $plant2 = new PlantEntity();
        $plant2->setId(Uuid::randomHex());
        $plant2->setScientificName('Betula pendula');

        $measurement2 = new PlantMeasurementEntity();
        $measurement2->setId(Uuid::randomHex());
        $measurement2->setObservationId(Uuid::randomHex());
        $measurement2->setPlant($plant2);
        $measurement2->setHeightCm(180);

        $observation = $this->buildObservation();
        $observation->setPlantMeasurements(new PlantMeasurementCollection([$measurement1, $measurement2]));

        $this->exportService->method('getObservationType')->willReturn('plants');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        // header + 2 data rows (one per plant)
        $this->assertCount(3, $rows);

        $headerIndex = array_flip($rows[0]);
        $this->assertSame('250', $rows[1][$headerIndex['height_cm']]);
        $this->assertSame('180', $rows[2][$headerIndex['height_cm']]);
        // animal fields should be empty on both rows
        $this->assertSame('', $rows[1][$headerIndex['count']]);
        $this->assertSame('', $rows[2][$headerIndex['count']]);
    }

    public function testGroundObservationProducesOneRowWithEmptyAnimalAndPlantFields(): void
    {
        $measurement = new GroundMeasurementEntity();
        $measurement->setId(Uuid::randomHex());
        $measurement->setObservationId(Uuid::randomHex());
        $measurement->setLocationType(GroundMeasurementDefinition::LOCATION_TYPE_WOOD_CENTER);

        $observation = $this->buildObservation();
        $observation->setGroundMeasurements(new GroundMeasurementCollection([$measurement]));

        $this->exportService->method('getObservationType')->willReturn('ground');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        // header + 1 data row
        $this->assertCount(2, $rows);

        $headerIndex = array_flip($rows[0]);
        // animal fields should be empty
        $this->assertSame('', $rows[1][$headerIndex['count']]);
        // plant fields should be empty
        $this->assertSame('', $rows[1][$headerIndex['height_cm']]);
    }

    public function testPhotoUrlsArePipeSeparated(): void
    {
        $photo1Id = Uuid::randomHex();
        $photo2Id = Uuid::randomHex();

        $photo1 = new PhotoEntity();
        $photo1->setId($photo1Id);
        $photo1->setMediaId(Uuid::randomHex());

        $photo2 = new PhotoEntity();
        $photo2->setId($photo2Id);
        $photo2->setMediaId(Uuid::randomHex());

        $this->tokenService
            ->method('generatePhotoUrl')
            ->willReturnCallback(function (string $baseUrl, string $photoId) {
                return ['url' => 'https://example.com/photo/' . $photoId, 'expires_at' => '2026-05-10T00:00:00+00:00'];
            });

        $item = $this->buildAnimalItem(count: 1, photos: [$photo1, $photo2]);
        $observation = $this->buildObservation();
        $observation->setItems(new ObservationItemCollection([$item]));

        $this->exportService->method('getObservationType')->willReturn('animals');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        $headerIndex = array_flip($rows[0]);
        $photoUrlsCell = $rows[1][$headerIndex['photo_urls']];

        $this->assertStringContainsString(' | ', $photoUrlsCell);
        $parts = explode(' | ', $photoUrlsCell);
        $this->assertCount(2, $parts);
        $this->assertStringContainsString($photo1Id, $parts[0]);
        $this->assertStringContainsString($photo2Id, $parts[1]);
    }

    public function testBaseRowContainsObservationMetadata(): void
    {
        $miniwaldId = Uuid::randomHex();
        $miniwald = new MiniwaldEntity();
        $miniwald->setId($miniwaldId);
        $miniwald->setName('Miniwald Hambach');

        $observation = $this->buildObservation();
        $observation->setObservedAt(new \DateTimeImmutable('2024-05-15 14:30:00'));
        $observation->setLatitude(51.12345);
        $observation->setLongitude(9.98765);
        $observation->setWeather('sunny');
        $observation->setWind('calm');
        $observation->setRemarks('General note');
        $observation->setMiniwald($miniwald);
        $observation->setItems(new ObservationItemCollection([]));

        $this->exportService->method('getObservationType')->willReturn('animals');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        $headerIndex = array_flip($rows[0]);
        $this->assertSame('2024-05-15', $rows[1][$headerIndex['date']]);
        $this->assertSame('14:30:00', $rows[1][$headerIndex['time']]);
        // formatDecimal outputs German-locale commas (Excel compatibility)
        $this->assertSame('51,12345', $rows[1][$headerIndex['latitude']]);
        $this->assertSame('9,98765', $rows[1][$headerIndex['longitude']]);
        $this->assertSame('sunny', $rows[1][$headerIndex['weather']]);
        $this->assertSame('calm', $rows[1][$headerIndex['wind']]);
        $this->assertSame('General note', $rows[1][$headerIndex['general_remarks']]);
        $this->assertSame('Miniwald Hambach', $rows[1][$headerIndex['miniwald']]);
    }

    public function testSpeciesFieldsPopulatedForAnimalItem(): void
    {
        $category = new SpeciesCategoryEntity();
        $category->setId(Uuid::randomHex());

        $species = $this->buildSpecies('Vulpes vulpes', $category);

        $item = $this->buildAnimalItem(count: 3, species: $species, remarks: 'Fuchs am Waldrand');
        $observation = $this->buildObservation();
        $observation->setItems(new ObservationItemCollection([$item]));

        $this->exportService->method('getObservationType')->willReturn('animals');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        $headerIndex = array_flip($rows[0]);
        $this->assertSame('Vulpes vulpes', $rows[1][$headerIndex['scientific_name']]);
        $this->assertSame('3', $rows[1][$headerIndex['count']]);
        $this->assertSame('Fuchs am Waldrand', $rows[1][$headerIndex['item_remarks']]);
    }

    public function testCustomEntryFallsBackToObservationSpeciesCategory(): void
    {
        $category = new SpeciesCategoryEntity();
        $category->setId(Uuid::randomHex());
        $category->setTranslated(['name' => 'Vögel']);

        $item = $this->buildAnimalItem(count: 2);
        $item->setCustomSpeciesName('Unbekannter Vogel');

        $observation = $this->buildObservation();
        $observation->setItems(new ObservationItemCollection([$item]));
        $observation->setSpeciesCategory($category);

        $this->exportService->method('getObservationType')->willReturn('animals');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        $headerIndex = array_flip($rows[0]);
        $this->assertSame('Vögel', $rows[1][$headerIndex['species_category']]);
        $this->assertSame('Unbekannter Vogel', $rows[1][$headerIndex['custom_species_name']]);
    }

    public function testItemSpeciesCategoryTakesPrecedenceOverObservationCategory(): void
    {
        $itemCategory = new SpeciesCategoryEntity();
        $itemCategory->setId(Uuid::randomHex());
        $itemCategory->setTranslated(['name' => 'Insekten']);

        $observationCategory = new SpeciesCategoryEntity();
        $observationCategory->setId(Uuid::randomHex());
        $observationCategory->setTranslated(['name' => 'Vögel']);

        $species = $this->buildSpecies('Coccinella septempunctata', $itemCategory);
        $item = $this->buildAnimalItem(count: 1, species: $species);

        $observation = $this->buildObservation();
        $observation->setItems(new ObservationItemCollection([$item]));
        $observation->setSpeciesCategory($observationCategory);

        $this->exportService->method('getObservationType')->willReturn('animals');

        $rows = $this->captureRows(new ObservationCollection([$observation]));

        $headerIndex = array_flip($rows[0]);
        $this->assertSame('Insekten', $rows[1][$headerIndex['species_category']]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildObservation(): ObservationEntity
    {
        $observation = new ObservationEntity();
        $observation->setId(Uuid::randomHex());
        $observation->setObservedAt(new \DateTimeImmutable('2024-05-15 14:30:00'));

        return $observation;
    }

    private function buildSpecies(string $scientificName, ?SpeciesCategoryEntity $category = null): SpeciesEntity
    {
        $species = new SpeciesEntity();
        $species->setId(Uuid::randomHex());
        $species->setScientificName($scientificName);
        if ($category !== null) {
            $species->setSpeciesCategory($category);
        }

        return $species;
    }

    private function buildAnimalItem(
        int $count,
        ?SpeciesEntity $species = null,
        ?string $remarks = null,
        array $photos = [],
    ): ObservationItemEntity {
        $item = new ObservationItemEntity();
        $item->setId(Uuid::randomHex());
        $item->setObservationId(Uuid::randomHex());
        $item->setCount($count);
        $item->setRemarks($remarks);
        $item->setSpecies($species);
        $item->setPhotos(new PhotoCollection($photos));

        return $item;
    }

    /**
     * Run the CSV export and parse the output into an array of rows.
     *
     * @return array<int, array<int, string>>
     */
    private function captureRows(ObservationCollection $collection): array
    {
        $this->exportService->method('loadObservations')->willReturn($collection);

        $context = Context::createDefaultContext();
        $response = $this->service->export([], 'https://example.com', $context);

        // Capture streamed output
        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        // Strip UTF-8 BOM
        $output = ltrim($output, "\xEF\xBB\xBF");

        // Parse CSV
        $rows = [];
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $rows[] = str_getcsv($line, ';');
        }

        return $rows;
    }
}
