<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Service;

use InReNa\Content\Miniwald\MiniwaldEntity;
use InReNa\Content\Observation\ObservationCollection;
use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\ObservationItem\ObservationItemCollection;
use InReNa\Content\ObservationItem\ObservationItemEntity;
use InReNa\Content\Photo\PhotoCollection;
use InReNa\Content\Photo\PhotoEntity;
use InReNa\Content\Species\SpeciesEntity;
use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use InReNa\Content\Subspecies\SubspeciesEntity;
use InReNa\Service\JsonExportService;
use InReNa\Service\ObservationExportService;
use InReNa\Service\PhotoAuthTokenService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \InReNa\Service\JsonExportService
 */
class JsonExportServiceTest extends TestCase
{
    private JsonExportService $service;
    private ObservationExportService $exportService;
    private PhotoAuthTokenService $tokenService;

    protected function setUp(): void
    {
        $this->exportService = $this->createStub(ObservationExportService::class);
        $this->tokenService = $this->createStub(PhotoAuthTokenService::class);
        $this->service = new JsonExportService($this->exportService, $this->tokenService);
    }

    public function testSerializesItemWithSpeciesTaxonId(): void
    {
        $species = new SpeciesEntity();
        $species->setId(Uuid::randomHex());
        $species->setTaxonId('99999');
        $species->setScientificName('Vulpes vulpes');

        $item = $this->buildItem(count: 1, species: $species);
        $observation = $this->buildObservation(items: [$item]);

        $result = $this->runExport($observation);

        $od = $result[0]['observation_data'];
        $this->assertSame('99999', $od['taxon_id']);
        $this->assertSame('Vulpes vulpes', $od['taxon_name']);
    }

    public function testSubspeciesTaxonIdOverridesSpecies(): void
    {
        $species = new SpeciesEntity();
        $species->setId(Uuid::randomHex());
        $species->setTaxonId('11111');
        $species->setScientificName('Capreolus capreolus');

        $subspecies = new SubspeciesEntity();
        $subspecies->setId(Uuid::randomHex());
        $subspecies->setTaxonId('22222');
        $subspecies->setScientificName('Capreolus capreolus capreolus');

        $item = $this->buildItem(count: 1, species: $species, subspecies: $subspecies);
        $observation = $this->buildObservation(items: [$item]);

        $result = $this->runExport($observation);

        $od = $result[0]['observation_data'];
        $this->assertSame('22222', $od['taxon_id']);
        $this->assertSame('Capreolus capreolus capreolus', $od['taxon_name']);
    }

    public function testLocationPrecisionIsGpsWhenCoordsPresent(): void
    {
        $item = $this->buildItem(count: 1);
        $observation = $this->buildObservation(items: [$item], lat: 51.12345, lon: 9.98765);

        $result = $this->runExport($observation);

        $this->assertSame('GPS', $result[0]['observation_data']['location']['precision']);
        $this->assertSame(51.12345, $result[0]['observation_data']['location']['lat']);
        $this->assertSame(9.98765, $result[0]['observation_data']['location']['lon']);
    }

    public function testLocationPrecisionIsMiniwaldWhenNoCoordsPresent(): void
    {
        $item = $this->buildItem(count: 1);
        $observation = $this->buildObservation(items: [$item], lat: null, lon: null);

        $result = $this->runExport($observation);

        $this->assertSame('Miniwald', $result[0]['observation_data']['location']['precision']);
        $this->assertNull($result[0]['observation_data']['location']['lat']);
        $this->assertNull($result[0]['observation_data']['location']['lon']);
    }

    public function testAreaIdIsMiniwaldId(): void
    {
        $miniwaldId = Uuid::randomHex();
        $item = $this->buildItem(count: 1);
        $observation = $this->buildObservation(items: [$item], miniwaldId: $miniwaldId);

        $result = $this->runExport($observation);

        $this->assertSame($miniwaldId, $result[0]['observation_data']['location']['area_id']);
    }

