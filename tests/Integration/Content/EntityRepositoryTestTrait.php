<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shared helper trait for integration tests.
 * Provides factory methods to create test entities through the Shopware DAL.
 */
trait EntityRepositoryTestTrait
{
    abstract protected static function getContainer(): ContainerInterface;

    protected function getRepository(string $entityName): EntityRepository
    {
        return static::getContainer()->get($entityName . '.repository');
    }

    protected function createMedia(IdsCollection $ids, string $key, ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $this->getRepository('media')->create([
            [
                'id' => $ids->get($key),
            ],
        ], $context);
    }

    protected function createSpeciesCategory(IdsCollection $ids, string $key, array $overrides = [], ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $data = array_merge([
            'id' => $ids->get($key),
            'name' => 'Category ' . $key,
            'description' => 'Description for ' . $key,
            'position' => 0,
            'active' => true,
        ], $overrides);

        $this->getRepository('miniwald_explorer_species_category')->create([$data], $context);
    }

    protected function createSpecies(IdsCollection $ids, string $key, string $categoryKey, array $overrides = [], ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $data = array_merge([
            'id' => $ids->get($key),
            'speciesCategoryId' => $ids->get($categoryKey),
            'scientificName' => 'Scientia ' . $key,
            'name' => 'Species ' . $key,
            'description' => 'Description for ' . $key,
            'position' => 0,
            'active' => true,
        ], $overrides);

        $this->getRepository('miniwald_explorer_species')->create([$data], $context);
    }

    protected function createSubspecies(IdsCollection $ids, string $key, string $speciesKey, array $overrides = [], ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $data = array_merge([
            'id' => $ids->get($key),
            'speciesId' => $ids->get($speciesKey),
            'name' => 'Subspecies ' . $key,
            'description' => 'Description for ' . $key,
            'position' => 0,
            'active' => true,
        ], $overrides);

        $this->getRepository('miniwald_explorer_subspecies')->create([$data], $context);
    }

    protected function createMiniwald(IdsCollection $ids, string $key, array $overrides = [], ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $data = array_merge([
            'id' => $ids->get($key),
            'name' => 'Miniwald ' . $key,
        ], $overrides);

        $this->getRepository('miniwald_explorer_miniwald')->create([$data], $context);
    }

    protected function createPlant(IdsCollection $ids, string $key, array $overrides = [], ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $data = array_merge([
            'id' => $ids->get($key),
            'name' => 'Plant ' . $key,
            'sortOrder' => 0,
            'active' => true,
        ], $overrides);

        $this->getRepository('miniwald_explorer_plant')->create([$data], $context);
    }

    protected function createObservation(IdsCollection $ids, string $key, array $overrides = [], ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $data = array_merge([
            'id' => $ids->get($key),
            'observedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
        ], $overrides);

        $this->getRepository('miniwald_explorer_observation')->create([$data], $context);
    }

    protected function createObservationItem(IdsCollection $ids, string $key, string $observationKey, array $overrides = [], ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $data = array_merge([
            'id' => $ids->get($key),
            'observationId' => $ids->get($observationKey),
            'count' => 1,
        ], $overrides);

        $this->getRepository('miniwald_explorer_observation_item')->create([$data], $context);
    }

    protected function createGroundMeasurement(IdsCollection $ids, string $key, string $observationKey, array $overrides = [], ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $data = array_merge([
            'id' => $ids->get($key),
            'observationId' => $ids->get($observationKey),
        ], $overrides);

        $this->getRepository('miniwald_explorer_ground_measurement')->create([$data], $context);
    }

    protected function createPlantMeasurement(IdsCollection $ids, string $key, string $observationKey, array $overrides = [], ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $data = array_merge([
            'id' => $ids->get($key),
            'observationId' => $ids->get($observationKey),
        ], $overrides);

        $this->getRepository('miniwald_explorer_plant_measurement')->create([$data], $context);
    }

    protected function createPhoto(IdsCollection $ids, string $key, string $observationItemKey, string $mediaKey, array $overrides = [], ?Context $context = null): void
    {
        $context ??= Context::createDefaultContext();
        $data = array_merge([
            'id' => $ids->get($key),
            'observationItemId' => $ids->get($observationItemKey),
            'mediaId' => $ids->get($mediaKey),
            'sortOrder' => 0,
        ], $overrides);

        $this->getRepository('miniwald_explorer_photo')->create([$data], $context);
    }
}
