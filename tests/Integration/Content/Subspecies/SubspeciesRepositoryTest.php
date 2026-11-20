<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content\Subspecies;

use InReNa\Content\Subspecies\SubspeciesDefinition;
use InReNa\Content\Subspecies\SubspeciesEntity;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

#[CoversClass(SubspeciesDefinition::class)]
class SubspeciesRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private EntityRepository $repository;
    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository('miniwald_explorer_subspecies');
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testCreateSubspecies(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat');
        $this->createSubspecies($this->ids, 'sub-1', 'sp', [
            'name' => 'European Red Fox',
            'scientificName' => 'Vulpes vulpes crucigera',
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('sub-1')]),
            $this->context
        )->first();

        static::assertInstanceOf(SubspeciesEntity::class, $entity);
        static::assertSame('Vulpes vulpes crucigera', $entity->getScientificName());
        static::assertSame('European Red Fox', $entity->getTranslation('name'));
    }

    public function testCreateRequiresSpeciesId(): void
    {
        $this->expectException(\Throwable::class);

        $this->repository->create([
            [
                'id' => $this->ids->get('sub-no-sp'),
                'name' => 'Orphan subspecies',
            ],
        ], $this->context);
    }

    public function testUpdateSubspecies(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat');
        $this->createSubspecies($this->ids, 'sub-upd', 'sp', ['scientificName' => 'Old']);

        $this->repository->update([
            [
                'id' => $this->ids->get('sub-upd'),
                'scientificName' => 'New scientific name',
                'position' => 5,
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('sub-upd')]),
            $this->context
        )->first();

        static::assertSame('New scientific name', $entity->getScientificName());
        static::assertSame(5, $entity->getPosition());
    }

    public function testDeleteSubspecies(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat');
        $this->createSubspecies($this->ids, 'sub-del', 'sp');

        $this->repository->delete([
            ['id' => $this->ids->get('sub-del')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('sub-del')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testSpeciesAssociationLoading(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat', ['name' => 'Parent Species']);
        $this->createSubspecies($this->ids, 'sub-assoc', 'sp');

        $criteria = new Criteria([$this->ids->get('sub-assoc')]);
        $criteria->addAssociation('species');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getSpecies());
        static::assertSame('Parent Species', $entity->getSpecies()->getTranslation('name'));
    }

    public function testCascadeDeleteFromSpecies(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp-cascade', 'cat');
        $this->createSubspecies($this->ids, 'sub-cascade', 'sp-cascade');

        $this->getRepository('miniwald_explorer_species')->delete([
            ['id' => $this->ids->get('sp-cascade')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('sub-cascade')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testCascadeDeleteFromCategory(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-cascade');
        $this->createSpecies($this->ids, 'sp-cascade', 'cat-cascade');
        $this->createSubspecies($this->ids, 'sub-cascade', 'sp-cascade');

        $this->getRepository('miniwald_explorer_species_category')->delete([
            ['id' => $this->ids->get('cat-cascade')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('sub-cascade')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testImageSetNullOnMediaDelete(): void
    {
        $this->createMedia($this->ids, 'media-sub');
        $this->createSpeciesCategory($this->ids, 'cat');
        $this->createSpecies($this->ids, 'sp', 'cat');
        $this->createSubspecies($this->ids, 'sub-media', 'sp', [
            'imageId' => $this->ids->get('media-sub'),
        ]);

        $this->getRepository('media')->delete([
            ['id' => $this->ids->get('media-sub')],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('sub-media')]),
            $this->context
        )->first();

        static::assertNull($entity->getImageId());
    }
}
