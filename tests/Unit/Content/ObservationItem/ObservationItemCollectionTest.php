<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\ObservationItem;

use InReNa\Content\ObservationItem\ObservationItemCollection;
use InReNa\Content\ObservationItem\ObservationItemEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ObservationItemCollection::class)]
class ObservationItemCollectionTest extends TestCase
{
    public function testGetExpectedClass(): void
    {
        $collection = new ObservationItemCollection();
        $method = new \ReflectionMethod($collection, 'getExpectedClass');
        static::assertSame(ObservationItemEntity::class, $method->invoke($collection));
    }

    public function testGetTotalCountEmpty(): void
    {
        $collection = new ObservationItemCollection();
        static::assertSame(0, $collection->getTotalCount());
    }

    public function testGetTotalCountSingleItem(): void
    {
        $item = new ObservationItemEntity();
        $item->setId('item-1');
        $item->setCount(5);

        $collection = new ObservationItemCollection([$item]);
        static::assertSame(5, $collection->getTotalCount());
    }

    public function testGetTotalCountMultipleItems(): void
    {
        $item1 = new ObservationItemEntity();
        $item1->setId('item-1');
        $item1->setCount(3);

        $item2 = new ObservationItemEntity();
        $item2->setId('item-2');
        $item2->setCount(7);

        $item3 = new ObservationItemEntity();
        $item3->setId('item-3');
        $item3->setCount(2);

        $collection = new ObservationItemCollection([$item1, $item2, $item3]);
        static::assertSame(12, $collection->getTotalCount());
    }

    public function testGetTotalCountWithCountOfOne(): void
    {
        $item = new ObservationItemEntity();
        $item->setId('item-1');
        $item->setCount(1);

        $collection = new ObservationItemCollection([$item]);
        static::assertSame(1, $collection->getTotalCount());
    }

    public function testFilterBySpeciesId(): void
    {
        $item1 = new ObservationItemEntity();
        $item1->setId('item-1');
        $item1->setSpeciesId('species-a');
        $item1->setCount(3);

        $item2 = new ObservationItemEntity();
        $item2->setId('item-2');
        $item2->setSpeciesId('species-b');
        $item2->setCount(5);

        $item3 = new ObservationItemEntity();
        $item3->setId('item-3');
        $item3->setSpeciesId('species-a');
        $item3->setCount(2);

        $collection = new ObservationItemCollection([$item1, $item2, $item3]);
        $filtered = $collection->filterBySpeciesId('species-a');

        static::assertCount(2, $filtered);
        static::assertInstanceOf(ObservationItemCollection::class, $filtered);
    }

    public function testFilterBySpeciesIdNoMatch(): void
    {
        $item = new ObservationItemEntity();
        $item->setId('item-1');
        $item->setSpeciesId('species-a');
        $item->setCount(3);

        $collection = new ObservationItemCollection([$item]);
        $filtered = $collection->filterBySpeciesId('species-nonexistent');

        static::assertCount(0, $filtered);
    }

    public function testFilterBySpeciesIdEmpty(): void
    {
        $collection = new ObservationItemCollection();
        $filtered = $collection->filterBySpeciesId('species-a');

        static::assertCount(0, $filtered);
    }

    public function testGetCustomSpeciesItems(): void
    {
        $item1 = new ObservationItemEntity();
        $item1->setId('item-1');
        $item1->setSpeciesId('species-a');
        $item1->setCount(3);

        $item2 = new ObservationItemEntity();
        $item2->setId('item-2');
        $item2->setSpeciesId(null);
        $item2->setCustomSpeciesName('Unbekannter Käfer');
        $item2->setCount(1);

        $item3 = new ObservationItemEntity();
        $item3->setId('item-3');
        $item3->setSpeciesId(null);
        $item3->setCustomSpeciesName('Neuer Schmetterling');
        $item3->setCount(2);

        $collection = new ObservationItemCollection([$item1, $item2, $item3]);
        $customItems = $collection->getCustomSpeciesItems();

        static::assertCount(2, $customItems);
        static::assertInstanceOf(ObservationItemCollection::class, $customItems);
    }

    public function testGetCustomSpeciesItemsExcludesNullCustomName(): void
    {
        $item1 = new ObservationItemEntity();
        $item1->setId('item-1');
        $item1->setSpeciesId(null);
        $item1->setCustomSpeciesName(null);
        $item1->setCount(1);

        $item2 = new ObservationItemEntity();
        $item2->setId('item-2');
        $item2->setSpeciesId(null);
        $item2->setCustomSpeciesName('Custom Species');
        $item2->setCount(1);

        $collection = new ObservationItemCollection([$item1, $item2]);
        $customItems = $collection->getCustomSpeciesItems();

        static::assertCount(1, $customItems);
    }

    public function testGetCustomSpeciesItemsEmpty(): void
    {
        $collection = new ObservationItemCollection();
        $customItems = $collection->getCustomSpeciesItems();

        static::assertCount(0, $customItems);
    }

    public function testGetCustomSpeciesItemsNoneCustom(): void
    {
        $item = new ObservationItemEntity();
        $item->setId('item-1');
        $item->setSpeciesId('species-a');
        $item->setCount(3);

        $collection = new ObservationItemCollection([$item]);
        $customItems = $collection->getCustomSpeciesItems();

        static::assertCount(0, $customItems);
    }
}
