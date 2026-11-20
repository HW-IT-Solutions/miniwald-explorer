<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\SpeciesCategory;

use InReNa\Content\Species\SpeciesCollection;
use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;

#[CoversClass(SpeciesCategoryEntity::class)]
class SpeciesCategoryEntityTest extends TestCase
{
    private SpeciesCategoryEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new SpeciesCategoryEntity();
    }

    public function testDefaultValues(): void
    {
        static::assertNull($this->entity->getName());
        static::assertNull($this->entity->getDescription());
        static::assertNull($this->entity->getImageId());
        static::assertNull($this->entity->getPosition());
        static::assertNull($this->entity->isActive());
        static::assertNull($this->entity->getTechnicalName());
        static::assertNull($this->entity->getImage());
        static::assertNull($this->entity->getSpecies());
    }

    public function testGetSetName(): void
    {
        $this->entity->setName('Vögel');
        static::assertSame('Vögel', $this->entity->getName());
    }

    public function testGetSetNameNull(): void
    {
        $this->entity->setName('Test');
        $this->entity->setName(null);
        static::assertNull($this->entity->getName());
    }

    public function testGetSetDescription(): void
    {
        $this->entity->setDescription('Alle Vogelarten im Miniwald');
        static::assertSame('Alle Vogelarten im Miniwald', $this->entity->getDescription());
    }

    public function testGetSetDescriptionNull(): void
    {
        $this->entity->setDescription(null);
        static::assertNull($this->entity->getDescription());
    }

    public function testGetSetImageId(): void
    {
        $uuid = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $this->entity->setImageId($uuid);
        static::assertSame($uuid, $this->entity->getImageId());
    }

    public function testGetSetImageIdNull(): void
    {
        $this->entity->setImageId(null);
        static::assertNull($this->entity->getImageId());
    }

    public function testGetSetPosition(): void
    {
        $this->entity->setPosition(5);
        static::assertSame(5, $this->entity->getPosition());
    }

    public function testGetSetPositionZero(): void
    {
        $this->entity->setPosition(0);
        static::assertSame(0, $this->entity->getPosition());
    }

    public function testGetSetPositionNull(): void
    {
        $this->entity->setPosition(null);
        static::assertNull($this->entity->getPosition());
    }

    public function testIsSetActive(): void
    {
        $this->entity->setActive(true);
        static::assertTrue($this->entity->isActive());
    }

    public function testIsSetActiveFalse(): void
    {
        $this->entity->setActive(false);
        static::assertFalse($this->entity->isActive());
    }

    public function testIsSetActiveNull(): void
    {
        $this->entity->setActive(null);
        static::assertNull($this->entity->isActive());
    }

    public function testGetSetImage(): void
    {
        $media = new MediaEntity();
        $media->setId('media-id-123');
        $this->entity->setImage($media);
        static::assertSame($media, $this->entity->getImage());
        static::assertSame('media-id-123', $this->entity->getImage()->getId());
    }

    public function testGetSetImageNull(): void
    {
        $this->entity->setImage(null);
        static::assertNull($this->entity->getImage());
    }

    public function testGetSetSpecies(): void
    {
        $collection = new SpeciesCollection();
        $this->entity->setSpecies($collection);
        static::assertSame($collection, $this->entity->getSpecies());
    }

    public function testGetSetSpeciesNull(): void
    {
        $this->entity->setSpecies(null);
        static::assertNull($this->entity->getSpecies());
    }

    public function testGetSetTechnicalName(): void
    {
        $this->entity->setTechnicalName('birds');
        static::assertSame('birds', $this->entity->getTechnicalName());
    }

    public function testGetSetTechnicalNameWithHyphens(): void
    {
        $this->entity->setTechnicalName('soil-organisms');
        static::assertSame('soil-organisms', $this->entity->getTechnicalName());
    }

    public function testGetSetTechnicalNameNull(): void
    {
        $this->entity->setTechnicalName('birds');
        $this->entity->setTechnicalName(null);
        static::assertNull($this->entity->getTechnicalName());
    }
}
