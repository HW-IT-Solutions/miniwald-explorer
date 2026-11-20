<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1776860000CreateMediaFolders extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1776860000;
    }

    public function update(Connection $connection): void
    {
        // Create folder configuration for images (with thumbnails)
        $imageConfigId = Uuid::randomBytes();
        $connection->insert('media_folder_configuration', [
            'id' => $imageConfigId,
            'create_thumbnails' => 1,
            'thumbnail_quality' => 80,
            'private' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Create folder configuration for observation images (higher thumbnail quality)
        $observationConfigId = Uuid::randomBytes();
        $connection->insert('media_folder_configuration', [
            'id' => $observationConfigId,
            'create_thumbnails' => 1,
            'thumbnail_quality' => 85,
            'private' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Create folder configuration for PDFs (no thumbnails)
        $pdfConfigId = Uuid::randomBytes();
        $connection->insert('media_folder_configuration', [
            'id' => $pdfConfigId,
            'create_thumbnails' => 0,
            'private' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Create root folder: MiniwaldExplorer
        $rootFolderId = Uuid::randomBytes();
        $connection->insert('media_folder', [
            'id' => $rootFolderId,
            'name' => 'MiniwaldExplorer',
            'media_folder_configuration_id' => $imageConfigId,
            'use_parent_configuration' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Create Species folder
        $speciesFolderId = Uuid::randomBytes();
        $connection->insert('media_folder', [
            'id' => $speciesFolderId,
            'parent_id' => $rootFolderId,
            'name' => 'Species',
            'media_folder_configuration_id' => $imageConfigId,
            'use_parent_configuration' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Create Species/Categories folder
        $categoriesFolderId = Uuid::randomBytes();
        $connection->insert('media_folder', [
            'id' => $categoriesFolderId,
            'parent_id' => $speciesFolderId,
            'name' => 'Categories',
            'media_folder_configuration_id' => $imageConfigId,
            'use_parent_configuration' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Create Species/Species folder
        $speciesSpeciesFolderId = Uuid::randomBytes();
        $connection->insert('media_folder', [
            'id' => $speciesSpeciesFolderId,
            'parent_id' => $speciesFolderId,
            'name' => 'Species',
            'media_folder_configuration_id' => $imageConfigId,
            'use_parent_configuration' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Create Species/Subspecies folder
        $subspeciesFolderId = Uuid::randomBytes();
        $connection->insert('media_folder', [
            'id' => $subspeciesFolderId,
            'parent_id' => $speciesFolderId,
            'name' => 'Subspecies',
            'media_folder_configuration_id' => $imageConfigId,
            'use_parent_configuration' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Create Plants folder
        $plantsFolderId = Uuid::randomBytes();
        $connection->insert('media_folder', [
            'id' => $plantsFolderId,
            'parent_id' => $rootFolderId,
            'name' => 'Plants',
            'media_folder_configuration_id' => $imageConfigId,
            'use_parent_configuration' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Create Observations folder (with higher quality config for compressed images)
        $observationsFolderId = Uuid::randomBytes();
        $connection->insert('media_folder', [
            'id' => $observationsFolderId,
            'parent_id' => $rootFolderId,
            'name' => 'Observations',
            'media_folder_configuration_id' => $observationConfigId,
            'use_parent_configuration' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        // Create Observation Sheets folder (for PDFs)
        $sheetsFolderId = Uuid::randomBytes();
        $connection->insert('media_folder', [
            'id' => $sheetsFolderId,
            'parent_id' => $rootFolderId,
            'name' => 'Observation Sheets',
            'media_folder_configuration_id' => $pdfConfigId,
            'use_parent_configuration' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Optional: Clean up folders on plugin uninstall
        // Not implemented to preserve media files
    }
}
