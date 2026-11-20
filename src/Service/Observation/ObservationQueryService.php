<?php declare(strict_types=1);

namespace InReNa\Service\Observation;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class ObservationQueryService
{
    public function __construct(
        private readonly EntityRepository $observationRepository,
    ) {
    }

    public function query(ObservationFilter $filter, Context $context): ObservationResult
    {
        // No limit/offset on the DAL query: we paginate the expanded ObservationView
        // items in PHP, not the parent Observation records. One Observation expands
        // into N items (ObservationItems + PlantMeasurements), so applying limit/offset
        // at the Observation level produces wrong page sizes and wrong totals.
        $criteria = (new Criteria())
            ->addAssociation('miniwald')
            ->addAssociation('speciesCategory')
            ->addAssociation('items.species')
            ->addAssociation('items.species.image')
            ->addAssociation('items.species.image.thumbnails')
            ->addAssociation('items.species.speciesCategory')
            ->addAssociation('items.subspecies')
            ->addAssociation('items.subspecies.image')
            ->addAssociation('items.subspecies.image.thumbnails')
            ->addAssociation('plantMeasurements.plant')
            ->addAssociation('plantMeasurements.plant.image')
            ->addAssociation('plantMeasurements.plant.image.thumbnails')
            ->addSorting(new FieldSorting('observedAt', $filter->order));

        if ($filter->miniwaldId === 'other') {
            $criteria->addFilter(new EqualsFilter('miniwaldId', null));
        } elseif ($filter->miniwaldId !== null) {
            $criteria->addFilter(new EqualsFilter('miniwaldId', $filter->miniwaldId));
        }
        if ($filter->customerId !== null) {
            $criteria->addFilter(new EqualsFilter('customerId', $filter->customerId));
        }
        if ($filter->speciesCategoryId !== null) {
            // Match via the cataloged species OR the observation-level category,
            // so observations containing only custom entries are included too
            $criteria->addFilter(new OrFilter([
                new EqualsFilter('items.species.speciesCategoryId', $filter->speciesCategoryId),
                new EqualsFilter('speciesCategoryId', $filter->speciesCategoryId),
            ]));
        }
        if ($filter->speciesId !== null) {
            $criteria->addFilter(new EqualsFilter('items.speciesId', $filter->speciesId));
        }
        if ($filter->plantId !== null) {
            $criteria->addFilter(new EqualsFilter('plantMeasurements.plantId', $filter->plantId));
        } elseif ($filter->plantsOnly) {
            $criteria->addFilter(new NotFilter(
                NotFilter::CONNECTION_AND,
                [new EqualsFilter('plantMeasurements.id', null)]
            ));
        }
        if ($filter->dateFrom !== null) {
            $criteria->addFilter(new RangeFilter('observedAt', [
                RangeFilter::GTE => $filter->dateFrom->format('Y-m-d 00:00:00'),
            ]));
        }
        if ($filter->dateTo !== null) {
            $criteria->addFilter(new RangeFilter('observedAt', [
                RangeFilter::LTE => $filter->dateTo->format('Y-m-d 23:59:59'),
            ]));
        }
        if ($filter->statuses !== null) {
            $criteria->addFilter(new EqualsAnyFilter('status', $filter->statuses));
        }

        $result = $this->observationRepository->search($criteria, $context);

        // Expand all matching observations into a flat list of ObservationView items.
        // Filters that target associations (speciesCategoryId, speciesId, plantId) are
        // re-applied in PHP here because Shopware DAL association filters determine
        // *which Observations* are returned but do NOT pre-filter the loaded association
        // collections on each entity. Without this second pass, items from other
        // categories/species would leak into the result.
        $allItems = [];

        foreach ($result->getEntities() as $obs) {
            /** @var \InReNa\Content\Observation\ObservationEntity $obs */
            $miniwaldName = $obs->getMiniwald()?->getName();
            $observedAt = $obs->getObservedAt();

            // Animal items — excluded entirely when only plants are requested
            if (!$filter->plantsOnly && $obs->getItems() !== null) {
                foreach ($obs->getItems() as $item) {
                    // PHP-level filter: skip items that don't match the requested species
                    if ($filter->speciesId !== null && $item->getSpeciesId() !== $filter->speciesId) {
                        continue;
                    }

                    // PHP-level filter: skip items whose species category doesn't
                    // match; custom entries use the observation-level category
                    if ($filter->speciesCategoryId !== null) {
                        $catId = $item->getSpecies()?->getSpeciesCategoryId()
                            ?? $obs->getSpeciesCategoryId();
                        if ($catId !== $filter->speciesCategoryId) {
                            continue;
                        }
                    }

                    $view = new ObservationView();
                    $view->id = $item->getId();
                    $view->type = 'animal';
                    $view->name = $item->getDisplayName();
                    $view->count = $item->getCount();
                    $view->miniwaldName = $miniwaldName;
                    $view->observedAt = $observedAt;

                    $species = $item->getSpecies();
                    if ($species !== null) {
                        $view->image = $item->getSubspecies()?->getImage() ?? $species->getImage();
                    }

                    // Category via the cataloged species, falling back to the
                    // observation-level category for custom entries.
                    // Technical names are plural (birds, butterflies, insects,
                    // soil — see the seeded categories); subType stays singular
                    // to match the icon names used in the templates.
                    $catTechnicalName = $species?->getSpeciesCategory()?->getTechnicalName()
                        ?? $obs->getSpeciesCategory()?->getTechnicalName();
                    if ($catTechnicalName !== null) {
                        $view->subType = match ($catTechnicalName) {
                            'birds' => 'bird',
                            'butterflies' => 'butterfly',
                            'insects' => 'insect',
                            'soil' => 'ground',
                            default => 'unknown',
                        };
                    }

                    $allItems[] = $view;
                }
            }

            // Plant measurements — only included when no animal-only filters are active
            if ($filter->speciesCategoryId === null && $filter->speciesId === null) {
                if ($obs->getPlantMeasurements() !== null) {
                    foreach ($obs->getPlantMeasurements() as $measurement) {
                        // PHP-level filter: skip measurements for other plants
                        if ($filter->plantId !== null && $measurement->getPlantId() !== $filter->plantId) {
                            continue;
                        }

                        $view = new ObservationView();
                        $view->id = $measurement->getId();
                        $view->type = 'plant';
                        $view->subType = null;
                        $view->count = 1;
                        $view->miniwaldName = $miniwaldName;
                        $view->observedAt = $observedAt;
                        $view->weather = $obs->getWeather();
                        $view->wind = $obs->getWind();

                        $plant = $measurement->getPlant();
                        $view->name = $plant?->getName()
                            ?? $measurement->getCustomPlantName()
                            ?? '–';
                        $view->image = $plant?->getImage();
                        $view->plantId = $measurement->getPlantId();
                        $view->heightCm = $measurement->getHeightCm();
                        $view->trunkCircumferenceCm = $measurement->getTrunkCircumferenceCm();
                        $view->crownDiameterCm = $measurement->getCrownDiameterCm();

                        $allItems[] = $view;
                    }
                }
            }

            // Ground measurements excluded per design requirement
        }

        // Total is the count of all expanded items across all pages.
        $totalCount = count($allItems);

        // Apply pagination in PHP against the flat item list.
        $offset = $filter->offset ?? 0;
        if ($filter->limit !== null) {
            $pageItems = array_slice($allItems, $offset, $filter->limit);
        } else {
            $pageItems = $offset > 0 ? array_slice($allItems, $offset) : $allItems;
        }

        return new ObservationResult($pageItems, $totalCount);
    }
}
