<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\ObservationItem;

use InReNa\Content\Observation\ObservationEntity;
use InReNa\Content\ObservationItem\ObservationItemEntity;
use InReNa\Content\Photo\PhotoCollection;
use InReNa\Content\Species\SpeciesEntity;
use InReNa\Content\Subspecies\SubspeciesEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ObservationItemEntity::class)]
class ObservationItemEntityTest extends TestCase
{
    private ObservationItemEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new ObservationItemEntity();
    }

    public function testDefaultNullableValues(): void
    {
        static::assertNull($this->entity->getSpeciesId());
        static::assertNull($this->entity->getSubspeciesId());
        static::assertNull($this->entity->getCustomSpeciesName());
        static::assertNull($this->entity->getRemarks());
        static::assertNull($this->entity->getObservation());
        static::assertNull($this->entity->getSpecies());
        static::assertNull($this->entity->getSubspecies());
        static::assertNull($this->entity->getPhotos());
    }

    public function testGetSetObservationId(): void
    {
        $uuid = 'obs-uuid-1234';
        $this->entity->setObservationId($uuid);
        static::assertSame($uuid, $this->entity->getObservationId());
    }

    public function testGetSetSpeciesId(): void
    {
        $uuid = 'species-uuid-5678';
        $this->entity->setSpeciesId($uuid);
        static::assertSame($uuid, $this->entity->getSpeciesId());
    }

    public function testGetSetSpeciesIdNull(): void
    {
        $this->entity->setSpeciesId(null);
        static::assertNull($this->entity->getSpeciesId());
    }

    public function testGetSetSubspeciesId(): void
    {
        $uuid = 'subspecies-uuid-9012';
        $this->entity->setSubspeciesId($uuid);
        static::assertSame($uuid, $this->entity->getSubspeciesId());
    }

    public function testGetSetSubspeciesIdNull(): void
    {
        $this->entity->setSubspeciesId(null);
        static::assertNull($this->entity->getSubspeciesId());
    }

    public function testGetSetCustomSpeciesName(): void
    {
        $this->entity->setCustomSpeciesName('Unbekannter Schmetterling');
        static::assertSame('Unbekannter Schmetterling', $this->entity->getCustomSpeciesName());
    }

    public function testGetSetCustomSpeciesNameNull(): void
    {
        $this->entity->setCustomSpeciesName(null);
        static::assertNull($this->entity->getCustomSpeciesName());
    }

    public function testGetSetCount(): void
    {
        $this->entity->setCount(5);
        static::assertSame(5, $this->entity->getCount());
    }

    public function testGetSetCountOne(): void
    {
        $this->entity->setCount(1);
        static::assertSame(1, $this->entity->getCount());
    }

    public function testGetSetCountLargeNumber(): void
    {
        $this->entity->setCount(999);
        static::assertSame(999, $this->entity->getCount());
    }

    public function testGetSetRemarks(): void
    {
        $remarks = 'Beobachtet auf einer Blüte am Waldrand';
        $this->entity->setRemarks($remarks);
        static::assertSame($remarks, $this->entity->getRemarks());
    }

    public function testGetSetRemarksNull(): void
    {
        $this->entity->setRemarks(null);
        static::assertNull($this->entity->getRemarks());
    }

    public function testGetSetObservation(): void
    {
        $observation = new ObservationEntity();
        $observation->setId('obs-id');
        $this->entity->setObservation($observation);
        static::assertSame($observation, $this->entity->getObservation());
    }

    public function testGetSetObservationNull(): void
    {
        $this->entity->setObservation(null);
        static::assertNull($this->entity->getObservation());
    }

    public function testGetSetSpecies(): void
    {
        $species = new SpeciesEntity();
        $species->setId('species-id');
        $species->setScientificName('Coccinellidae');
        $this->entity->setSpecies($species);
        static::assertSame($species, $this->entity->getSpecies());
    }

    public function testGetSetSpeciesNull(): void
    {
        $this->entity->setSpecies(null);
        static::assertNull($this->entity->getSpecies());
    }

    public function testGetSetSubspecies(): void
    {
        $subspecies = new SubspeciesEntity();
        $subspecies->setId('sub-id');
        $this->entity->setSubspecies($subspecies);
        static::assertSame($subspecies, $this->entity->getSubspecies());
    }

    public function testGetSetSubspeciesNull(): void
    {
        $this->entity->setSubspecies(null);
        static::assertNull($this->entity->getSubspecies());
    }

    public function testGetSetPhotos(): void
    {
        $collection = new PhotoCollection();
        $this->entity->setPhotos($collection);
        static::assertSame($collection, $this->entity->getPhotos());
    }

    public function testGetSetPhotosNull(): void
    {
        $this->entity->setPhotos(null);
        static::assertNull($this->entity->getPhotos());
    }

    public function testGetDisplayNameWithSubspecies(): void
    {
        $subspecies = $this->createStub(SubspeciesEntity::class);
        $subspecies->method('getTranslation')->willReturn('Siebenpunkt-Marienkäfer');
        $subspecies->method('getName')->willReturn('Siebenpunkt-Marienkäfer');

        $this->entity->setSubspecies($subspecies);
        static::assertSame('Siebenpunkt-Marienkäfer', $this->entity->getDisplayName());
    }

    public function testGetDisplayNameWithSubspeciesTranslationNull(): void
    {
        $subspecies = $this->createStub(SubspeciesEntity::class);
        $subspecies->method('getTranslation')->willReturn(null);
        $subspecies->method('getName')->willReturn('Fallback Name');

        $this->entity->setSubspecies($subspecies);
        static::assertSame('Fallback Name', $this->entity->getDisplayName());
    }

    public function testGetDisplayNameWithSpecies(): void
    {
        $species = $this->createStub(SpeciesEntity::class);
        $species->method('getTranslation')->willReturn('Marienkäfer');
        $species->method('getName')->willReturn('Marienkäfer');

        $this->entity->setSpecies($species);
        static::assertSame('Marienkäfer', $this->entity->getDisplayName());
    }

    public function testGetDisplayNameWithSpeciesTranslationNull(): void
    {
        $species = $this->createStub(SpeciesEntity::class);
        $species->method('getTranslation')->willReturn(null);
        $species->method('getName')->willReturn('Species Fallback');

        $this->entity->setSpecies($species);
        static::assertSame('Species Fallback', $this->entity->getDisplayName());
    }

    public function testGetDisplayNameWithCustomSpeciesName(): void
    {
        $this->entity->setCustomSpeciesName('Unbekannter Käfer');
        static::assertSame('Unbekannter Käfer', $this->entity->getDisplayName());
    }

    public function testGetDisplayNameFallbackToUnknown(): void
    {
        // No subspecies, no species, no custom name
        static::assertSame('Unknown Species', $this->entity->getDisplayName());
    }

    public function testGetDisplayNameSubspeciesTakesPrecedenceOverSpecies(): void
    {
        $species = $this->createStub(SpeciesEntity::class);
        $species->method('getTranslation')->willReturn('Marienkäfer');

        $subspecies = $this->createStub(SubspeciesEntity::class);
        $subspecies->method('getTranslation')->willReturn('Siebenpunkt');
        $subspecies->method('getName')->willReturn('Siebenpunkt');

        $this->entity->setSpecies($species);
        $this->entity->setSubspecies($subspecies);

        // Subspecies should take precedence
        static::assertSame('Siebenpunkt', $this->entity->getDisplayName());
    }

    public function testGetDisplayNameSpeciesTakesPrecedenceOverCustom(): void
    {
        $species = $this->createStub(SpeciesEntity::class);
        $species->method('getTranslation')->willReturn('Marienkäfer');
        $species->method('getName')->willReturn('Marienkäfer');

        $this->entity->setSpecies($species);
        $this->entity->setCustomSpeciesName('Custom Name');

        // Species should take precedence over custom name
        static::assertSame('Marienkäfer', $this->entity->getDisplayName());
    }
}