    public function testHabitatIsMiniwaldName(): void
    {
        $miniwald = new MiniwaldEntity();
        $miniwald->setId(Uuid::randomHex());
        $miniwald->setName('Miniwald Hambach');

        $item = $this->buildItem(count: 1);
        $observation = $this->buildObservation(items: [$item], miniwald: $miniwald);

        $result = $this->runExport($observation);

        $this->assertSame('Miniwald Hambach', $result[0]['observation_data']['details']['habitat']);
    }

    public function testHabitatIsNullWhenNoMiniwald(): void
    {
        $item = $this->buildItem(count: 1);
        $observation = $this->buildObservation(items: [$item]);

        $result = $this->runExport($observation);

        $this->assertNull($result[0]['observation_data']['details']['habitat']);
    }

    public function testStaticFieldsCountTypeAndObservationType(): void
    {
        $item = $this->buildItem(count: 3);
        $observation = $this->buildObservation(items: [$item]);

        $result = $this->runExport($observation);

        $details = $result[0]['observation_data']['details'];
        $this->assertSame('genau', $details['count_type']);
        $this->assertSame('Sichtung', $details['observation_type']);
        $this->assertSame(3, $details['count']);
    }

    public function testCommentIsItemRemarks(): void
    {
        $item = $this->buildItem(count: 1, remarks: 'Zwei Rehe am Waldrand');
        $observation = $this->buildObservation(items: [$item]);

        $result = $this->runExport($observation);

        $this->assertSame('Zwei Rehe am Waldrand', $result[0]['observation_data']['details']['comment']);
    }

    public function testMediaArrayContainsPhotoUrl(): void
    {
        $photoId = Uuid::randomHex();
        $photo = new PhotoEntity();
        $photo->setId($photoId);
        $photo->setMediaId(Uuid::randomHex());
        $photo->setCaption('Belegfoto Reh');

        // Need a fresh mock (not stub) here to configure specific return value
        $tokenService = $this->createMock(PhotoAuthTokenService::class);
        $tokenService
            ->expects($this->once())
            ->method('generatePhotoUrl')
            ->with('https://example.com', $photoId)
            ->willReturn(['url' => 'https://example.com/api/_action/miniwald/photo/' . $photoId . '?token=abc', 'expires_at' => '2026-05-10T00:00:00+00:00']);

        $exportService = $this->createStub(ObservationExportService::class);
        $service = new JsonExportService($exportService, $tokenService);

        $item = $this->buildItem(count: 1, photos: [$photo]);
        $observation = $this->buildObservation(items: [$item]);
        $collection = new ObservationCollection([$observation]);

        $exportService->method('countObservations')->willReturn(1);
        $exportService->method('loadObservations')->willReturn($collection);

        $context = Context::createDefaultContext();
        $response = $service->export([], 'https://example.com', $context);
        $data = json_decode($response->getContent(), true);
        $result = $data['observations'];

        $media = $result[0]['observation_data']['media'];
        $this->assertCount(1, $media);
        $this->assertSame('image', $media[0]['type']);
        $this->assertStringContainsString($photoId, $media[0]['url']);
        $this->assertSame('Belegfoto Reh', $media[0]['caption']);
    }

    public function testObservationWithMultipleItemsProducesMultipleRecords(): void
    {
        $species1 = new SpeciesEntity();
        $species1->setId(Uuid::randomHex());
        $species1->setTaxonId('111');
        $species1->setScientificName('Vulpes vulpes');

        $species2 = new SpeciesEntity();
        $species2->setId(Uuid::randomHex());
        $species2->setTaxonId('222');
        $species2->setScientificName('Capreolus capreolus');

        $item1 = $this->buildItem(count: 1, species: $species1);
        $item2 = $this->buildItem(count: 2, species: $species2);
        $observation = $this->buildObservation(items: [$item1, $item2]);

        $result = $this->runExport($observation);

        $this->assertCount(2, $result);
        $this->assertSame('111', $result[0]['observation_data']['taxon_id']);
        $this->assertSame('222', $result[1]['observation_data']['taxon_id']);
    }

