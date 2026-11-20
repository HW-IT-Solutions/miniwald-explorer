<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Add species_category_id FK to observation_sheet table.
 *
 * Animal-type sheets are now linked to a species category instead of
 * using hardcoded type slugs (butterflies, birds, etc.).
 * The unique constraint changes from (type) to (type, species_category_id).
 */
class Migration1776852001AddSpeciesCategoryToObservationSheet extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1776852001;
    }

    public function update(Connection $connection): void
    {
        // Add species_category_id column
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_observation_sheet`
            ADD COLUMN `species_category_id` BINARY(16) NULL AFTER `type`
        ');

        // Drop old unique constraint on type
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_observation_sheet`
            DROP INDEX `uniq.miniwald_explorer_observation_sheet.type`
        ');

        // Add new unique constraint on (type, species_category_id)
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_observation_sheet`
            ADD UNIQUE KEY `uniq.miniwald_explorer_observation_sheet.type_category` (`type`, `species_category_id`)
        ');

        // Add foreign key
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_observation_sheet`
            ADD KEY `fk.miniwald_explorer_observation_sheet.species_category_id` (`species_category_id`),
            ADD CONSTRAINT `fk.miniwald_explorer_observation_sheet.species_category_id`
                FOREIGN KEY (`species_category_id`) REFERENCES `miniwald_explorer_species_category` (`id`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ');

        // Delete any old hardcoded animal-type rows that no longer apply
        $connection->executeStatement('
            DELETE FROM `miniwald_explorer_observation_sheet`
            WHERE `type` IN (\'butterflies\', \'birds\', \'beetles\', \'soil_organisms\')
        ');

        // Rename 'plants' type to 'plant' for consistency
        $connection->executeStatement('
            UPDATE `miniwald_explorer_observation_sheet`
            SET `type` = \'plant\'
            WHERE `type` = \'plants\'
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
