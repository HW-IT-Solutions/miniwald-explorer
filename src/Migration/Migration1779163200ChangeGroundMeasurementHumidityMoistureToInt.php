<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Changes moisture and humidity columns from VARCHAR(20) enum strings to TINYINT UNSIGNED (0–100).
 *
 * Existing enum string values are mapped to their midpoint integer values:
 *   less_25  → 12
 *   25_30    → 27
 *   30_60    → 45
 *   60_70    → 65
 *   more_70  → 80
 * Any other non-NULL value is set to NULL.
 */
class Migration1779163200ChangeGroundMeasurementHumidityMoistureToInt extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779163200;
    }

    public function update(Connection $connection): void
    {
        // Map existing enum strings to average integer values before changing column type
        $connection->executeStatement('
            UPDATE `miniwald_explorer_ground_measurement`
            SET `moisture` = CASE `moisture`
                WHEN \'less_25\' THEN 12
                WHEN \'25_30\'   THEN 27
                WHEN \'30_60\'   THEN 45
                WHEN \'60_70\'   THEN 65
                WHEN \'more_70\' THEN 80
            END
            WHERE `moisture` IS NOT NULL
        ');

        $connection->executeStatement('
            UPDATE `miniwald_explorer_ground_measurement`
            SET `humidity` = CASE `humidity`
                WHEN \'less_25\' THEN 12
                WHEN \'25_30\'   THEN 27
                WHEN \'30_60\'   THEN 45
                WHEN \'60_70\'   THEN 65
                WHEN \'more_70\' THEN 80
            END
            WHERE `humidity` IS NOT NULL
        ');

        // Change moisture from VARCHAR(20) to TINYINT UNSIGNED (0–100)
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            MODIFY COLUMN `moisture` TINYINT UNSIGNED NULL
        ');

        // Change humidity from VARCHAR(20) to TINYINT UNSIGNED (0–100)
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            MODIFY COLUMN `humidity` TINYINT UNSIGNED NULL
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
