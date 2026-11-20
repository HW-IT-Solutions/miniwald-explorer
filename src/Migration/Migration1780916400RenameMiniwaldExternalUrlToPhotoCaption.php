<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1780916400RenameMiniwaldExternalUrlToPhotoCaption extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1780916400;
    }

    public function update(Connection $connection): void
    {
        $columnExists = $connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = \'miniwald_explorer_miniwald\'
               AND COLUMN_NAME = \'external_url\''
        );

        if ((int) $columnExists > 0) {
            $connection->executeStatement('
                ALTER TABLE `miniwald_explorer_miniwald`
                CHANGE COLUMN `external_url` `photo_caption` VARCHAR(500) NULL
            ');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing destructive
    }
}
