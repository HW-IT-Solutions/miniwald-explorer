<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Subspecies;

use InReNa\Content\Species\SpeciesEntity;
use InReNa\Content\Subspecies\SubspeciesEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;

#[CoversClass(SubspeciesEntity::class)]
class SubspeciesEntityTest extends TestCase
{
    private SubspeciesEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new SubspeciesEntity();
    }

    public function testDefaultValues(): void
    {
        static::assertNull($this->entity->getSpeciesId());
        static::assertNull($this->entity->getName());
        static::assertNull($this->entity->getScientificName());
        static::assertNull($this->entity->getTaxonId());
        static::assertNull($this->entity->getDescription());
        static::assertNull($this->entity->getImageId());
        static::assertNull($this->entity->getPosition());
        static::assertNull($this->entity->isActive());
        static::assertNull($this->entity->getSpecies());
        static::assertNull($this->entity->getImage());
    }

    public function testGetSetSpeciesId(): void
    {
        $uuid = 'species-uuid-1234';
        $this->entity->setSpeciesId($uuid);
        static::assertSame($uuid, $this->entity->getSpeciesId());
    }

    public function testGetSetSpeciesIdNull(): void
    {
        $this->entity->setSpeciesId(null);
        static::assertNull($this->entity->getSpeciesId());
    }

    public function testGetSetName(): void
    {
        $this->entity->setName('Siebenpunkt-Marienkäfer');
        static::assertSame('Siebenpunkt-Marienkäfer', $this->entity->getName());
    }

    public function testGetSetScientificName(): void
    {
        $this->entity->setScientificName('Coccinella septempunctata');
        static::assertSame('Coccinella septempunctata', $this->entity->getScientificName());
    }

    public function testGetSetTaxonId(): void
    {
        $this->entity->setTaxonId('67890');
        static::assertSame('67890', $this->entity->getTaxonId());
    }

    public function testGetSetTaxonIdNull(): void
    {
        $this->entity->setTaxonId(null);
        static::assertNull($this->entity->getTaxonId());
    }

    public function testGetSetDescription(): void
    {
        $description = 'Der bekannteste Marienkäfer mit sieben schwarzen Punkten';
        $this->entity->setDescription($description);
        static::assertSame($description, $this->entity->getDescription());
    }

    public function testGetSetImageId(): void
    {
        $uuid = 'img-uuid-9999';
        $this->entity->setImageId($uuid);
        static::assertSame($uuid, $this->entity->getImageId());
    }

    public function testGetSetPosition(): void
    {
        $this->entity->setPosition(1);
        static::assertSame(1, $this->entity->getPosition());
    }

    public function testIsSetActive(): void
    {
        $this->entity->setActive(true);
        static::assertTrue($this->entity->isActive());

        $this->entity->setActive(false);
        static::assertFalse($this->entity->isActive());

        $this->entity->setActive(null);
        static::assertNull($this->entity->isActive());
    }

    public function testGetSetSpecies(): void
    {
        $species = new SpeciesEntity();
        $species->setId('species-id');
        $species->setScientificName('Coccinellidae');
        $this->entity->setSpecies($species);
        static::assertSame($species, $this->entity->getSpecies());
        static::assertSame('Coccinellidae', $this->entity->getSpecies()->getScientificName());
    }

    public function testGetSetSpeciesNull(): void
    {
        $this->entity->setSpecies(null);
        static::assertNull($this->entity->getSpecies());
    }

    public function testGetSetImage(): void
    {
        $media = new MediaEntity();
        $media->setId('media-id');
        $this->entity->setImage($media);
        static::assertSame($media, $this->entity->getImage());
    }

    public function testGetSetImageNull(): void
    {
        $this->entity->setImage(null);
        static::assertNull($this->entity->getImage());
    }
}
