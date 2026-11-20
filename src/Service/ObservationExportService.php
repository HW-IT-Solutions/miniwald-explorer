<?php declare(strict_types=1);

namespace InReNa\Service;

use InReNa\Content\Observation\ObservationCollection;
use InReNa\Content\Observation\ObservationDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Shared service for loading observation data for export.
 *
 * Used by both CSV and JSON export to build criteria and load observations
 * with all necessary associations.
 */
class ObservationExportService
{
    public function __construct(
        private readonly EntityRepository $observationRepository,
    ) {
    }

    /**
     * Load observations matching the given filters.
     *
     * @param array<string, mixed> $filters Filter parameters
     * @param int $page Page number (1-based)
     * @param int $limit Items per page
     */
    public function loadObservations(array $filters, Context $context, int $page = 1, int $limit = 500): ObservationCollection
    {
        $criteria = $this->buildCriteria($filters, $page, $limit);

        /** @var ObservationCollection $result */
        $result = $this->observationRepository->search($criteria, $context)->getEntities();

        return $result;
    }

    /**
     * Count total observations matching the given filters.
     */
    public function countObservations(array $filters, Context $context): int
    {
        $criteria = $this->buildCriteria($filters, 1, 1);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);

        return $this->observationRepository->search($criteria, $context)->getTotal();
    }

    /**
     * Determine the observation type.
     *
     * Prefers the persisted type field (singular values, mapped to the
     * plural labels the export format uses); falls back to deriving the
     * type from loaded associations for legacy rows without one.
     */
    public function getObservationType(\InReNa\Content\Observation\ObservationEntity $observation): string
    {
        $persisted = match ($observation->getType()) {
            ObservationDefinition::TYPE_ANIMAL => 'animals',
            ObservationDefinition::TYPE_PLANT => 'plants',
            ObservationDefinition::TYPE_GROUND => 'ground',
            default => null,
        };
        if ($persisted !== null) {
            return $persisted;
        }

        if ($observation->getItems() !== null && $observation->getItems()->count() > 0) {
            return 'animals';
        }
        if ($observation->getPlantMeasurements() !== null && $observation->getPlantMeasurements()->count() > 0) {
            return 'plants';
        }
        if ($observation->getGroundMeasurements() !== null && $observation->getGroundMeasurements()->count() > 0) {
            return 'ground';
        }

        return 'unknown';
    }

    /**
     * Build search criteria from filter parameters.
     *
     * Only verified observations are exported (hardcoded).
     *
     * Supported filters:
     * - dateFrom: string (ISO date)
     * - dateTo: string (ISO date)
     * - type: string (animals|plants|ground)
     * - customerId: string (UUID)
     * - miniwaldId: string (UUID)
     */
    private function buildCriteria(array $filters, int $page, int $limit): Criteria
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset(($page - 1) * $limit);
        $criteria->addSorting(new FieldSorting('observedAt', FieldSorting::DESCENDING));

        // Load all associations needed for export
        $criteria->addAssociation('customer');
        $criteria->addAssociation('miniwald');
        $criteria->addAssociation('speciesCategory');
        $criteria->addAssociation('items.species.speciesCategory');
        $criteria->addAssociation('items.subspecies');
        $criteria->addAssociation('items.photos.media');
        $criteria->addAssociation('groundMeasurements');
        $criteria->addAssociation('plantMeasurements.plant');

        // Apply filters
        if (!empty($filters['dateFrom'])) {
            $criteria->addFilter(new RangeFilter('observedAt', [
                RangeFilter::GTE => $this->normalizeDateBoundary($filters['dateFrom'], false),
            ]));
        }

        if (!empty($filters['dateTo'])) {
            $criteria->addFilter(new RangeFilter('observedAt', [
                RangeFilter::LTE => $this->normalizeDateBoundary($filters['dateTo'], true),
            ]));
        }

        if (!empty($filters['customerId'])) {
            $criteria->addFilter(new EqualsFilter('customerId', $filters['customerId']));
        }

        if (($filters['miniwaldId'] ?? null) === 'other') {
            $criteria->addFilter(new EqualsFilter('miniwaldId', null));
        } elseif (!empty($filters['miniwaldId'])) {
            $criteria->addFilter(new EqualsFilter('miniwaldId', $filters['miniwaldId']));
        }

        // Only verified observations are exported
        $criteria->addFilter(new EqualsFilter('status', 'verified'));

        if (!empty($filters['type'])) {
            $this->applyTypeFilter($criteria, $filters['type']);
        }

        return $criteria;
    }

    /**
     * The filter values name whole days, but arrive with arbitrary time
     * components (the admin datepicker sends full ISO timestamps like
     * 2026-07-13T12:45:00.000Z). Comparing them as-is would cut off part of
     * the boundary day, so only the date part is kept and expanded to the
     * start or end of that day.
     */
    private function normalizeDateBoundary(string $date, bool $endOfDay): string
    {
        try {
            $day = new \DateTimeImmutable($date);
        } catch (\Exception) {
            return $date;
        }

        return $day->format('Y-m-d') . ($endOfDay ? ' 23:59:59' : ' 00:00:00');
    }

    private function applyTypeFilter(Criteria $criteria, string $type): void
    {
        // The migration backfilled the type column, so filtering on it
        // covers legacy rows too
        match ($type) {
            'animals' => $criteria->addFilter(
                new EqualsFilter('type', ObservationDefinition::TYPE_ANIMAL)
            ),
            'plants' => $criteria->addFilter(
                new EqualsFilter('type', ObservationDefinition::TYPE_PLANT)
            ),
            'ground' => $criteria->addFilter(
                new EqualsFilter('type', ObservationDefinition::TYPE_GROUND)
            ),
            default => null,
        };
    }
}
