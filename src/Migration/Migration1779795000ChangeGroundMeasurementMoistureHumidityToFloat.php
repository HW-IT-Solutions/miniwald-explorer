<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Changes moisture and humidity columns from INT to FLOAT
 * to allow precise decimal values (e.g. 67.5% humidity).
 *
 * The PDF observation sheet records these as real measurements
 * from a hygrometer/soil sensor, so integer precision is insufficient.
 */
class Migration1779795000ChangeGroundMeasurementMoistureHumidityToFloat extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779795000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            MODIFY COLUMN `moisture` FLOAT NULL,
            MODIFY COLUMN `humidity` FLOAT NULL
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
