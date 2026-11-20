<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Adds light_conditions field to ground_measurement table.
 * Enum values: sunny, shady
 */
class Migration1776870000AddLightConditionsToGroundMeasurement extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1776870000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            ADD COLUMN `light_conditions` VARCHAR(20) NULL
            AFTER `ph_value`
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
