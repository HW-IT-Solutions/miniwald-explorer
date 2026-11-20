<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content\Plant;

use InReNa\Content\Plant\PlantDefinition;
use InReNa\Content\Plant\PlantEntity;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

#[CoversClass(PlantDefinition::class)]
class PlantRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private EntityRepository $repository;
    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository('miniwald_explorer_plant');
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testCreatePlant(): void
    {
        $this->createPlant($this->ids, 'plant-1', [
            'name' => 'Oak Tree',
            'position' => 'A1',
            'sortOrder' => 1,
        ]);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('plant-1')]),
            $this->context
        )->first();

        static::assertInstanceOf(PlantEntity::class, $entity);
        static::assertSame('Oak Tree', $entity->getTranslation('name'));
        static::assertSame('A1', $entity->getPosition());
        static::assertSame(1, $entity->getSortOrder());
    }

    public function testCreateWithTranslation(): void
    {
        $this->repository->create([
            [
                'id' => $this->ids->get('plant-trans'),
                'translations' => [
                    'en-GB' => [
                        'name' => 'Birch',
                    ],
                ],
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('plant-trans')]),
            $this->context
        )->first();

        static::assertSame('Birch', $entity->getTranslation('name'));
    }

    public function testUpdatePlant(): void
    {
        $this->createPlant($this->ids, 'plant-upd');

        $this->repository->update([
            [
                'id' => $this->ids->get('plant-upd'),
                'position' => 'B2',
                'sortOrder' => 5,
                'active' => false,
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('plant-upd')]),
            $this->context
        )->first();

        static::assertSame('B2', $entity->getPosition());
        static::assertSame(5, $entity->getSortOrder());
        static::assertFalse($entity->isActive());
    }

    public function testDeletePlant(): void
    {
        $this->createPlant($this->ids, 'plant-del');

        $this->repository->delete([
            ['id' => $this->ids->get('plant-del')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('plant-del')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testImageSetNullOnMediaDelete(): void
    {
        $this->createMedia($this->ids, 'media-plant');
        $this->createPlant($this->ids, 'plant-media', [
            'imageId' => $this->ids->get('media-plant'),
        ]);

        $this->getRepository('media')->delete([
            ['id' => $this->ids->get('media-plant')],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('plant-media')]),
            $this->context
        )->first();

        static::assertNull($entity->getImageId());
    }

    public function testImageAssociationLoading(): void
    {
        $this->createMedia($this->ids, 'media-plant');
        $this->createPlant($this->ids, 'plant-img', [
            'imageId' => $this->ids->get('media-plant'),
        ]);

        $criteria = new Criteria([$this->ids->get('plant-img')]);
        $criteria->addAssociation('image');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getImage());
    }

    public function testMiniwaldAssociationLoading(): void
    {
        $this->createMiniwald($this->ids, 'mw-plant-assoc', ['name' => 'Sophienhöhe']);
        $this->createPlant($this->ids, 'plant-mw', [
            'miniwaldId' => $this->ids->get('mw-plant-assoc'),
        ]);

        $criteria = new Criteria([$this->ids->get('plant-mw')]);
        $criteria->addAssociation('miniwald');

        $entity = $this->repository->search($criteria, $this->context)->first();

        static::assertNotNull($entity->getMiniwald());
        static::assertSame('Sophienhöhe', $entity->getMiniwald()->getName());
    }

    public function testMiniwaldSetNullOnMiniwaldDelete(): void
    {
        $this->createMiniwald($this->ids, 'mw-to-delete');
        $this->createPlant($this->ids, 'plant-mw-del', [
            'miniwaldId' => $this->ids->get('mw-to-delete'),
        ]);

        $this->getRepository('miniwald_explorer_miniwald')->delete([
            ['id' => $this->ids->get('mw-to-delete')],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('plant-mw-del')]),
            $this->context
        )->first();

        static::assertNull($entity->getMiniwaldId());
    }

    public function testDefaultValues(): void
    {
        $this->repository->create([
            [
                'id' => $this->ids->get('plant-defaults'),
                'name' => 'Default Plant',
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('plant-defaults')]),
            $this->context
        )->first();

        static::assertSame(0, $entity->getSortOrder());
        static::assertTrue($entity->isActive());
    }
}
