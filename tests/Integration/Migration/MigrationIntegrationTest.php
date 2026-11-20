<?php declare(strict_types=1);

namespace InReNa\Tests\Integration\Migration;

use Doctrine\DBAL\Connection;
use InReNa\Migration\Migration1776765600CreateMiniwaldExplorerEntities;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * Verifies the migration created all tables with correct schema in the real database.
 */
#[CoversClass(Migration1776765600CreateMiniwaldExplorerEntities::class)]
class MigrationIntegrationTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
    }

    public static function tableProvider(): array
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

    #[DataProvider('tableProvider')]
    public function testTableExists(string $tableName): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = array_map(
            fn ($table) => $table->getName(),
            $schemaManager->listTables()
        );

        static::assertContains($tableName, $tables, "Table '{$tableName}' should exist in the database");
    }

    public function testSpeciesCategoryTableSchema(): void
    {
        $columns = $this->getColumnNames('miniwald_explorer_species_category');

        static::assertContains('id', $columns);
        static::assertContains('image_id', $columns);
        static::assertContains('position', $columns);
        static::assertContains('active', $columns);
        static::assertContains('created_at', $columns);
        static::assertContains('updated_at', $columns);
    }

    public function testObservationTableSchema(): void
    {
        $columns = $this->getColumnNames('miniwald_explorer_observation');

        static::assertContains('id', $columns);
        static::assertContains('customer_id', $columns);
        static::assertContains('miniwald_id', $columns);
        static::assertContains('observed_at', $columns);
        static::assertContains('latitude', $columns);
        static::assertContains('longitude', $columns);
        static::assertContains('weather', $columns);
        static::assertContains('wind', $columns);
        static::assertContains('remarks', $columns);
        static::assertContains('status', $columns);
        static::assertContains('submitted_at', $columns);
    }

    public static function foreignKeyProvider(): array
    {
        return [
            'species → species_category' => [
                'miniwald_explorer_species',
                'fk.miniwald_explorer_species.species_category_id',
            ],
            'subspecies → species' => [
                'miniwald_explorer_subspecies',
                'fk.miniwald_explorer_subspecies.species_id',
            ],
            'observation_item → observation' => [
                'miniwald_explorer_observation_item',
                'fk.miniwald_explorer_observation_item.observation_id',
            ],
            'ground_measurement → observation' => [
                'miniwald_explorer_ground_measurement',
                'fk.miniwald_explorer_ground_measurement.observation_id',
            ],
            'plant_measurement → observation' => [
                'miniwald_explorer_plant_measurement',
                'fk.miniwald_explorer_plant_measurement.observation_id',
            ],
            'photo → observation_item' => [
                'miniwald_explorer_photo',
                'fk.miniwald_explorer_photo.observation_item_id',
            ],
            'photo → media' => [
                'miniwald_explorer_photo',
                'fk.miniwald_explorer_photo.media_id',
            ],
        ];
    }

    #[DataProvider('foreignKeyProvider')]
    public function testForeignKeysExist(string $tableName, string $constraintName): void
    {
        $foreignKeys = $this->connection->createSchemaManager()->listTableForeignKeys($tableName);
        $fkNames = array_map(fn ($fk) => $fk->getName(), $foreignKeys);

        static::assertContains(
            $constraintName,
            $fkNames,
            "Foreign key '{$constraintName}' should exist on table '{$tableName}'"
        );
    }

    public function testUniqueConstraintOnGroundMeasurementObservationId(): void
    {
        $indexes = $this->connection->createSchemaManager()->listTableIndexes('miniwald_explorer_ground_measurement');
        $uniqueIndexes = array_filter($indexes, fn ($index) => $index->isUnique() && !$index->isPrimary());

        $uniqueColumns = [];
        foreach ($uniqueIndexes as $index) {
            $uniqueColumns = array_merge($uniqueColumns, $index->getColumns());
        }

        static::assertContains('observation_id', $uniqueColumns, 'observation_id should have a unique constraint on ground_measurement');
    }

    public function testUniqueConstraintOnPlantMeasurementObservationId(): void
    {
        $indexes = $this->connection->createSchemaManager()->listTableIndexes('miniwald_explorer_plant_measurement');
        $uniqueIndexes = array_filter($indexes, fn ($index) => $index->isUnique() && !$index->isPrimary());

        $uniqueColumns = [];
        foreach ($uniqueIndexes as $index) {
            $uniqueColumns = array_merge($uniqueColumns, $index->getColumns());
        }

        static::assertNotContains('observation_id', $uniqueColumns, 'observation_id must NOT have a unique constraint on plant_measurement (1:N refactor removed it)');
    }

    /**
     * @return string[]
     */
    private function getColumnNames(string $tableName): array
    {
        $columns = $this->connection->createSchemaManager()->listTableColumns($tableName);

        return array_map(fn ($column) => $column->getName(), $columns);
    }
}
