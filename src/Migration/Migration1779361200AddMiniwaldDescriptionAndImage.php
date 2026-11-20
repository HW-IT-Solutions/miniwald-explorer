<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1779361200AddMiniwaldDescriptionAndImage extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779300000;
    }

    public function update(Connection $connection): void
    {
        // Add image_id and external_url columns to miniwald table
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_miniwald`
                ADD COLUMN IF NOT EXISTS `image_id` BINARY(16) NULL AFTER `radius_meters`,
                ADD COLUMN IF NOT EXISTS `external_url` VARCHAR(500) NULL AFTER `image_id`
        ');

        // Add FK constraint for image_id (only if not already exists)
        $this->addFkIfNotExists(
            $connection,
            'miniwald_explorer_miniwald',
            'fk.miniwald_explorer_miniwald.image_id',
            'image_id',
            'media',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Create translation table
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_miniwald_translation` (
                `miniwald_explorer_miniwald_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `description` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`miniwald_explorer_miniwald_id`, `language_id`),
                KEY `fk.miniwald_explorer_miniwald_translation.language_id` (`language_id`),
                CONSTRAINT `fk.miniwald_explorer_miniwald_translation.miniwald_id`
                    FOREIGN KEY (`miniwald_explorer_miniwald_id`) REFERENCES `miniwald_explorer_miniwald` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_miniwald_translation.language_id`
                    FOREIGN KEY (`language_id`) REFERENCES `language` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing destructive
    }

    private function addFkIfNotExists(
        Connection $connection,
        string $table,
        string $constraintName,
        string $column,
        string $refTable,
        string $refColumn,
        string $onDelete,
        string $onUpdate
    ): void {
        $exists = $connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [$table, $constraintName]
        );

        if ((int) $exists === 0) {
            $connection->executeStatement(sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s ON UPDATE %s',
                $table,
                $constraintName,
                $column,
                $refTable,
                $refColumn,
                $onDelete,
                $onUpdate
            ));
        }
    }
}
