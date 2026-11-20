<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Changes ph_value column from INT(11) to DECIMAL(3,1) to allow one decimal place (e.g. 6.5).
 *
 * Existing integer values (e.g. 7) are preserved as-is and become 7.0 automatically.
 * No data backfill required — MySQL handles the implicit cast.
 */
class Migration1779166800ChangePhValueToDecimal extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779166800;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            MODIFY COLUMN `ph_value` DECIMAL(3,1) NULL
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
