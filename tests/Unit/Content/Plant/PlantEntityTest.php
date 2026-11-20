<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Plant;

use InReNa\Content\Miniwald\MiniwaldEntity;
use InReNa\Content\Plant\PlantEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;

#[CoversClass(PlantEntity::class)]
class PlantEntityTest extends TestCase
{
    private PlantEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new PlantEntity();
    }

    public function testDefaultValues(): void
    {
        static::assertNull($this->entity->getMiniwaldId());
        static::assertNull($this->entity->getMiniwald());
        static::assertNull($this->entity->getName());
        static::assertNull($this->entity->getScientificName());
        static::assertNull($this->entity->getImageId());
        static::assertNull($this->entity->getPosition());
        static::assertNull($this->entity->getSortOrder());
        static::assertNull($this->entity->isActive());
        static::assertNull($this->entity->getImage());
    }

    public function testGetSetName(): void
    {
        $this->entity->setName('Eiche');
        static::assertSame('Eiche', $this->entity->getName());
    }

    public function testGetSetScientificName(): void
    {
        $this->entity->setScientificName('Quercus');
        static::assertSame('Quercus', $this->entity->getScientificName());
    }

    public function testGetSetScientificNameNull(): void
    {
        $this->entity->setScientificName(null);
        static::assertNull($this->entity->getScientificName());
    }

    public function testGetSetImageId(): void
    {
        $uuid = 'img-uuid-plant';
        $this->entity->setImageId($uuid);
        static::assertSame($uuid, $this->entity->getImageId());
    }

    public function testGetSetPosition(): void
    {
        $this->entity->setPosition('A1');
        static::assertSame('A1', $this->entity->getPosition());
    }

    public function testGetSetPositionGridFormat(): void
    {
        $this->entity->setPosition('B3');
        static::assertSame('B3', $this->entity->getPosition());
    }

    public function testGetSetPositionNull(): void
    {
        $this->entity->setPosition(null);
        static::assertNull($this->entity->getPosition());
    }

    public function testGetSetSortOrder(): void
    {
        $this->entity->setSortOrder(5);
        static::assertSame(5, $this->entity->getSortOrder());
    }

    public function testGetSetSortOrderNull(): void
    {
        $this->entity->setSortOrder(null);
        static::assertNull($this->entity->getSortOrder());
    }

    public function testIsSetActive(): void
    {
        $this->entity->setActive(true);
        static::assertTrue($this->entity->isActive());

        $this->entity->setActive(false);
        static::assertFalse($this->entity->isActive());
    }

    public function testGetSetMiniwaldId(): void
    {
        $uuid = 'miniwald-uuid-123';
        $this->entity->setMiniwaldId($uuid);
        static::assertSame($uuid, $this->entity->getMiniwaldId());
    }

    public function testGetSetMiniwaldIdNull(): void
    {
        $this->entity->setMiniwaldId(null);
        static::assertNull($this->entity->getMiniwaldId());
    }

    public function testGetSetMiniwald(): void
    {
        $miniwald = new MiniwaldEntity();
        $miniwald->setId('miniwald-entity-id');
        $miniwald->setName('Sophienhöhe');
        $this->entity->setMiniwald($miniwald);
        static::assertSame($miniwald, $this->entity->getMiniwald());
        static::assertSame('Sophienhöhe', $this->entity->getMiniwald()->getName());
    }

    public function testGetSetMiniwaldNull(): void
    {
        $this->entity->setMiniwald(null);
        static::assertNull($this->entity->getMiniwald());
    }

    public function testGetSetImage(): void
    {
        $media = new MediaEntity();
        $media->setId('media-plant-id');
        $this->entity->setImage($media);
        static::assertSame($media, $this->entity->getImage());
    }

    public function testGetSetImageNull(): void
    {
        $this->entity->setImage(null);
        static::assertNull($this->entity->getImage());
    }
}
