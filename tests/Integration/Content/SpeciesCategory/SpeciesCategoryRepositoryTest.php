<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content\SpeciesCategory;

use InReNa\Content\SpeciesCategory\SpeciesCategoryDefinition;
use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

#[CoversClass(SpeciesCategoryDefinition::class)]
class SpeciesCategoryRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private EntityRepository $repository;
    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository('miniwald_explorer_species_category');
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testCreateSpeciesCategory(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-1', [
            'name' => 'Mammals',
            'description' => 'Warm-blooded animals',
            'position' => 1,
            'active' => true,
        ]);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('cat-1')]),
            $this->context
        );

        static::assertCount(1, $result);
        /** @var SpeciesCategoryEntity $entity */
        $entity = $result->first();
        static::assertSame('Mammals', $entity->getTranslation('name'));
        static::assertSame('Warm-blooded animals', $entity->getTranslation('description'));
        static::assertSame(1, $entity->getPosition());
        static::assertTrue($entity->isActive());
    }

    public function testCreateWithTranslation(): void
    {
        $this->repository->create([
            [
                'id' => $this->ids->get('cat-trans'),
                'position' => 0,
                'active' => true,
                'translations' => [
                    'en-GB' => [
                        'name' => 'Birds',
                        'description' => 'Feathered creatures',
                    ],
                ],
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('cat-trans')]),
            $this->context
        )->first();

        static::assertSame('Birds', $entity->getTranslation('name'));
        static::assertSame('Feathered creatures', $entity->getTranslation('description'));
    }

    public function testUpdateSpeciesCategory(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-upd', [
            'name' => 'Original',
            'position' => 0,
            'active' => true,
        ]);

        $this->repository->update([
            [
                'id' => $this->ids->get('cat-upd'),
                'position' => 5,
                'active' => false,
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('cat-upd')]),
            $this->context
        )->first();

        static::assertSame(5, $entity->getPosition());
        static::assertFalse($entity->isActive());
    }

    public function testUpdateTranslation(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-trupd', ['name' => 'Old Name']);

        $this->repository->update([
            [
                'id' => $this->ids->get('cat-trupd'),
                'name' => 'New Name',
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('cat-trupd')]),
            $this->context
        )->first();

        static::assertSame('New Name', $entity->getTranslation('name'));
    }

    public function testDeleteSpeciesCategory(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-del');

        $this->repository->delete([
            ['id' => $this->ids->get('cat-del')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('cat-del')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testDefaultValues(): void
    {
        $this->repository->create([
            [
                'id' => $this->ids->get('cat-defaults'),
                'name' => 'Defaults Test',
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('cat-defaults')]),
            $this->context
        )->first();

        static::assertSame(0, $entity->getPosition());
        static::assertTrue($entity->isActive());
    }

    public function testImageAssociation(): void
    {
        $this->createMedia($this->ids, 'media-cat');
        $this->createSpeciesCategory($this->ids, 'cat-img', [
            'name' => 'With Image',
            'imageId' => $this->ids->get('media-cat'),
        ]);

        $criteria = new Criteria([$this->ids->get('cat-img')]);
        $criteria->addAssociation('image');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getImage());
        static::assertSame($this->ids->get('media-cat'), $entity->getImageId());
    }

    public function testImageSetNullOnMediaDelete(): void
    {
        $this->createMedia($this->ids, 'media-del');
        $this->createSpeciesCategory($this->ids, 'cat-media-del', [
            'name' => 'Will lose image',
            'imageId' => $this->ids->get('media-del'),
        ]);

        $this->getRepository('media')->delete([
            ['id' => $this->ids->get('media-del')],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('cat-media-del')]),
            $this->context
        )->first();

        static::assertNull($entity->getImageId());
    }

    public function testSpeciesAssociationLoading(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-assoc', ['name' => 'Parent']);
        $this->createSpecies($this->ids, 'sp-1', 'cat-assoc', ['scientificName' => 'Vulpes vulpes']);
        $this->createSpecies($this->ids, 'sp-2', 'cat-assoc', ['scientificName' => 'Canis lupus']);

        $criteria = new Criteria([$this->ids->get('cat-assoc')]);
        $criteria->addAssociation('species');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getSpecies());
        static::assertCount(2, $entity->getSpecies());
    }

    public function testSearchByName(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-s1', ['name' => 'Mammals']);
        $this->createSpeciesCategory($this->ids, 'cat-s2', ['name' => 'Birds']);
        $this->createSpeciesCategory($this->ids, 'cat-s3', ['name' => 'Insects']);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'Birds'));

        $result = $this->repository->search($criteria, $this->context);

        static::assertGreaterThanOrEqual(1, $result->count());
        static::assertSame('Birds', $result->first()->getTranslation('name'));
    }

    public function testSortByPosition(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-p3', ['name' => 'Third', 'position' => 3]);
        $this->createSpeciesCategory($this->ids, 'cat-p1', ['name' => 'First', 'position' => 1]);
        $this->createSpeciesCategory($this->ids, 'cat-p2', ['name' => 'Second', 'position' => 2]);

        $criteria = new Criteria([
            $this->ids->get('cat-p1'),
            $this->ids->get('cat-p2'),
            $this->ids->get('cat-p3'),
        ]);
        $criteria->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));

        $result = $this->repository->search($criteria, $this->context);
        $names = array_values(array_map(
            fn ($entity) => $entity->getTranslation('name'),
            $result->getElements()
        ));

        static::assertSame('First', $names[0]);
        static::assertSame('Second', $names[1]);
        static::assertSame('Third', $names[2]);
    }

    public function testTechnicalNameDefaultsToNull(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-tn-null', ['name' => 'No Technical Name']);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('cat-tn-null')]),
            $this->context
        )->first();

        static::assertNull($entity->getTechnicalName());
    }

    public function testCreateWithTechnicalName(): void
    {
        $this->repository->create([
            [
                'id' => $this->ids->get('cat-tn'),
                'name' => 'Birds',
                'technicalName' => 'birds',
                'position' => 1,
                'active' => true,
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('cat-tn')]),
            $this->context
        )->first();

        static::assertSame('birds', $entity->getTechnicalName());
    }

    public function testUpdateTechnicalName(): void
    {
        $this->createSpeciesCategory($this->ids, 'cat-tn-upd', ['name' => 'Insects']);

        $this->repository->update([
            [
                'id' => $this->ids->get('cat-tn-upd'),
                'technicalName' => 'insects',
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('cat-tn-upd')]),
            $this->context
        )->first();

        static::assertSame('insects', $entity->getTechnicalName());
    }

    public function testTechnicalNameCanBeSetToNull(): void
    {
        $this->repository->create([
            [
                'id' => $this->ids->get('cat-tn-clr'),
                'name' => 'Butterflies',
                'technicalName' => 'butterflies',
                'position' => 0,
                'active' => true,
            ],
        ], $this->context);

        $this->repository->update([
            [
                'id' => $this->ids->get('cat-tn-clr'),
                'technicalName' => null,
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('cat-tn-clr')]),
            $this->context
        )->first();

        static::assertNull($entity->getTechnicalName());
    }
}
