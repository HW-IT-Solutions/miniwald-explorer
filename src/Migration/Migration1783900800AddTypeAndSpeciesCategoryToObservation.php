<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Add explicit type and species_category_id to observation.
 *
 * Previously the observation type (animal/plant/ground) was derived from
 * which child records exist, and the species category only via
 * item -> species -> species_category. Observations whose items contain
 * only custom species names (species_id NULL) could not be categorized.
 * Both values are now persisted at submission time.
 *
 * Backfill: type is derived from existing child records (same precedence
 * as the old runtime detection). species_category_id is taken from any
 * item's species; observations with only custom entries stay NULL — that
 * information was never recorded and cannot be reconstructed.
 */
class Migration1783900800AddTypeAndSpeciesCategoryToObservation extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1783900800;
    }

    public function update(Connection $connection): void
    {
        $columnExists = $connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = \'miniwald_explorer_observation\'
               AND COLUMN_NAME = \'type\''
        );

        if ((int) $columnExists > 0) {
            return;
        }

        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_observation`
            ADD COLUMN `type` VARCHAR(20) NULL AFTER `miniwald_id`,
            ADD COLUMN `species_category_id` BINARY(16) NULL AFTER `type`,
            ADD KEY `idx.miniwald_explorer_observation.type` (`type`),
            ADD CONSTRAINT `fk.miniwald_explorer_observation.species_category_id`
                FOREIGN KEY (`species_category_id`) REFERENCES `miniwald_explorer_species_category` (`id`)
                ON DELETE SET NULL ON UPDATE CASCADE
        ');

        // Backfill type with the same precedence the runtime detection used:
        // items -> animal, plant measurements -> plant, ground measurements -> ground
        $connection->executeStatement('
            UPDATE `miniwald_explorer_observation` o
            SET o.`type` = \'animal\'
            WHERE o.`type` IS NULL
              AND EXISTS (
                  SELECT 1 FROM `miniwald_explorer_observation_item` i
                  WHERE i.`observation_id` = o.`id`
              )
        ');

        $connection->executeStatement('
            UPDATE `miniwald_explorer_observation` o
            SET o.`type` = \'plant\'
            WHERE o.`type` IS NULL
              AND EXISTS (
                  SELECT 1 FROM `miniwald_explorer_plant_measurement` pm
                  WHERE pm.`observation_id` = o.`id`
              )
        ');

        $connection->executeStatement('
            UPDATE `miniwald_explorer_observation` o
            SET o.`type` = \'ground\'
            WHERE o.`type` IS NULL
              AND EXISTS (
                  SELECT 1 FROM `miniwald_explorer_ground_measurement` gm
                  WHERE gm.`observation_id` = o.`id`
              )
        ');

        // Backfill species_category_id from any item's cataloged species.
        // The observation wizard is one-category-per-session, so items of one
        // observation never mix categories.
        $connection->executeStatement('
            UPDATE `miniwald_explorer_observation` o
            SET o.`species_category_id` = (
                SELECT s.`species_category_id`
                FROM `miniwald_explorer_observation_item` i
                INNER JOIN `miniwald_explorer_species` s ON s.`id` = i.`species_id`
                WHERE i.`observation_id` = o.`id`
                  AND s.`species_category_id` IS NOT NULL
                LIMIT 1
            )
            WHERE o.`type` = \'animal\'
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing destructive
    }
}
