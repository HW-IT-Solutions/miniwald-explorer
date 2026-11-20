<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use InReNa\Migration\Migration1776765600CreateMiniwaldExplorerEntities;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Migration\MigrationStep;

#[CoversClass(Migration1776765600CreateMiniwaldExplorerEntities::class)]
class MigrationTest extends TestCase
{
    private Migration1776765600CreateMiniwaldExplorerEntities $migration;

    protected function setUp(): void
    {
        $this->migration = new Migration1776765600CreateMiniwaldExplorerEntities();
    }

    public function testExtendsMigrationStep(): void
    {
        static::assertInstanceOf(MigrationStep::class, $this->migration);
    }

    public function testGetCreationTimestamp(): void
    {
        static::assertSame(1776765600, $this->migration->getCreationTimestamp());
    }

    public function testGetCreationTimestampIsPositiveInteger(): void
    {
        static::assertGreaterThan(0, $this->migration->getCreationTimestamp());
    }

    public function testUpdateExecutesTenStatements(): void
    {
        $connection = $this->createMock(Connection::class);

        // 10 tables: species_category + translation, species + translation,
        // subspecies + translation, miniwald, plant + translation,
        // observation, observation_item, ground_measurement, plant_measurement, photo
        // = 14 CREATE TABLE statements total
        $connection->expects(static::exactly(14))
            ->method('executeStatement');

        $this->migration->update($connection);
    }

    public function testUpdateDestructiveDoesNothing(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection->expects(static::never())
            ->method('executeStatement');

        $this->migration->updateDestructive($connection);
    }

    public function testUpdateCreatesTablesInCorrectOrder(): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        static::assertCount(14, $executedStatements);

        // Verify table creation order (parent tables before child tables)
        $tableOrder = [];
        foreach ($executedStatements as $sql) {
            if (preg_match('/CREATE TABLE IF NOT EXISTS `([^`]+)`/', $sql, $matches)) {
                $tableOrder[] = $matches[1];
            }
        }

        static::assertSame([
            'miniwald_explorer_species_category',
            'miniwald_explorer_species_category_translation',
            'miniwald_explorer_species',
            'miniwald_explorer_species_translation',
            'miniwald_explorer_subspecies',
            'miniwald_explorer_subspecies_translation',
            'miniwald_explorer_miniwald',
            'miniwald_explorer_plant',
            'miniwald_explorer_plant_translation',
            'miniwald_explorer_observation',
            'miniwald_explorer_observation_item',
            'miniwald_explorer_ground_measurement',
            'miniwald_explorer_plant_measurement',
            'miniwald_explorer_photo',
        ], $tableOrder);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function tableNameProvider(): array
    {
        return [
            'species_category' => ['miniwald_explorer_species_category'],
            'species_category_translation' => ['miniwald_explorer_species_category_translation'],
            'species' => ['miniwald_explorer_species'],
            'species_translation' => ['miniwald_explorer_species_translation'],
            'subspecies' => ['miniwald_explorer_subspecies'],
            'subspecies_translation' => ['miniwald_explorer_subspecies_translation'],
            'miniwald' => ['miniwald_explorer_miniwald'],
            'plant' => ['miniwald_explorer_plant'],
            'plant_translation' => ['miniwald_explorer_plant_translation'],
            'observation' => ['miniwald_explorer_observation'],
            'observation_item' => ['miniwald_explorer_observation_item'],
            'ground_measurement' => ['miniwald_explorer_ground_measurement'],
            'plant_measurement' => ['miniwald_explorer_plant_measurement'],
            'photo' => ['miniwald_explorer_photo'],
        ];
    }

