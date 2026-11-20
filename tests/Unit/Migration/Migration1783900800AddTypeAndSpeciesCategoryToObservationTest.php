<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use InReNa\Migration\Migration1783900800AddTypeAndSpeciesCategoryToObservation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Migration\MigrationStep;

#[CoversClass(Migration1783900800AddTypeAndSpeciesCategoryToObservation::class)]
class Migration1783900800AddTypeAndSpeciesCategoryToObservationTest extends TestCase
{
    private Migration1783900800AddTypeAndSpeciesCategoryToObservation $migration;

    protected function setUp(): void
    {
        $this->migration = new Migration1783900800AddTypeAndSpeciesCategoryToObservation();
    }

    public function testExtendsMigrationStep(): void
    {
        static::assertInstanceOf(MigrationStep::class, $this->migration);
    }

    public function testGetCreationTimestamp(): void
    {
        static::assertSame(1783900800, $this->migration->getCreationTimestamp());
    }

    public function testUpdateIsSkippedWhenColumnExists(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn('1');
        $connection->expects(static::never())->method('executeStatement');

        $this->migration->update($connection);
    }

    public function testUpdateExecutesAlterAndBackfillStatements(): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturn('0');
        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = preg_replace('/\s+/', ' ', $sql);

                return 0;
            });

        $this->migration->update($connection);

        // 1 ALTER + 3 type backfills + 1 category backfill
        static::assertCount(5, $executedStatements);

        static::assertStringContainsString('ALTER TABLE `miniwald_explorer_observation`', $executedStatements[0]);
        static::assertStringContainsString('ADD COLUMN `type` VARCHAR(20) NULL', $executedStatements[0]);
        static::assertStringContainsString('ADD COLUMN `species_category_id` BINARY(16) NULL', $executedStatements[0]);
        static::assertStringContainsString(
            'FOREIGN KEY (`species_category_id`) REFERENCES `miniwald_explorer_species_category` (`id`) ON DELETE SET NULL',
            $executedStatements[0]
        );

        static::assertStringContainsString("SET o.`type` = 'animal'", $executedStatements[1]);
        static::assertStringContainsString('miniwald_explorer_observation_item', $executedStatements[1]);

        static::assertStringContainsString("SET o.`type` = 'plant'", $executedStatements[2]);
        static::assertStringContainsString('miniwald_explorer_plant_measurement', $executedStatements[2]);

        static::assertStringContainsString("SET o.`type` = 'ground'", $executedStatements[3]);
        static::assertStringContainsString('miniwald_explorer_ground_measurement', $executedStatements[3]);

        static::assertStringContainsString('SET o.`species_category_id` =', $executedStatements[4]);
        static::assertStringContainsString("WHERE o.`type` = 'animal'", $executedStatements[4]);
    }

    public function testTypeBackfillOnlyTouchesRowsWithoutType(): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturn('0');
        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = preg_replace('/\s+/', ' ', $sql);

                return 0;
            });

        $this->migration->update($connection);

        foreach ([1, 2, 3] as $index) {
            static::assertStringContainsString('WHERE o.`type` IS NULL', $executedStatements[$index]);
        }
    }

    public function testUpdateDestructiveDoesNothing(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::never())->method('executeStatement');

        $this->migration->updateDestructive($connection);
    }
}
