<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Adds taxon_id VARCHAR(255) NULL to miniwald_explorer_species and miniwald_explorer_subspecies.
 * Used for matching records with external systems (e.g. NABU naturgucker, GBIF).
 */
class Migration1777549500AddTaxonIdToSpeciesAndSubspecies extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1777549500;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_species`
                ADD COLUMN `taxon_id` VARCHAR(255) NULL AFTER `scientific_name`
        ');

        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_subspecies`
                ADD COLUMN `taxon_id` VARCHAR(255) NULL AFTER `scientific_name`
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