    #[DataProvider('tableNameProvider')]
    public function testUpdateCreatesTable(string $tableName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        $found = false;
        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                $found = true;
                break;
            }
        }

        static::assertTrue($found, "Table '{$tableName}' was not created in migration");
    }

    #[DataProvider('tableNameProvider')]
    public function testAllTablesUseIfNotExists(string $tableName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, $tableName)) {
                static::assertStringContainsString('CREATE TABLE IF NOT EXISTS', $sql);
            }
        }
    }

    #[DataProvider('tableNameProvider')]
    public function testAllTablesUseInnoDb(string $tableName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                static::assertStringContainsString('ENGINE=InnoDB', $sql);
            }
        }
    }

    #[DataProvider('tableNameProvider')]
    public function testAllTablesUseUtf8mb4(string $tableName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                static::assertStringContainsString('utf8mb4', $sql);
                static::assertStringContainsString('utf8mb4_unicode_ci', $sql);
            }
        }
    }

    #[DataProvider('tableNameProvider')]
    public function testAllTablesHavePrimaryKey(string $tableName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                static::assertStringContainsString('PRIMARY KEY', $sql);
            }
        }
    }

    #[DataProvider('tableNameProvider')]
    public function testAllTablesHaveCreatedAtAndUpdatedAt(string $tableName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                static::assertStringContainsString('`created_at` DATETIME(3) NOT NULL', $sql);
                static::assertStringContainsString('`updated_at` DATETIME(3) NULL', $sql);
            }
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function foreignKeyProvider(): array
    {
        return [
            'species_category.image_id → media' => [
                'miniwald_explorer_species_category',
                'FOREIGN KEY (`image_id`) REFERENCES `media` (`id`)',
            ],
            'species.species_category_id → species_category' => [
                'miniwald_explorer_species',
                'FOREIGN KEY (`species_category_id`) REFERENCES `miniwald_explorer_species_category` (`id`)',
            ],
            'species.image_id → media' => [
                'miniwald_explorer_species',
                'FOREIGN KEY (`image_id`) REFERENCES `media` (`id`)',
            ],
            'subspecies.species_id → species' => [
                'miniwald_explorer_subspecies',
                'FOREIGN KEY (`species_id`) REFERENCES `miniwald_explorer_species` (`id`)',
            ],
            'subspecies.image_id → media' => [
                'miniwald_explorer_subspecies',
                'FOREIGN KEY (`image_id`) REFERENCES `media` (`id`)',
            ],
            'plant.image_id → media' => [
                'miniwald_explorer_plant',
                'FOREIGN KEY (`image_id`) REFERENCES `media` (`id`)',
            ],
            'observation.customer_id → customer' => [
                'miniwald_explorer_observation',
                'FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`)',
            ],
            'observation.miniwald_id → miniwald' => [
                'miniwald_explorer_observation',
                'FOREIGN KEY (`miniwald_id`) REFERENCES `miniwald_explorer_miniwald` (`id`)',
            ],
            'observation_item.observation_id → observation' => [
                'miniwald_explorer_observation_item',
                'FOREIGN KEY (`observation_id`) REFERENCES `miniwald_explorer_observation` (`id`)',
            ],
            'observation_item.species_id → species' => [
                'miniwald_explorer_observation_item',
                'FOREIGN KEY (`species_id`) REFERENCES `miniwald_explorer_species` (`id`)',
            ],
            'observation_item.subspecies_id → subspecies' => [
                'miniwald_explorer_observation_item',
                'FOREIGN KEY (`subspecies_id`) REFERENCES `miniwald_explorer_subspecies` (`id`)',
            ],
            'ground_measurement.observation_id → observation' => [
                'miniwald_explorer_ground_measurement',
                'FOREIGN KEY (`observation_id`) REFERENCES `miniwald_explorer_observation` (`id`)',
            ],
            'plant_measurement.observation_id → observation' => [
                'miniwald_explorer_plant_measurement',
                'FOREIGN KEY (`observation_id`) REFERENCES `miniwald_explorer_observation` (`id`)',
            ],
            'plant_measurement.plant_id → plant' => [
                'miniwald_explorer_plant_measurement',
                'FOREIGN KEY (`plant_id`) REFERENCES `miniwald_explorer_plant` (`id`)',
            ],
            'photo.observation_item_id → observation_item' => [
                'miniwald_explorer_photo',
                'FOREIGN KEY (`observation_item_id`) REFERENCES `miniwald_explorer_observation_item` (`id`)',
            ],
            'photo.media_id → media' => [
                'miniwald_explorer_photo',
                'FOREIGN KEY (`media_id`) REFERENCES `media` (`id`)',
            ],
        ];
    }

    #[DataProvider('foreignKeyProvider')]
    public function testForeignKeyExists(string $tableName, string $expectedForeignKey): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        $found = false;
        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                // Normalize whitespace for comparison
                $normalizedSql = preg_replace('/\s+/', ' ', $sql);
                $normalizedExpected = preg_replace('/\s+/', ' ', $expectedForeignKey);
                if (str_contains($normalizedSql, $normalizedExpected)) {
                    $found = true;
                }
                break;
            }
        }

        static::assertTrue($found, "Foreign key '{$expectedForeignKey}' not found in table '{$tableName}'");
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function cascadeDeleteProvider(): array
    {
        return [
            'species.species_category_id' => [
                'miniwald_explorer_species',
                'species_category_id',
            ],
            'subspecies.species_id' => [
                'miniwald_explorer_subspecies',
                'species_id',
            ],
            'observation_item.observation_id' => [
                'miniwald_explorer_observation_item',
                'observation_id',
            ],
            'ground_measurement.observation_id' => [
                'miniwald_explorer_ground_measurement',
                'observation_id',
            ],
            'plant_measurement.observation_id' => [
                'miniwald_explorer_plant_measurement',
                'observation_id',
            ],
            'photo.observation_item_id' => [
                'miniwald_explorer_photo',
                'observation_item_id',
            ],
            'photo.media_id' => [
                'miniwald_explorer_photo',
                'media_id',
            ],
        ];
    }

    #[DataProvider('cascadeDeleteProvider')]
    public function testCascadeDeleteOnForeignKey(string $tableName, string $columnName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                $normalizedSql = preg_replace('/\s+/', ' ', $sql);
                // Find the FK constraint for this column and verify ON DELETE CASCADE
                static::assertMatchesRegularExpression(
                    '/FOREIGN KEY \(`' . $columnName . '`\) REFERENCES .+ ON DELETE CASCADE/',
                    $normalizedSql,
                    "Column '{$columnName}' in table '{$tableName}' should have ON DELETE CASCADE"
                );
                return;
            }
        }

        static::fail("Table '{$tableName}' not found in migration statements");
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function setNullDeleteProvider(): array
    {
        return [
            'species_category.image_id' => [
                'miniwald_explorer_species_category',
                'image_id',
            ],
            'species.image_id' => [
                'miniwald_explorer_species',
                'image_id',
            ],
            'subspecies.image_id' => [
                'miniwald_explorer_subspecies',
                'image_id',
            ],
            'plant.image_id' => [
                'miniwald_explorer_plant',
                'image_id',
            ],
            'observation.customer_id' => [
                'miniwald_explorer_observation',
                'customer_id',
            ],
            'observation.miniwald_id' => [
                'miniwald_explorer_observation',
                'miniwald_id',
            ],
            'observation_item.species_id' => [
                'miniwald_explorer_observation_item',
                'species_id',
            ],
            'observation_item.subspecies_id' => [
                'miniwald_explorer_observation_item',
                'subspecies_id',
            ],
            'plant_measurement.plant_id' => [
                'miniwald_explorer_plant_measurement',
                'plant_id',
            ],
        ];
    }

    #[DataProvider('setNullDeleteProvider')]
    public function testSetNullDeleteOnForeignKey(string $tableName, string $columnName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                $normalizedSql = preg_replace('/\s+/', ' ', $sql);
                static::assertMatchesRegularExpression(
                    '/FOREIGN KEY \(`' . $columnName . '`\) REFERENCES .+ ON DELETE SET NULL/',
                    $normalizedSql,
                    "Column '{$columnName}' in table '{$tableName}' should have ON DELETE SET NULL"
                );
                return;
            }
        }

        static::fail("Table '{$tableName}' not found in migration statements");
    }

    /**
     * @return array<string, array{string}>
     */
    public static function translationTableProvider(): array
    {
        return [
            'species_category_translation' => ['miniwald_explorer_species_category_translation'],
            'species_translation' => ['miniwald_explorer_species_translation'],
            'subspecies_translation' => ['miniwald_explorer_subspecies_translation'],
            'plant_translation' => ['miniwald_explorer_plant_translation'],
        ];
    }

    #[DataProvider('translationTableProvider')]
    public function testTranslationTablesHaveLanguageIdForeignKey(string $tableName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                $normalizedSql = preg_replace('/\s+/', ' ', $sql);
                static::assertStringContainsString('`language_id` BINARY(16) NOT NULL', $normalizedSql);
                static::assertStringContainsString(
                    "FOREIGN KEY (`language_id`) REFERENCES `language` (`id`)",
                    $normalizedSql
                );
                return;
            }
        }

        static::fail("Translation table '{$tableName}' not found");
    }

    #[DataProvider('translationTableProvider')]
    public function testTranslationTablesHaveCompositeKey(string $tableName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                // Translation tables should NOT have `id` as PK, but a composite key
                static::assertStringNotContainsString('`id` BINARY(16)', $sql);
                // The PK should contain language_id
                static::assertMatchesRegularExpression(
                    '/PRIMARY KEY \(`[^`]+`, `language_id`\)/',
                    preg_replace('/\s+/', ' ', $sql),
                    "Translation table '{$tableName}' should have composite primary key with language_id"
                );
                return;
            }
        }

        static::fail("Translation table '{$tableName}' not found");
    }

    #[DataProvider('translationTableProvider')]
    public function testTranslationTablesHaveNameField(string $tableName): void
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                static::assertStringContainsString('`name` VARCHAR(255) NOT NULL', $sql);
                return;
            }
        }

        static::fail("Translation table '{$tableName}' not found");
    }

    public function testSpeciesCategoryTableHasExpectedColumns(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_species_category');

        static::assertStringContainsString('`id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`image_id` BINARY(16) NULL', $sql);
        static::assertStringContainsString('`position` INT(11) NOT NULL DEFAULT 0', $sql);
        static::assertStringContainsString('`active` TINYINT(1) NOT NULL DEFAULT 1', $sql);
    }

    public function testSpeciesTableHasExpectedColumns(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_species');

        static::assertStringContainsString('`id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`species_category_id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`image_id` BINARY(16) NULL', $sql);
        static::assertStringContainsString('`scientific_name` VARCHAR(255) NOT NULL', $sql);
        static::assertStringContainsString('`position` INT(11) NOT NULL DEFAULT 0', $sql);
        static::assertStringContainsString('`active` TINYINT(1) NOT NULL DEFAULT 1', $sql);
    }

    public function testSubspeciesTableHasExpectedColumns(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_subspecies');

        static::assertStringContainsString('`id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`species_id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`image_id` BINARY(16) NULL', $sql);
        static::assertStringContainsString('`scientific_name` VARCHAR(255) NULL', $sql);
        static::assertStringContainsString('`position` INT(11) NOT NULL DEFAULT 0', $sql);
        static::assertStringContainsString('`active` TINYINT(1) NOT NULL DEFAULT 1', $sql);
    }

    public function testMiniwaldTableHasExpectedColumns(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_miniwald');

        static::assertStringContainsString('`id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`name` VARCHAR(255) NOT NULL', $sql);
        // Miniwald is a simple table - no foreign keys
        static::assertStringNotContainsString('FOREIGN KEY', $sql);
    }

    public function testPlantTableHasExpectedColumns(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_plant');

        static::assertStringContainsString('`id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`image_id` BINARY(16) NULL', $sql);
        static::assertStringContainsString('`scientific_name` VARCHAR(255) NULL', $sql);
        static::assertStringContainsString('`position` VARCHAR(50) NULL', $sql);
        static::assertStringContainsString('`sort_order` INT(11) NOT NULL DEFAULT 0', $sql);
        static::assertStringContainsString('`active` TINYINT(1) NOT NULL DEFAULT 1', $sql);
        static::assertStringNotContainsString('miniwald_id', $sql);
        static::assertStringNotContainsString('species_id', $sql);
    }

    public function testObservationTableHasExpectedColumns(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_observation');

        static::assertStringContainsString('`id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`customer_id` BINARY(16) NULL', $sql);
        static::assertStringContainsString('`miniwald_id` BINARY(16) NULL', $sql);
        static::assertStringContainsString('`observed_at` DATETIME(3) NOT NULL', $sql);
        static::assertStringContainsString('`latitude` DOUBLE NULL', $sql);
        static::assertStringContainsString('`longitude` DOUBLE NULL', $sql);
        static::assertStringContainsString('`weather` VARCHAR(50) NULL', $sql);
        static::assertStringContainsString('`wind` VARCHAR(50) NULL', $sql);
        static::assertStringContainsString('`remarks` LONGTEXT NULL', $sql);
        static::assertStringContainsString("`status` VARCHAR(50) NOT NULL DEFAULT 'draft'", $sql);
        static::assertStringContainsString('`submitted_at` DATETIME(3) NULL', $sql);
    }

    public function testObservationTableHasIndexes(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_observation');

        static::assertStringContainsString('`idx.miniwald_explorer_observation.status`', $sql);
        static::assertStringContainsString('`idx.miniwald_explorer_observation.observed_at`', $sql);
    }

    public function testObservationItemTableHasExpectedColumns(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_observation_item');

        static::assertStringContainsString('`id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`observation_id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`species_id` BINARY(16) NULL', $sql);
        static::assertStringContainsString('`subspecies_id` BINARY(16) NULL', $sql);
        static::assertStringContainsString('`custom_species_name` VARCHAR(255) NULL', $sql);
        static::assertStringContainsString('`count` INT(11) NOT NULL DEFAULT 0', $sql);
        static::assertStringContainsString('`remarks` LONGTEXT NULL', $sql);
    }

    public function testGroundMeasurementTableHasExpectedColumns(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_ground_measurement');

        static::assertStringContainsString('`id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`observation_id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`air_temperature` DOUBLE NULL', $sql);
        static::assertStringContainsString('`ground_temperature` DOUBLE NULL', $sql);
        static::assertStringContainsString('`moisture` VARCHAR(20) NULL', $sql);
        static::assertStringContainsString('`humidity` VARCHAR(20) NULL', $sql);
        static::assertStringContainsString('`ph_value` INT(11) NULL', $sql);
        static::assertStringContainsString('`remarks` LONGTEXT NULL', $sql);
    }

    public function testGroundMeasurementTableHasUniqueObservationId(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_ground_measurement');

        static::assertStringContainsString(
            'UNIQUE KEY `uniq.miniwald_explorer_ground_measurement.observation_id`',
            $sql
        );
    }

    public function testPlantMeasurementTableHasExpectedColumns(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_plant_measurement');

        static::assertStringContainsString('`id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`observation_id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`plant_id` BINARY(16) NULL', $sql);
        static::assertStringContainsString('`height_cm` INT(11) NULL', $sql);
        static::assertStringContainsString('`trunk_circumference_cm` DOUBLE NULL', $sql);
        static::assertStringContainsString('`crown_diameter_cm` INT(11) NULL', $sql);
        static::assertStringContainsString('`remarks` LONGTEXT NULL', $sql);
    }

    public function testPlantMeasurementTableHasUniqueObservationId(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_plant_measurement');

        static::assertStringContainsString(
            'UNIQUE KEY `uniq.miniwald_explorer_plant_measurement.observation_id`',
            $sql
        );
    }

    public function testPhotoTableHasExpectedColumns(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_photo');

        static::assertStringContainsString('`id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`observation_item_id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`media_id` BINARY(16) NOT NULL', $sql);
        static::assertStringContainsString('`caption` VARCHAR(500) NULL', $sql);
        static::assertStringContainsString('`sort_order` INT(11) NOT NULL DEFAULT 0', $sql);
        static::assertStringContainsString('`is_approved` TINYINT(1) NULL', $sql);
        static::assertStringContainsString('`approved_at` DATETIME(3) NULL', $sql);
        static::assertStringContainsString('`approved_by` VARCHAR(255) NULL', $sql);
        static::assertStringContainsString('`rejection_reason` LONGTEXT NULL', $sql);
    }

    public function testPhotoTableHasApprovalIndex(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_photo');

        static::assertStringContainsString('`idx.miniwald_explorer_photo.is_approved`', $sql);
    }

    public function testSpeciesTranslationHasIdentificationTips(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_species_translation');

        static::assertStringContainsString('`identification_tips` LONGTEXT NULL', $sql);
    }

    public function testSubspeciesTranslationHasIdentificationTips(): void
    {
        $sql = $this->getCreateTableSql('miniwald_explorer_subspecies_translation');

        static::assertStringContainsString('`identification_tips` LONGTEXT NULL', $sql);
    }

    /**
     * Helper to capture SQL and return the CREATE TABLE statement for a specific table.
     */
    private function getCreateTableSql(string $tableName): string
    {
        $executedStatements = [];
        $connection = $this->createStub(Connection::class);

        $connection->method('executeStatement')
            ->willReturnCallback(function (string $sql) use (&$executedStatements): int {
                $executedStatements[] = $sql;
                return 0;
            });

        $this->migration->update($connection);

        foreach ($executedStatements as $sql) {
            if (str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tableName}`")) {
                return $sql;
            }
        }

        static::fail("Table '{$tableName}' not found in migration statements");
    }
}