    public function testObservationWithNoItemsProducesNoRecords(): void
    {
        $observation = $this->buildObservation(items: []);

        $result = $this->runExport($observation);

        $this->assertCount(0, $result);
    }

    public function testDateAndTimeFromObservedAt(): void
    {
        $item = $this->buildItem(count: 1);
        $observation = $this->buildObservation(items: [$item]);
        $observation->setObservedAt(new \DateTimeImmutable('2024-05-15 14:30:00'));

        $result = $this->runExport($observation);

        $od = $result[0]['observation_data'];
        $this->assertSame('2024-05-15', $od['date']);
        $this->assertSame('14:30:00', $od['time']);
    }

    public function testExportResponseContainsObservationsAndMeta(): void
    {
        $item = $this->buildItem(count: 1);
        $observation = $this->buildObservation(items: [$item]);
        $collection = new ObservationCollection([$observation]);

        $this->exportService->method('countObservations')->willReturn(1);
        $this->exportService->method('loadObservations')->willReturn($collection);

        $context = Context::createDefaultContext();
        $response = $this->service->export([], 'https://example.com', $context);

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('observations', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertSame(1, $data['meta']['total']);
        $this->assertCount(1, $data['observations']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function testCustomEntryUsesObservationSpeciesCategory(): void
    {
        $category = new SpeciesCategoryEntity();
        $category->setId(Uuid::randomHex());
        $category->setTranslated(['name' => 'Vögel']);

        $item = $this->buildItem(count: 2);
        $item->setCustomSpeciesName('Unbekannter Vogel');

        $observation = $this->buildObservation(items: [$item]);
        $observation->setType('animal');
        $observation->setSpeciesCategory($category);

        $result = $this->runExport($observation);

        $od = $result[0]['observation_data'];
        $this->assertNull($od['taxon_id']);
        $this->assertSame('Unbekannter Vogel', $od['custom_species_name']);
        $this->assertSame('Vögel', $od['species_category']);
    }

    public function testPersistedTypeTakesPrecedenceOverAssociations(): void
    {
        // Animal type persisted, but no items loaded: must yield no
        // animal records instead of falling through to plant/ground
        $observation = $this->buildObservation(items: []);
        $observation->setType('animal');

        $result = $this->runExport($observation);

        $this->assertSame([], $result);
    }

    private function buildItem(
        int $count,
        ?SpeciesEntity $species = null,
        ?SubspeciesEntity $subspecies = null,
        ?string $remarks = null,
        array $photos = [],
    ): ObservationItemEntity {
        $item = new ObservationItemEntity();
        $item->setId(Uuid::randomHex());
        $item->setObservationId(Uuid::randomHex());
        $item->setCount($count);
        $item->setRemarks($remarks);
        $item->setSpecies($species);
        $item->setSubspecies($subspecies);
        $item->setPhotos(new PhotoCollection($photos));

        return $item;
    }

    private function buildObservation(
        array $items,
        ?float $lat = 51.0,
        ?float $lon = 6.5,
        ?string $miniwaldId = null,
        ?MiniwaldEntity $miniwald = null,
    ): ObservationEntity {
        $observation = new ObservationEntity();
        $observation->setId(Uuid::randomHex());
        $observation->setObservedAt(new \DateTimeImmutable('2024-05-15 14:30:00'));
        $observation->setLatitude($lat);
        $observation->setLongitude($lon);
        $observation->setMiniwaldId($miniwaldId);
        $observation->setMiniwald($miniwald);
        $observation->setItems(new ObservationItemCollection($items));

        return $observation;
    }

    /**
     * Run export() with a single observation and return the observations array.
     *
     * @return array<int, array<string, mixed>>
     */
    private function runExport(ObservationEntity $observation, string $baseUrl = 'https://example.com'): array
    {
        $collection = new ObservationCollection([$observation]);

        $this->exportService->method('countObservations')->willReturn(1);
        $this->exportService->method('loadObservations')->willReturn($collection);

        $context = Context::createDefaultContext();
        $response = $this->service->export([], $baseUrl, $context);

        $data = json_decode($response->getContent(), true);

        return $data['observations'];
    }
}
