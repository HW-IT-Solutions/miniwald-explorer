<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\SpeciesCategory;

use InReNa\Content\SpeciesCategory\SpeciesCategoryCollection;
use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpeciesCategoryCollection::class)]
class SpeciesCategoryCollectionTest extends TestCase
{
    public function testGetExpectedClass(): void
    {
        $collection = new SpeciesCategoryCollection();
        $method = new \ReflectionMethod($collection, 'getExpectedClass');
        static::assertSame(SpeciesCategoryEntity::class, $method->invoke($collection));
    }

    public function testAddAndRetrieveEntities(): void
    {
        $entity1 = new SpeciesCategoryEntity();
        $entity1->setId('cat-1');
        $entity1->setName('Vögel');

        $entity2 = new SpeciesCategoryEntity();
        $entity2->setId('cat-2');
        $entity2->setName('Käfer');

        $collection = new SpeciesCategoryCollection([$entity1, $entity2]);

        static::assertCount(2, $collection);
        static::assertSame($entity1, $collection->get('cat-1'));
        static::assertSame($entity2, $collection->get('cat-2'));
    }

    public function testFirstAndLast(): void
    {
        $entity1 = new SpeciesCategoryEntity();
        $entity1->setId('cat-1');

        $entity2 = new SpeciesCategoryEntity();
        $entity2->setId('cat-2');

        $collection = new SpeciesCategoryCollection([$entity1, $entity2]);

        static::assertSame($entity1, $collection->first());
        static::assertSame($entity2, $collection->last());
    }

    public function testEmptyCollection(): void
    {
        $collection = new SpeciesCategoryCollection();
        static::assertCount(0, $collection);
        static::assertNull($collection->first());
        static::assertNull($collection->last());
    }
}
