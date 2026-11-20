<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Service;

use InReNa\Content\Miniwald\MiniwaldCollection;
use InReNa\Content\Miniwald\MiniwaldEntity;
use InReNa\Service\MiniwaldGeoService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \InReNa\Service\MiniwaldGeoService
 */
class MiniwaldGeoServiceTest extends TestCase
{
    private MiniwaldGeoService $service;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(EntityRepository::class);
        $this->service = new MiniwaldGeoService($this->repository);
    }

    public function testCalculateDistanceSamePoint(): void
    {
        $distance = $this->service->calculateDistance(50.9016, 6.2308, 50.9016, 6.2308);
        $this->assertEqualsWithDelta(0.0, $distance, 0.1);
    }

    public function testCalculateDistanceKnownPoints(): void
    {
        // Aldenhoven (50.901600, 6.230825) to Jülich (50.934159, 6.349921)
        // Expected distance: approximately 9.2 km
        $distance = $this->service->calculateDistance(50.901600, 6.230825, 50.934159, 6.349921);
        $this->assertGreaterThan(8000, $distance);
        $this->assertLessThan(10000, $distance);
    }

    public function testCalculateDistanceAldenhovenToLinnich(): void
    {
        // Aldenhoven (50.901600, 6.230825) to Linnich (50.985703, 6.269206)
        // Expected distance: approximately 9.7 km
        $distance = $this->service->calculateDistance(50.901600, 6.230825, 50.985703, 6.269206);
        $this->assertGreaterThan(9000, $distance);
        $this->assertLessThan(11000, $distance);
    }

    public function testCalculateDistanceShortDistance(): void
    {
        // Two points ~100m apart (roughly 0.001 degrees latitude at this latitude)
        $distance = $this->service->calculateDistance(50.9016, 6.2308, 50.9025, 6.2308);
        $this->assertGreaterThan(80, $distance);
        $this->assertLessThan(120, $distance);
    }

    public function testFindNearestMiniwaldWithinRadius(): void
    {
        $miniwaldId = Uuid::randomHex();
        $miniwald = $this->createMiniwaldEntity($miniwaldId, 'Aldenhoven', 50.901600, 6.230825, 500);

        $this->mockRepositorySearch([$miniwald]);

        // Observer is at the exact center of Aldenhoven
        $result = $this->service->findNearestMiniwald(50.901600, 6.230825, Context::createDefaultContext());

        $this->assertNotNull($result);
        $this->assertSame($miniwaldId, $result['miniwaldId']);
        $this->assertSame('Aldenhoven', $result['name']);
        $this->assertLessThan(1.0, $result['distance']);
    }

    public function testFindNearestMiniwaldOutsideRadius(): void
    {
        $miniwaldId = Uuid::randomHex();
        $miniwald = $this->createMiniwaldEntity($miniwaldId, 'Aldenhoven', 50.901600, 6.230825, 500);

        $this->mockRepositorySearch([$miniwald]);

        // Observer is ~9km away (at Jülich coordinates)
        $result = $this->service->findNearestMiniwald(50.934159, 6.349921, Context::createDefaultContext());

        $this->assertNull($result);
    }

    public function testFindNearestMiniwaldSelectsClosest(): void
    {
        $aldenhovenId = Uuid::randomHex();
        $aldenhoven = $this->createMiniwaldEntity($aldenhovenId, 'Aldenhoven', 50.901600, 6.230825, 1000);

        $julichId = Uuid::randomHex();
        $julich = $this->createMiniwaldEntity($julichId, 'Jülich', 50.934159, 6.349921, 1000);

        $this->mockRepositorySearch([$aldenhoven, $julich]);

        // Observer is very close to Aldenhoven center
        $result = $this->service->findNearestMiniwald(50.9017, 6.2309, Context::createDefaultContext());

        $this->assertNotNull($result);
        $this->assertSame($aldenhovenId, $result['miniwaldId']);
        $this->assertSame('Aldenhoven', $result['name']);
    }

    public function testFindNearestMiniwaldNoMiniwaelder(): void
    {
        $this->mockRepositorySearch([]);

        $result = $this->service->findNearestMiniwald(50.901600, 6.230825, Context::createDefaultContext());

        $this->assertNull($result);
    }

    public function testFindNearestMiniwaldDefaultRadius(): void
    {
        $miniwaldId = Uuid::randomHex();
        // Miniwald with null radius (should default to 500m)
        $miniwald = $this->createMiniwaldEntity($miniwaldId, 'TestWald', 50.901600, 6.230825, null);

        $this->mockRepositorySearch([$miniwald]);

        // Observer is ~200m away (within default 500m radius)
        $result = $this->service->findNearestMiniwald(50.9034, 6.2308, Context::createDefaultContext());

        $this->assertNotNull($result);
        $this->assertSame($miniwaldId, $result['miniwaldId']);
    }

    public function testFindNearestMiniwaldDistanceIsRounded(): void
    {
        $miniwaldId = Uuid::randomHex();
        $miniwald = $this->createMiniwaldEntity($miniwaldId, 'Aldenhoven', 50.901600, 6.230825, 500);

        $this->mockRepositorySearch([$miniwald]);

        $result = $this->service->findNearestMiniwald(50.9020, 6.2310, Context::createDefaultContext());

        $this->assertNotNull($result);
        // Distance should be rounded to 1 decimal place
        $this->assertSame(round($result['distance'], 1), $result['distance']);
    }

    private function createMiniwaldEntity(string $id, string $name, float $lat, float $lng, ?int $radius): MiniwaldEntity
    {
        $entity = new MiniwaldEntity();
        $entity->setId($id);
        $entity->setName($name);
        $entity->setLatitude($lat);
        $entity->setLongitude($lng);
        $entity->setRadiusMeters($radius);

        return $entity;
    }

    /**
     * @param MiniwaldEntity[] $entities
     */
    private function mockRepositorySearch(array $entities): void
    {
        $collection = new MiniwaldCollection($entities);

        $searchResult = $this->createStub(EntitySearchResult::class);
        $searchResult->method('getIterator')->willReturn($collection->getIterator());

        $this->repository->method('search')->willReturn($searchResult);
    }
}
