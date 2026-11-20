<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1777356000AddMapIndexes extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1777356000;
    }

    public function update(Connection $connection): void
    {
        // Composite index for the map query WHERE clause:
        // status, latitude IS NOT NULL, longitude IS NOT NULL, observed_at (ORDER BY), customer_id, miniwald_id
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_observation`
            ADD INDEX IF NOT EXISTS `idx_map_query`
            (`status`, `latitude`, `longitude`, `observed_at`, `customer_id`, `miniwald_id`)
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
