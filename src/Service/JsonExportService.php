<?php declare(strict_types=1);

namespace InReNa\Service;

use InReNa\Content\GroundMeasurement\GroundMeasurementEntity;
use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\ObservationItem\ObservationItemEntity;
use InReNa\Content\PlantMeasurement\PlantMeasurementEntity;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service for exporting observations as JSON.
 *
 * Produces one record per sub-entity:
 * - Animals: one record per ObservationItem (species sighting)
 * - Plants:  one record per PlantMeasurement
 * - Ground:  one record per GroundMeasurement
 *
 * Photo URLs include HMAC-SHA256 auth tokens with 7-day validity.
 * Supports pagination for large datasets.
 */
class JsonExportService
{
    public function __construct(
        private readonly ObservationExportService $exportService,
        private readonly PhotoAuthTokenService $tokenService,
    ) {
    }

    /**
     * Generate a JSON response for the given filters.
     *
     * @param array<string, mixed> $filters
     */
    public function export(array $filters, string $baseUrl, Context $context, int $page = 1, int $limit = 100): JsonResponse
    {
        $total = $this->exportService->countObservations($filters, $context);
        $observations = $this->exportService->loadObservations($filters, $context, $page, $limit);

        $data = [];
        foreach ($observations as $observation) {
            foreach ($this->flattenObservation($observation, $baseUrl) as $record) {
                $data[] = $record;
            }
        }

        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $result = [
            'observations' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'exported_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'filters' => $this->sanitizeFilters($filters),
            ],
        ];

