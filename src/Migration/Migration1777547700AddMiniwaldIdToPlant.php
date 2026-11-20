<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Adds miniwald_id foreign key to miniwald_explorer_plant table.
 * A plant can optionally belong to a specific Miniwald.
 */
class Migration1777547700AddMiniwaldIdToPlant extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1777547700;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_plant`
                ADD COLUMN `miniwald_id` BINARY(16) NULL AFTER `id`,
                ADD KEY `fk.miniwald_explorer_plant.miniwald_id` (`miniwald_id`),
                ADD CONSTRAINT `fk.miniwald_explorer_plant.miniwald_id`
                    FOREIGN KEY (`miniwald_id`) REFERENCES `miniwald_explorer_miniwald` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
