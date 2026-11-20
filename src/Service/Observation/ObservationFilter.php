<?php declare(strict_types=1);

namespace InReNa\Service\Observation;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Value object for filtering observation queries.
 * All fields are optional / nullable.
 */
readonly class ObservationFilter
{
    public function __construct(
        public ?int $limit = null,
        public ?int $offset = null,
        public ?string $miniwaldId = null,
        public ?string $speciesCategoryId = null,
        public ?string $speciesId = null,
        public ?string $plantId = null,
        public bool $plantsOnly = false,
        public ?string $customerId = null,
        public ?\DateTimeInterface $dateFrom = null,
        public ?\DateTimeInterface $dateTo = null,
        public string $order = FieldSorting::DESCENDING,
        /**
         * @var string[]|null
         */
        public ?array $statuses = null,
    ) {
    }
}