        return new JsonResponse($result);
    }

    /**
     * Export all observations as a downloadable JSON file.
     *
     * @param array<string, mixed> $filters
     */
    public function exportFile(array $filters, string $baseUrl, Context $context): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($filters, $baseUrl, $context) {
            $total = $this->exportService->countObservations($filters, $context);

            echo '{"observations":[';

            $page = 1;
            $pageSize = 200;
            $first = true;

            do {
                $observations = $this->exportService->loadObservations($filters, $context, $page, $pageSize);

                foreach ($observations as $observation) {
                    foreach ($this->flattenObservation($observation, $baseUrl) as $record) {
                        if (!$first) {
                            echo ',';
                        }
                        echo json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $first = false;
                    }
                }

                ++$page;
            } while ($observations->count() === $pageSize);

            echo '],"meta":' . json_encode([
                'total' => $total,
                'exported_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'filters' => $this->sanitizeFilters($filters),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '}';
        });

        $filename = 'miniwald-observations-' . date('Y-m-d_His') . '.json';

        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }

    /**
     * Flatten one Observation into one or more records depending on type:
     * - animals: one record per ObservationItem (wrapped in observation_data key)
     * - plants:  one record per PlantMeasurement (wrapped in plant_data key)
     * - ground:  one record per GroundMeasurement (wrapped in ground_data key)
     *
     * The persisted type field takes precedence; loaded associations are
     * the fallback for legacy rows without one.
     *
     * @return array<int, array<string, mixed>>
     */
    private function flattenObservation(ObservationEntity $observation, string $baseUrl): array
    {
        switch ($observation->getType()) {
            case ObservationDefinition::TYPE_ANIMAL:
                return $this->flattenAnimalObservation($observation, $baseUrl);
            case ObservationDefinition::TYPE_PLANT:
                return $this->flattenPlantObservation($observation);
            case ObservationDefinition::TYPE_GROUND:
                return $this->flattenGroundObservation($observation);
        }

        $items = $observation->getItems();
        if ($items !== null && $items->count() > 0) {
            return $this->flattenAnimalObservation($observation, $baseUrl);
        }

        $plantMeasurements = $observation->getPlantMeasurements();
        if ($plantMeasurements !== null && $plantMeasurements->count() > 0) {
            return $this->flattenPlantObservation($observation);
        }

        $groundMeasurements = $observation->getGroundMeasurements();
        if ($groundMeasurements !== null && $groundMeasurements->count() > 0) {
            return $this->flattenGroundObservation($observation);
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function flattenAnimalObservation(ObservationEntity $observation, string $baseUrl): array
    {
        $items = $observation->getItems();
        if ($items === null || $items->count() === 0) {
            return [];
        }

        $records = [];
        foreach ($items as $item) {
            $records[] = $this->serializeAnimalItem($observation, $item, $baseUrl);
        }

        return $records;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function flattenPlantObservation(ObservationEntity $observation): array
    {
        $measurements = $observation->getPlantMeasurements();
        if ($measurements === null || $measurements->count() === 0) {
            return [];
        }

        $records = [];
        foreach ($measurements as $measurement) {
            $records[] = $this->serializePlantMeasurement($observation, $measurement);
        }

        return $records;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function flattenGroundObservation(ObservationEntity $observation): array
    {
        $measurements = $observation->getGroundMeasurements();
        if ($measurements === null || $measurements->count() === 0) {
            return [];
        }

        $records = [];
        foreach ($measurements as $measurement) {
            $records[] = $this->serializeGroundMeasurement($observation, $measurement);
        }

        return $records;
    }

    /**
     * Serialize one ObservationItem (animal sighting) into a record.
     * Uses the observation_data wrapper to maintain backward compatibility.
     *
     * @return array<string, mixed>
     */
    private function serializeAnimalItem(
        ObservationEntity $observation,
        ObservationItemEntity $item,
        string $baseUrl,
    ): array {
        $species = $item->getSpecies();
        $subspecies = $item->getSubspecies();
        $miniwald = $observation->getMiniwald();

        $taxonId = $subspecies?->getTaxonId() ?? $species?->getTaxonId();
        $taxonName = $subspecies?->getScientificName() ?? $species?->getScientificName();

        $lat = $observation->getLatitude();
        $lon = $observation->getLongitude();

        $media = [];
        $photos = $item->getPhotos();
        if ($photos !== null) {
            foreach ($photos as $photo) {
                $urlData = $this->tokenService->generatePhotoUrl($baseUrl, $photo->getId());
                $media[] = [
                    'type' => 'image',
                    'url' => $urlData['url'],
                    'caption' => $photo->getCaption(),
                ];
            }
        }

        $category = $species?->getSpeciesCategory() ?? $observation->getSpeciesCategory();

        return [
            'observation_data' => [
                'taxon_id' => $taxonId,
                'taxon_name' => $taxonName,
                'custom_species_name' => $item->getCustomSpeciesName(),
                'species_category' => $category?->getTranslation('name'),
                'date' => $observation->getObservedAt()?->format('Y-m-d'),
                'time' => $observation->getObservedAt()?->format('H:i:s'),
                'observer' => $observation->getCustomer()?->getFirstName(),
                'location' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'precision' => ($lat !== null && $lon !== null) ? 'GPS' : 'Miniwald',
                    'area_id' => $observation->getMiniwaldId(),
                ],
                'details' => [
                    'count' => $item->getCount(),
                    'count_type' => 'genau',
                    'observation_type' => 'Sichtung',
                    'habitat' => $miniwald?->getName(),
                    'comment' => $item->getRemarks(),
                ],
                'media' => $media,
            ],
        ];
    }

    /**
     * Serialize one PlantMeasurement into a record.
     *
     * @return array<string, mixed>
     */
    private function serializePlantMeasurement(
        ObservationEntity $observation,
        PlantMeasurementEntity $measurement,
    ): array {
        $plant = $measurement->getPlant();
        $miniwald = $observation->getMiniwald();
        $lat = $observation->getLatitude();
        $lon = $observation->getLongitude();

        return [
            'plant_data' => [
                'observation_id' => $observation->getId(),
                'date' => $observation->getObservedAt()?->format('Y-m-d'),
                'time' => $observation->getObservedAt()?->format('H:i:s'),
                'observer' => $observation->getCustomer()?->getFirstName(),
                'miniwald' => $miniwald?->getName(),
                'location' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'precision' => ($lat !== null && $lon !== null) ? 'GPS' : 'Miniwald',
                    'area_id' => $observation->getMiniwaldId(),
                ],
                'weather' => $observation->getWeather(),
                'wind' => $observation->getWind(),
                'general_remarks' => $observation->getRemarks(),
                'plant_name' => $plant?->getTranslation('name') ?? $measurement->getCustomPlantName(),
                'plant_scientific_name' => $plant?->getScientificName(),
                'height_cm' => $measurement->getHeightCm(),
                'trunk_circumference_cm' => $measurement->getTrunkCircumferenceCm(),
                'crown_diameter_cm' => $measurement->getCrownDiameterCm(),
                'plant_remarks' => $measurement->getRemarks(),
            ],
        ];
    }

    /**
     * Serialize one GroundMeasurement into a record.
     *
     * @return array<string, mixed>
     */
    private function serializeGroundMeasurement(
        ObservationEntity $observation,
        GroundMeasurementEntity $measurement,
    ): array {
        $miniwald = $observation->getMiniwald();
        $lat = $observation->getLatitude();
        $lon = $observation->getLongitude();

        return [
            'ground_data' => [
                'observation_id' => $observation->getId(),
                'date' => $observation->getObservedAt()?->format('Y-m-d'),
                'time' => $observation->getObservedAt()?->format('H:i:s'),
                'observer' => $observation->getCustomer()?->getFirstName(),
                'miniwald' => $miniwald?->getName(),
                'location' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'precision' => ($lat !== null && $lon !== null) ? 'GPS' : 'Miniwald',
                    'area_id' => $observation->getMiniwaldId(),
                ],
                'weather' => $observation->getWeather(),
                'wind' => $observation->getWind(),
                'general_remarks' => $observation->getRemarks(),
                'ground_location_type' => $measurement->getLocationTypeLabel(),
                'air_temperature' => $measurement->getAirTemperature(),
                'ground_temperature' => $measurement->getGroundTemperature(),
                'humidity' => $measurement->getHumidity(),
                'moisture' => $measurement->getMoisture(),
                'ph_value' => $measurement->getPhValue(),
                'ground_remarks' => $measurement->getRemarks(),
            ],
        ];
    }

    /**
     * Remove empty filter values for clean meta output.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function sanitizeFilters(array $filters): array
    {
        return array_filter($filters, static fn ($value) => $value !== null && $value !== '');
    }
}
