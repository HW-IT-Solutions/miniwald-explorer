<?php declare(strict_types=1);

namespace InReNa\Service\Observation;

/**
 * Simple result struct returned by ObservationQueryService::query().
 */
readonly class ObservationResult
{
    /**
     * @param ObservationView[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}
