<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Refactor plant_measurement from OneToOne to OneToMany relationship.
 *
 * Drops the UNIQUE constraint on observation_id so that multiple
 * PlantMeasurement records can exist per Observation.
 */
class Migration1777791600RefactorPlantMeasurementToOneToMany extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1777791600;
    }

    public function update(Connection $connection): void
    {
        // Only act if the unique index still exists (idempotent).
        $uniqueIndexExists = (int) $connection->fetchOne('
            SELECT COUNT(*)
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = \'miniwald_explorer_plant_measurement\'
              AND INDEX_NAME = \'uniq.miniwald_explorer_plant_measurement.observation_id\'
        ');

        if ($uniqueIndexExists === 0) {
            // Already migrated — nothing to do.
            return;
        }

        // MySQL requires dropping the FK constraint before dropping the unique index
        // that backs it. After dropping both, re-add the FK backed by a regular index.
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_plant_measurement`
            DROP FOREIGN KEY `fk.miniwald_explorer_plant_measurement.observation_id`
        ');

        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_plant_measurement`
            DROP INDEX `uniq.miniwald_explorer_plant_measurement.observation_id`
        ');

        // Re-add the FK. MySQL automatically creates a regular (non-unique) index for it.
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_plant_measurement`
            ADD CONSTRAINT `fk.miniwald_explorer_plant_measurement.observation_id`
                FOREIGN KEY (`observation_id`) REFERENCES `miniwald_explorer_observation` (`id`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
