<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Migration to add GPS center point and detection radius fields to the miniwald table.
 *
 * These fields enable automatic Miniwald detection based on the observer's GPS position:
 * - latitude/longitude: center point of the Miniwald
 * - radius_meters: detection radius in meters (default 500m)
 */
class Migration1776852002AddGeoFieldsToMiniwald extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1776852002;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchAllAssociativeIndexed(
            'SHOW COLUMNS FROM `miniwald_explorer_miniwald`'
        );

        if (!isset($columns['latitude'])) {
            $connection->executeStatement('
                ALTER TABLE `miniwald_explorer_miniwald`
                    ADD COLUMN `latitude` DOUBLE NULL AFTER `name`,
                    ADD COLUMN `longitude` DOUBLE NULL AFTER `latitude`,
                    ADD COLUMN `radius_meters` INT(11) NOT NULL DEFAULT 500 AFTER `longitude`
            ');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
