<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Refactors ground_measurement from OneToOne to OneToMany relationship.
 *
 * Changes:
 * - Drops the UNIQUE constraint on observation_id (was enforcing OneToOne)
 * - Adds location_type column (wood_center, wood_edge, street_curb)
 * - Adds new UNIQUE constraint on (observation_id, location_type) — one of each type per observation
 * - Backfills existing rows with location_type = 'wood_center'
 */
class Migration1779200000RefactorGroundMeasurementToOneToMany extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779116400;
    }

    public function update(Connection $connection): void
    {
        // Add location_type column (nullable first so existing rows don't fail)
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            ADD COLUMN `location_type` VARCHAR(30) NULL
            AFTER `observation_id`
        ');

        // Backfill existing rows with default location type
        $connection->executeStatement('
            UPDATE `miniwald_explorer_ground_measurement`
            SET `location_type` = \'wood_center\'
            WHERE `location_type` IS NULL
        ');

        // Now make it NOT NULL
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            MODIFY COLUMN `location_type` VARCHAR(30) NOT NULL DEFAULT \'wood_center\'
        ');

        // MySQL won't drop a UNIQUE index that backs a FK constraint.
        // Drop the FK first, then drop the UNIQUE index, then re-add the FK
        // (the new composite UNIQUE key will serve as the backing index for the FK).
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            DROP FOREIGN KEY `fk.miniwald_explorer_ground_measurement.observation_id`
        ');

        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            DROP INDEX `uniq.miniwald_explorer_ground_measurement.observation_id`
        ');

        // Add new unique constraint: one of each location_type per observation.
        // This also serves as the backing index for the FK we re-add below.
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            ADD UNIQUE KEY `uniq.miniwald_explorer_ground_measurement.obs_location`
                (`observation_id`, `location_type`)
        ');

        // Re-add the FK constraint (now backed by the composite unique key above)
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_ground_measurement`
            ADD CONSTRAINT `fk.miniwald_explorer_ground_measurement.observation_id`
                FOREIGN KEY (`observation_id`) REFERENCES `miniwald_explorer_observation` (`id`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
