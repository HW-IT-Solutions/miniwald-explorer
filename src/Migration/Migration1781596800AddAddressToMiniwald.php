<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1781596800AddAddressToMiniwald extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1781596800;
    }

    public function update(Connection $connection): void
    {
        $columnExists = $connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = \'miniwald_explorer_miniwald\'
               AND COLUMN_NAME = \'address\''
        );

        if ((int) $columnExists === 0) {
            $connection->executeStatement('
                ALTER TABLE `miniwald_explorer_miniwald`
                ADD COLUMN `address` VARCHAR(500) NULL AFTER `photo_caption`
            ');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing destructive
    }
}
