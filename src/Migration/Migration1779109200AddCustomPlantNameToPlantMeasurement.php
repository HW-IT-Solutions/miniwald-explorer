<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Adds custom_plant_name column to miniwald_explorer_plant_measurement.
 * Allows recording a free-text plant name when the plant is not in the catalog.
 */
class Migration1779109200AddCustomPlantNameToPlantMeasurement extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779109200;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_plant_measurement`
            ADD COLUMN `custom_plant_name` VARCHAR(255) NULL AFTER `plant_id`
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing to do
    }
}
