<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Species;

use InReNa\Content\Species\SpeciesEntity;
use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use InReNa\Content\Subspecies\SubspeciesCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;

#[CoversClass(SpeciesEntity::class)]
class SpeciesEntityTest extends TestCase
{
    private SpeciesEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new SpeciesEntity();
    }

    public function testDefaultValues(): void
    {
        static::assertNull($this->entity->getSpeciesCategoryId());
        static::assertNull($this->entity->getName());
        static::assertNull($this->entity->getScientificName());
        static::assertNull($this->entity->getTaxonId());
        static::assertNull($this->entity->getDescription());
        static::assertNull($this->entity->getImageId());
        static::assertNull($this->entity->getPosition());
        static::assertNull($this->entity->isActive());
        static::assertNull($this->entity->getSpeciesCategory());
        static::assertNull($this->entity->getImage());
        static::assertNull($this->entity->getSubspecies());
    }

    public function testGetSetSpeciesCategoryId(): void
    {
        $uuid = 'cat-uuid-1234';
        $this->entity->setSpeciesCategoryId($uuid);
        static::assertSame($uuid, $this->entity->getSpeciesCategoryId());
    }

    public function testGetSetSpeciesCategoryIdNull(): void
    {
        $this->entity->setSpeciesCategoryId(null);
        static::assertNull($this->entity->getSpeciesCategoryId());
    }

    public function testGetSetName(): void
    {
        $this->entity->setName('Marienkäfer');
        static::assertSame('Marienkäfer', $this->entity->getName());
    }

    public function testGetSetScientificName(): void
    {
        $this->entity->setScientificName('Coccinellidae');
        static::assertSame('Coccinellidae', $this->entity->getScientificName());
    }

    public function testGetSetScientificNameNull(): void
    {
        $this->entity->setScientificName(null);
        static::assertNull($this->entity->getScientificName());
    }

    public function testGetSetTaxonId(): void
    {
        $this->entity->setTaxonId('12345');
        static::assertSame('12345', $this->entity->getTaxonId());
    }

    public function testGetSetTaxonIdNull(): void
    {
        $this->entity->setTaxonId(null);
        static::assertNull($this->entity->getTaxonId());
    }

    public function testGetSetDescription(): void
    {
        $this->entity->setDescription('Ein kleiner Käfer mit roten Flügeln');
        static::assertSame('Ein kleiner Käfer mit roten Flügeln', $this->entity->getDescription());
    }

    public function testGetSetImageId(): void
    {
        $uuid = 'img-uuid-5678';
        $this->entity->setImageId($uuid);
        static::assertSame($uuid, $this->entity->getImageId());
    }

    public function testGetSetPosition(): void
    {
        $this->entity->setPosition(3);
        static::assertSame(3, $this->entity->getPosition());
    }

    public function testIsSetActive(): void
    {
        $this->entity->setActive(true);
        static::assertTrue($this->entity->isActive());

        $this->entity->setActive(false);
        static::assertFalse($this->entity->isActive());
    }

    public function testGetSetSpeciesCategory(): void
    {
        $category = new SpeciesCategoryEntity();
        $category->setId('cat-id');
        $category->setName('Käfer');
        $this->entity->setSpeciesCategory($category);
        static::assertSame($category, $this->entity->getSpeciesCategory());
        static::assertSame('Käfer', $this->entity->getSpeciesCategory()->getName());
    }

    public function testGetSetSpeciesCategoryNull(): void
    {
        $this->entity->setSpeciesCategory(null);
        static::assertNull($this->entity->getSpeciesCategory());
    }

    public function testGetSetImage(): void
    {
        $media = new MediaEntity();
        $this->entity->setImage($media);
        static::assertSame($media, $this->entity->getImage());
    }

    public function testGetSetSubspecies(): void
    {
        $collection = new SubspeciesCollection();
        $this->entity->setSubspecies($collection);
        static::assertSame($collection, $this->entity->getSubspecies());
    }

    public function testGetSetSubspeciesNull(): void
    {
        $this->entity->setSubspecies(null);
        static::assertNull($this->entity->getSubspecies());
    }
}
