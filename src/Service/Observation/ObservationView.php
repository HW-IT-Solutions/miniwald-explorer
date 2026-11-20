<?php declare(strict_types=1);

namespace InReNa\Service\Observation;

use Shopware\Core\Content\Media\MediaEntity;

/**
 * Flat DTO for one observation row in the UI.
 * One instance per animal item or plant measurement.
 * Ground measurements are excluded.
 */
class ObservationView
{
    public string $id;

    /**
     * 'animal' | 'plant'
     */
    public string $type;

    /**
     * 'bird' | 'butterfly' | 'insect' | null (null for plant)
     */
    public ?string $subType = null;

    /**
     * Translated display name (species, plant, or custom name)
     */
    public string $name;

    /**
     * Animal count; always 1 for plant rows
     */
    public int $count = 1;

    /**
     * Species image (animal) or plant image (plant); null if none
     */
    public ?MediaEntity $image = null;

    public ?string $miniwaldName = null;

    public ?\DateTimeInterface $observedAt = null;

    // Plant-only fields

    /**
     * Plant ID — set for plant rows; used by templates to link to the plant detail page.
     */
    public ?string $plantId = null;

    public ?int $heightCm = null;

    public ?float $trunkCircumferenceCm = null;

    public ?int $crownDiameterCm = null;

    // Observation-level fields (populated for plant rows)

    public ?string $weather = null;

    public ?string $wind = null;
}
