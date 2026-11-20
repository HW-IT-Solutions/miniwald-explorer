<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Migration to create all MiniwaldExplorer custom entity tables.
 *
 * Tables created:
 * - miniwald_explorer_species_category (with translation table)
 * - miniwald_explorer_species (with translation table)
 * - miniwald_explorer_subspecies (with translation table)
 * - miniwald_explorer_miniwald
 * - miniwald_explorer_plant (with translation table)
 * - miniwald_explorer_observation
 * - miniwald_explorer_observation_item
 * - miniwald_explorer_ground_measurement
 * - miniwald_explorer_plant_measurement
 * - miniwald_explorer_photo
 */
class Migration1776765600CreateMiniwaldExplorerEntities extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1776765600;
    }

    public function update(Connection $connection): void
    {
        $this->createSpeciesCategoryTable($connection);
        $this->createSpeciesTable($connection);
        $this->createSubspeciesTable($connection);
        $this->createMiniwaldTable($connection);
        $this->createPlantTable($connection);
        $this->createObservationTable($connection);
        $this->createObservationItemTable($connection);
        $this->createGroundMeasurementTable($connection);
        $this->createPlantMeasurementTable($connection);
        $this->createPhotoTable($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }

    private function createSpeciesCategoryTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_species_category` (
                `id` BINARY(16) NOT NULL,
                `image_id` BINARY(16) NULL,
                `position` INT(11) NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.miniwald_explorer_species_category.image_id` (`image_id`),
                CONSTRAINT `fk.miniwald_explorer_species_category.image_id` 
                    FOREIGN KEY (`image_id`) REFERENCES `media` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_species_category_translation` (
                `miniwald_explorer_species_category_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`miniwald_explorer_species_category_id`, `language_id`),
                KEY `fk.miniwald_explorer_species_category_translation.language_id` (`language_id`),
                CONSTRAINT `fk.miniwald_explorer_species_category_translation.category_id` 
                    FOREIGN KEY (`miniwald_explorer_species_category_id`) REFERENCES `miniwald_explorer_species_category` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_species_category_translation.language_id` 
                    FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    private function createSpeciesTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_species` (
                `id` BINARY(16) NOT NULL,
                `species_category_id` BINARY(16) NOT NULL,
                `image_id` BINARY(16) NULL,
                `scientific_name` VARCHAR(255) NOT NULL,
                `position` INT(11) NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.miniwald_explorer_species.species_category_id` (`species_category_id`),
                KEY `fk.miniwald_explorer_species.image_id` (`image_id`),
                CONSTRAINT `fk.miniwald_explorer_species.species_category_id` 
                    FOREIGN KEY (`species_category_id`) REFERENCES `miniwald_explorer_species_category` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_species.image_id` 
                    FOREIGN KEY (`image_id`) REFERENCES `media` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_species_translation` (
                `miniwald_explorer_species_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` LONGTEXT NULL,
                `identification_tips` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`miniwald_explorer_species_id`, `language_id`),
                KEY `fk.miniwald_explorer_species_translation.language_id` (`language_id`),
                CONSTRAINT `fk.miniwald_explorer_species_translation.species_id` 
                    FOREIGN KEY (`miniwald_explorer_species_id`) REFERENCES `miniwald_explorer_species` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_species_translation.language_id` 
                    FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    private function createSubspeciesTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_subspecies` (
                `id` BINARY(16) NOT NULL,
                `species_id` BINARY(16) NOT NULL,
                `image_id` BINARY(16) NULL,
                `scientific_name` VARCHAR(255) NULL,
                `position` INT(11) NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.miniwald_explorer_subspecies.species_id` (`species_id`),
                KEY `fk.miniwald_explorer_subspecies.image_id` (`image_id`),
                CONSTRAINT `fk.miniwald_explorer_subspecies.species_id` 
                    FOREIGN KEY (`species_id`) REFERENCES `miniwald_explorer_species` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_subspecies.image_id` 
                    FOREIGN KEY (`image_id`) REFERENCES `media` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_subspecies_translation` (
                `miniwald_explorer_subspecies_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` LONGTEXT NULL,
                `identification_tips` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`miniwald_explorer_subspecies_id`, `language_id`),
                KEY `fk.miniwald_explorer_subspecies_translation.language_id` (`language_id`),
                CONSTRAINT `fk.miniwald_explorer_subspecies_translation.subspecies_id` 
                    FOREIGN KEY (`miniwald_explorer_subspecies_id`) REFERENCES `miniwald_explorer_subspecies` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_subspecies_translation.language_id` 
                    FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    private function createMiniwaldTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_miniwald` (
                `id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    private function createPlantTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_plant` (
                `id` BINARY(16) NOT NULL,
                `image_id` BINARY(16) NULL,
                `scientific_name` VARCHAR(255) NULL,
                `position` VARCHAR(50) NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.miniwald_explorer_plant.image_id` (`image_id`),
                CONSTRAINT `fk.miniwald_explorer_plant.image_id`
                    FOREIGN KEY (`image_id`) REFERENCES `media` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_plant_translation` (
                `miniwald_explorer_plant_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`miniwald_explorer_plant_id`, `language_id`),
                KEY `fk.miniwald_explorer_plant_translation.language_id` (`language_id`),
                CONSTRAINT `fk.miniwald_explorer_plant_translation.plant_id` 
                    FOREIGN KEY (`miniwald_explorer_plant_id`) REFERENCES `miniwald_explorer_plant` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_plant_translation.language_id` 
                    FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    private function createObservationTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_observation` (
                `id` BINARY(16) NOT NULL,
                `customer_id` BINARY(16) NULL,
                `miniwald_id` BINARY(16) NULL,
                `observed_at` DATETIME(3) NOT NULL,
                `latitude` DOUBLE NULL,
                `longitude` DOUBLE NULL,
                `weather` VARCHAR(50) NULL,
                `wind` VARCHAR(50) NULL,
                `remarks` LONGTEXT NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT \'draft\',
                `submitted_at` DATETIME(3) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.miniwald_explorer_observation.customer_id` (`customer_id`),
                KEY `fk.miniwald_explorer_observation.miniwald_id` (`miniwald_id`),
                KEY `idx.miniwald_explorer_observation.status` (`status`),
                KEY `idx.miniwald_explorer_observation.observed_at` (`observed_at`),
                CONSTRAINT `fk.miniwald_explorer_observation.customer_id` 
                    FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_observation.miniwald_id` 
                    FOREIGN KEY (`miniwald_id`) REFERENCES `miniwald_explorer_miniwald` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    private function createObservationItemTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_observation_item` (
                `id` BINARY(16) NOT NULL,
                `observation_id` BINARY(16) NOT NULL,
                `species_id` BINARY(16) NULL,
                `subspecies_id` BINARY(16) NULL,
                `custom_species_name` VARCHAR(255) NULL,
                `count` INT(11) NOT NULL DEFAULT 0,
                `remarks` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.miniwald_explorer_observation_item.observation_id` (`observation_id`),
                KEY `fk.miniwald_explorer_observation_item.species_id` (`species_id`),
                KEY `fk.miniwald_explorer_observation_item.subspecies_id` (`subspecies_id`),
                CONSTRAINT `fk.miniwald_explorer_observation_item.observation_id` 
                    FOREIGN KEY (`observation_id`) REFERENCES `miniwald_explorer_observation` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_observation_item.species_id` 
                    FOREIGN KEY (`species_id`) REFERENCES `miniwald_explorer_species` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_observation_item.subspecies_id` 
                    FOREIGN KEY (`subspecies_id`) REFERENCES `miniwald_explorer_subspecies` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    private function createGroundMeasurementTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_ground_measurement` (
                `id` BINARY(16) NOT NULL,
                `observation_id` BINARY(16) NOT NULL,
                `air_temperature` DOUBLE NULL,
                `ground_temperature` DOUBLE NULL,
                `moisture` VARCHAR(20) NULL,
                `humidity` VARCHAR(20) NULL,
                `ph_value` INT(11) NULL,
                `remarks` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.miniwald_explorer_ground_measurement.observation_id` (`observation_id`),
                CONSTRAINT `fk.miniwald_explorer_ground_measurement.observation_id` 
                    FOREIGN KEY (`observation_id`) REFERENCES `miniwald_explorer_observation` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    private function createPlantMeasurementTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_plant_measurement` (
                `id` BINARY(16) NOT NULL,
                `observation_id` BINARY(16) NOT NULL,
                `plant_id` BINARY(16) NULL,
                `height_cm` INT(11) NULL,
                `trunk_circumference_cm` DOUBLE NULL,
                `crown_diameter_cm` INT(11) NULL,
                `remarks` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.miniwald_explorer_plant_measurement.observation_id` (`observation_id`),
                KEY `fk.miniwald_explorer_plant_measurement.plant_id` (`plant_id`),
                CONSTRAINT `fk.miniwald_explorer_plant_measurement.observation_id` 
                    FOREIGN KEY (`observation_id`) REFERENCES `miniwald_explorer_observation` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_plant_measurement.plant_id` 
                    FOREIGN KEY (`plant_id`) REFERENCES `miniwald_explorer_plant` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    private function createPhotoTable(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `miniwald_explorer_photo` (
                `id` BINARY(16) NOT NULL,
                `observation_item_id` BINARY(16) NOT NULL,
                `media_id` BINARY(16) NOT NULL,
                `caption` VARCHAR(500) NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `is_approved` TINYINT(1) NULL,
                `approved_at` DATETIME(3) NULL,
                `approved_by` VARCHAR(255) NULL,
                `rejection_reason` LONGTEXT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.miniwald_explorer_photo.observation_item_id` (`observation_item_id`),
                KEY `fk.miniwald_explorer_photo.media_id` (`media_id`),
                KEY `idx.miniwald_explorer_photo.is_approved` (`is_approved`),
                CONSTRAINT `fk.miniwald_explorer_photo.observation_item_id` 
                    FOREIGN KEY (`observation_item_id`) REFERENCES `miniwald_explorer_observation_item` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.miniwald_explorer_photo.media_id` 
                    FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }
}
