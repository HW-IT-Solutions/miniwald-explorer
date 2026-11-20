<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Migration to create the observation_sheet table for PDF management.
 *
 * Each row stores one of the 6 observation sheet types with a reference
 * to the uploaded PDF in the media table.
 */
class Migration1776852000CreateObservationSheetTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1776852000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_observation_sheet` (
                `id` BINARY(16) NOT NULL,
                `type` VARCHAR(50) NOT NULL,
                `media_id` BINARY(16) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.miniwald_explorer_observation_sheet.type` (`type`),
                KEY `fk.miniwald_explorer_observation_sheet.media_id` (`media_id`),
                CONSTRAINT `fk.miniwald_explorer_observation_sheet.media_id`
                    FOREIGN KEY (`media_id`) REFERENCES `media` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
