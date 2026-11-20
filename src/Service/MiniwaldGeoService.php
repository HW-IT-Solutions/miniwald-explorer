<?php declare(strict_types=1);

namespace InReNa\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

/**
 * Service for GPS-based Miniwald detection.
 *
 * Uses the Haversine formula to calculate the distance between the observer's
 * GPS position and each Miniwald's center point, then checks if the observer
 * is within the Miniwald's configured detection radius.
 */
class MiniwaldGeoService
{
    private const EARTH_RADIUS_METERS = 6_371_000;

    public function __construct(
        private readonly EntityRepository $miniwaldRepository
    ) {
    }

    /**
     * Find the nearest Miniwald to the given GPS coordinates.
     *
     * @return array{miniwaldId: string, name: string, distance: float}|null
     *                                                                       Returns the nearest Miniwald if within its radius, or null if none found.
     */
    public function findNearestMiniwald(float $latitude, float $longitude, Context $context): ?array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new NotFilter(NotFilter::CONNECTION_AND, [
                new EqualsFilter('latitude', null),
            ]),
            new NotFilter(NotFilter::CONNECTION_AND, [
                new EqualsFilter('longitude', null),
            ]),
        ]));

        $miniwaelder = $this->miniwaldRepository->search($criteria, $context);

        $nearest = null;
        $nearestDistance = PHP_FLOAT_MAX;

        foreach ($miniwaelder as $miniwald) {
            /** @var \InReNa\Content\Miniwald\MiniwaldEntity $miniwald */
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $miniwald->getLatitude(),
                $miniwald->getLongitude()
            );

            $radiusMeters = $miniwald->getRadiusMeters() ?? 500;

            if ($distance <= $radiusMeters && $distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearest = [
                    'miniwaldId' => $miniwald->getId(),
                    'name' => $miniwald->getName(),
                    'distance' => round($distance, 1),
                ];
            }
        }

        return $nearest;
    }

    /**
     * Calculate the distance between two GPS coordinates using the Haversine formula.
     *
     * @return float Distance in meters
     */
    public function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2)
            + cos($lat1Rad) * cos($lat2Rad)
            * sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }
}
