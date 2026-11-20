<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content\Species;

use InReNa\Content\Species\SpeciesDefinition;
use InReNa\Content\Species\SpeciesEntity;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

#[CoversClass(SpeciesDefinition::class)]
class SpeciesRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private EntityRepository $repository;
    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository('miniwald_explorer_species');
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testCreateSpecies(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp-1', 'cat', [
            'scientificName' => 'Vulpes vulpes',
            'name' => 'Red Fox',
            'description' => 'A common fox species',
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('sp-1')]),
            $this->context
        )->first();

        static::assertInstanceOf(SpeciesEntity::class, $entity);
        static::assertSame('Vulpes vulpes', $entity->getScientificName());
        static::assertSame('Red Fox', $entity->getTranslation('name'));
        static::assertSame('A common fox species', $entity->getTranslation('description'));
    }

    public function testCreateRequiresCategoryId(): void
    {
        $this->expectException(\Throwable::class);

        $this->repository->create([
            [
                'id' => $this->ids->get('sp-no-cat'),
                'scientificName' => 'Orphan species',
                'name' => 'No Category',
            ],
        ], $this->context);
    }

    public function testUpdateSpecies(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp-upd', 'cat', ['scientificName' => 'Old name']);

        $this->repository->update([
            [
                'id' => $this->ids->get('sp-upd'),
                'scientificName' => 'New scientific name',
                'position' => 10,
                'active' => false,
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('sp-upd')]),
            $this->context
        )->first();

        static::assertSame('New scientific name', $entity->getScientificName());
        static::assertSame(10, $entity->getPosition());
        static::assertFalse($entity->isActive());
    }

    public function testDeleteSpecies(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp-del', 'cat');

        $this->repository->delete([
            ['id' => $this->ids->get('sp-del')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('sp-del')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testCategoryAssociationLoading(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat', ['name' => 'Mammals']);
        $this->createSpecies($this->ids, 'sp-assoc', 'cat');

        $criteria = new Criteria([$this->ids->get('sp-assoc')]);
        $criteria->addAssociation('speciesCategory');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getSpeciesCategory());
        static::assertSame('Mammals', $entity->getSpeciesCategory()->getTranslation('name'));
    }

    public function testSubspeciesAssociationLoading(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp-parent', 'cat');
        $this->createSubspecies($this->ids, 'sub-1', 'sp-parent');
        $this->createSubspecies($this->ids, 'sub-2', 'sp-parent');

        $criteria = new Criteria([$this->ids->get('sp-parent')]);
        $criteria->addAssociation('subspecies');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getSubspecies());
        static::assertCount(2, $entity->getSubspecies());
    }

    public function testImageAssociation(): void
    {
        $this->createMedia($this->ids, 'media-sp');
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp-img', 'cat', [
            'imageId' => $this->ids->get('media-sp'),
        ]);

        $criteria = new Criteria([$this->ids->get('sp-img')]);
        $criteria->addAssociation('image');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getImage());
        static::assertSame($this->ids->get('media-sp'), $entity->getImageId());
    }

    public function testImageSetNullOnMediaDelete(): void
    {
        $this->createMedia($this->ids, 'media-del');
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp-media-del', 'cat', [
            'imageId' => $this->ids->get('media-del'),
        ]);

        $this->getRepository('media')->delete([
            ['id' => $this->ids->get('media-del')],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('sp-media-del')]),
            $this->context
        )->first();

        static::assertNull($entity->getImageId());
    }

    public function testCascadeDeleteFromCategory(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-cascade');
        $this->createSpecies($this->ids, 'sp-cascade', 'cat-cascade');

        $this->getRepository('miniwald_explorer_species_category')->delete([
            ['id' => $this->ids->get('cat-cascade')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('sp-cascade')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testDefaultValues(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->repository->create([
            [
                'id' => $this->ids->get('sp-defaults'),
                'speciesCategoryId' => $this->ids->get('cat'),
                'scientificName' => 'Defaults test',
                'name' => 'Defaults',
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('sp-defaults')]),
            $this->context
        )->first();

        static::assertSame(0, $entity->getPosition());
        static::assertTrue($entity->isActive());
    }
}
