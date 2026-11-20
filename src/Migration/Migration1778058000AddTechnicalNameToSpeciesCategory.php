<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Adds a CSS-safe technical_name column to species_category.
 * Seeds known slugs for the 4 existing categories.
 */
class Migration1778058000AddTechnicalNameToSpeciesCategory extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1778058000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_species_category`
            ADD COLUMN `technical_name` VARCHAR(50) NULL AFTER `active`
        ');

        // Seed known slugs based on the German translation name
        $connection->executeStatement('
            UPDATE `miniwald_explorer_species_category` sc
            JOIN `miniwald_explorer_species_category_translation` sct
                ON sct.`miniwald_explorer_species_category_id` = sc.`id`
            SET sc.`technical_name` = CASE sct.`name`
                WHEN \'Vögel\'                           THEN \'birds\'
                WHEN \'Schmetterlinge\'                  THEN \'butterflies\'
                WHEN \'Insekten, Schnecken und Spinnen\' THEN \'insects\'
                WHEN \'Bodenlebewesen\'                  THEN \'soil\'
            END
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
