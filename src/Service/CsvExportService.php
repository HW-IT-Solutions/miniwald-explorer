<?php declare(strict_types=1);

namespace InReNa\Service;

use InReNa\Content\GroundMeasurement\GroundMeasurementDefinition;
use InReNa\Content\GroundMeasurement\GroundMeasurementEntity;
use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\ObservationItem\ObservationItemEntity;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service for exporting observations as CSV.
 *
 * Produces a flat CSV where each row represents either:
 * - An observation item (animal observation) with one row per species
 * - A plant measurement
 * - A ground measurement
 *
 * Photo URLs include HMAC-SHA256 auth tokens with 24h validity.
 */
class CsvExportService
{
    public function __construct(
        private readonly ObservationExportService $exportService,
        private readonly PhotoAuthTokenService $tokenService,
    ) {
    }

    /**
     * Generate a CSV StreamedResponse for the given filters.
     *
     * @param array<string, mixed> $filters
     * @param bool $anonymize When true, the observer_name column is omitted (storefront/public export)
     */
    public function export(array $filters, string $baseUrl, Context $context, bool $anonymize = false): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($filters, $baseUrl, $context, $anonymize) {
            $output = fopen('php://output', 'wb'); // @phpstan-ignore shopware.forbidLocalDiskWrite
            if ($output === false) {
                return;
            }

            // Write BOM for Excel UTF-8 compatibility
            fwrite($output, "\xEF\xBB\xBF");

            // Write header row
            fputcsv($output, $this->getHeaders($anonymize), ';');

            // Stream observations in pages
            $page = 1;
            $pageSize = 200;

            do {
                $observations = $this->exportService->loadObservations($filters, $context, $page, $pageSize);

                foreach ($observations as $observation) {
                    $rows = $this->observationToRows($observation, $baseUrl, $anonymize);
                    foreach ($rows as $row) {
                        fputcsv($output, $row, ';');
                    }
                }

                ++$page;
            } while ($observations->count() === $pageSize);

            fclose($output);
        });

        $filename = 'miniwald-observations-' . date('Y-m-d_His') . '.csv';

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }

    /**
     * @return string[]
     */
    private function getHeaders(bool $anonymize = false): array
    {
        $headers = [
            'observation_id',
            'type',
            'date',
            'time',
        ];

        if (!$anonymize) {
            $headers[] = 'observer_name';
        }

        $headers = array_merge($headers, [
            'miniwald',
            'latitude',
            'longitude',
            'weather',
            'wind',
            'general_remarks',
            // Animal observation fields
            'species_category',
            'species_name',
            'scientific_name',
            'subspecies_name',
            'subspecies_scientific_name',
            'custom_species_name',
            'count',
            'item_remarks',
            'photo_urls',
            // Plant measurement fields
            'plant_name',
            'plant_scientific_name',
            'plant_sort_order',
            'height_cm',
            'trunk_circumference_cm',
            'crown_diameter_cm',
            'plant_remarks',
            // Ground measurement fields
            'ground_location_type',
            'light_conditions',
            'air_temperature',
            'ground_temperature',
            'humidity',
            'moisture',
            'ph_value',
            'ground_remarks',
        ]);

        return $headers;
    }

    /**
     * Convert an observation to one or more CSV rows.
     *
     * @return array<int, array<int, string>>
     */
    private function observationToRows(ObservationEntity $observation, string $baseUrl, bool $anonymize = false): array
    {
        $type = $this->exportService->getObservationType($observation);
        $baseRow = $this->buildBaseRow($observation, $type, $anonymize);

        $rows = [];

        switch ($type) {
            case 'animals':
                $items = $observation->getItems();
                if ($items === null || $items->count() === 0) {
                    $rows[] = array_merge($baseRow, $this->emptyAnimalFields(), $this->emptyPlantFields(), $this->emptyGroundFields());
                    break;
                }

                foreach ($items as $item) {
                    $rows[] = array_merge($baseRow, $this->animalFields($item, $observation, $baseUrl), $this->emptyPlantFields(), $this->emptyGroundFields());
                }
                break;

            case 'plants':
                $measurements = $observation->getPlantMeasurements();
                if ($measurements === null || $measurements->count() === 0) {
                    $rows[] = array_merge($baseRow, $this->emptyAnimalFields(), $this->emptyPlantFields(), $this->emptyGroundFields());
                } else {
                    foreach ($measurements as $measurement) {
                        $rows[] = array_merge($baseRow, $this->emptyAnimalFields(), $this->plantFields($measurement), $this->emptyGroundFields());
                    }
                }
                break;

            case 'ground':
                $measurements = $observation->getGroundMeasurements();
                if ($measurements === null || $measurements->count() === 0) {
                    $rows[] = array_merge($baseRow, $this->emptyAnimalFields(), $this->emptyPlantFields(), $this->emptyGroundFields());
                } else {
                    foreach ($measurements as $measurement) {
                        $rows[] = array_merge($baseRow, $this->emptyAnimalFields(), $this->emptyPlantFields(), $this->groundFields($measurement));
                    }
                }
                break;

            default:
                $rows[] = array_merge($baseRow, $this->emptyAnimalFields(), $this->emptyPlantFields(), $this->emptyGroundFields());
                break;
        }

        return $rows;
    }

    /**
     * @return string[]
     */
    private function buildBaseRow(ObservationEntity $observation, string $type, bool $anonymize = false): array
    {
        $customer = $observation->getCustomer();
        $miniwald = $observation->getMiniwald();

        $row = [
            $observation->getId(),
            $type,
            $observation->getObservedAt()?->format('Y-m-d') ?? '',
            $observation->getObservedAt()?->format('H:i:s') ?? '',
        ];

        if (!$anonymize) {
            $row[] = $customer?->getFirstName() ?? '';
        }

        $row = array_merge($row, [
            $miniwald?->getName() ?? '',
            $this->formatDecimal($observation->getLatitude()),
            $this->formatDecimal($observation->getLongitude()),
            $observation->getWeather() ?? '',
            $observation->getWind() ?? '',
            $observation->getRemarks() ?? '',
        ]);

        return $row;
    }

    /**
     * @return string[]
     */
    private function animalFields(ObservationItemEntity $item, ObservationEntity $observation, string $baseUrl): array
    {
        $species = $item->getSpecies();
        $subspecies = $item->getSubspecies();
        // Custom entries (no species) fall back to the category the
        // observation was recorded for
        $category = $species?->getSpeciesCategory() ?? $observation->getSpeciesCategory();

        // Build photo URLs with auth tokens
        $photoUrls = [];
        $photos = $item->getPhotos();
        if ($photos !== null) {
            foreach ($photos as $photo) {
                $urlData = $this->tokenService->generatePhotoUrl($baseUrl, $photo->getId());
                $photoUrls[] = $urlData['url'];
            }
        }

        return [
            $category?->getTranslation('name') ?? '',
            $species?->getTranslation('name') ?? '',
            $species?->getScientificName() ?? '',
            $subspecies?->getTranslation('name') ?? '',
            $subspecies?->getScientificName() ?? '',
            $item->getCustomSpeciesName() ?? '',
            (string) $item->getCount(),
            $item->getRemarks() ?? '',
            implode(' | ', $photoUrls),
        ];
    }

    /**
     * @return string[]
     */
    private function emptyAnimalFields(): array
    {
        return ['', '', '', '', '', '', '', '', ''];
    }

    /**
     * @return string[]
     */
    private function plantFields(?\InReNa\Content\PlantMeasurement\PlantMeasurementEntity $measurement): array
    {
        if ($measurement === null) {
            return $this->emptyPlantFields();
        }

        $plant = $measurement->getPlant();

        return [
            $plant?->getTranslation('name') ?? $measurement->getCustomPlantName() ?? '',
            $plant?->getScientificName() ?? '',
            $plant?->getSortOrder() !== null ? (string) $plant->getSortOrder() : '',
            $this->formatDecimal($measurement->getHeightCm()),
            $this->formatDecimal($measurement->getTrunkCircumferenceCm()),
            $this->formatDecimal($measurement->getCrownDiameterCm()),
            $measurement->getRemarks() ?? '',
        ];
    }

    /**
     * @return string[]
     */
    private function emptyPlantFields(): array
    {
        return ['', '', '', '', '', '', ''];
    }

    /**
     * @return string[]
     */
    private function groundFields(GroundMeasurementEntity $measurement): array
    {
        $lightConditions = $measurement->getLightConditions();
        $lightLabel = $lightConditions !== null
            ? (GroundMeasurementDefinition::LIGHT_LABELS[$lightConditions] ?? $lightConditions)
            : '';

        return [
            $measurement->getLocationTypeLabel(),
            $lightLabel,
            $this->formatDecimal($measurement->getAirTemperature()),
            $this->formatDecimal($measurement->getGroundTemperature()),
            $this->formatDecimal($measurement->getHumidity()),
            $this->formatDecimal($measurement->getMoisture()),
            $this->formatDecimal($measurement->getPhValue()),
            $measurement->getRemarks() ?? '',
        ];
    }

    /**
     * @return string[]
     */
    private function emptyGroundFields(): array
    {
        return ['', '', '', '', '', '', '', ''];
    }

    /**
     * Format a float for German-locale CSV (comma as decimal separator).
     * Prevents Excel from misinterpreting values like "9.6" as dates ("9. Jun").
     */
    private function formatDecimal(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        return str_replace('.', ',', (string) $value);
    }
}
