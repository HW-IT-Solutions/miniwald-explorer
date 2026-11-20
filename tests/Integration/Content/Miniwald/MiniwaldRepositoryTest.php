<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Content\Miniwald;

use InReNa\Content\Miniwald\MiniwaldDefinition;
use InReNa\Content\Miniwald\MiniwaldEntity;
use InReNa\Tests\Integration\Content\EntityRepositoryTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Test\Stub\Framework\IdsCollection;

#[CoversClass(MiniwaldDefinition::class)]
class MiniwaldRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;
    use EntityRepositoryTestTrait;

    private EntityRepository $repository;
    private IdsCollection $ids;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getRepository('miniwald_explorer_miniwald');
        $this->ids = new IdsCollection();
        $this->context = Context::createDefaultContext();
    }

    public function testCreateMiniwald(): void
    {
        $this->createMiniwald($this->ids, 'mw-1', ['name' => 'Sophienhöhe']);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('mw-1')]),
            $this->context
        );

        static::assertCount(1, $result);
        /** @var MiniwaldEntity $entity */
        $entity = $result->first();
        static::assertSame('Sophienhöhe', $entity->getName());
    }

    public function testCreateRequiresName(): void
    {
        $this->expectException(\Throwable::class);

        $this->repository->create([
            [
                'id' => $this->ids->get('mw-no-name'),
                // name is missing — required field
            ],
        ], $this->context);
    }

    public function testUpdateMiniwald(): void
    {
        $this->createMiniwald($this->ids, 'mw-upd', ['name' => 'Original']);

        $this->repository->update([
            [
                'id' => $this->ids->get('mw-upd'),
                'name' => 'Updated Name',
            ],
        ], $this->context);

        $entity = $this->repository->search(
            new Criteria([$this->ids->get('mw-upd')]),
            $this->context
        )->first();

        static::assertSame('Updated Name', $entity->getName());
    }

    public function testDeleteMiniwald(): void
    {
        $this->createMiniwald($this->ids, 'mw-del');

        $this->repository->delete([
            ['id' => $this->ids->get('mw-del')],
        ], $this->context);

        $result = $this->repository->search(
            new Criteria([$this->ids->get('mw-del')]),
            $this->context
        );

        static::assertCount(0, $result);
    }

    public function testSearchByName(): void
    {
        $this->createMiniwald($this->ids, 'mw-a', ['name' => 'Sophienhöhe']);
        $this->createMiniwald($this->ids, 'mw-b', ['name' => 'Garzweiler']);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'Sophienhöhe'));

        $result = $this->repository->search($criteria, $this->context);

        static::assertCount(1, $result);
        static::assertSame('Sophienhöhe', $result->first()->getName());
    }

}
