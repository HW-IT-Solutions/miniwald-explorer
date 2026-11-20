<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1779361201CreateMiniForestsMediaFolder extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779361201;
    }

    public function update(Connection $connection): void
    {
        // Check if "Mini Forests" folder already exists under MiniwaldExplorer
        $exists = $connection->fetchOne(
            'SELECT 1 FROM `media_folder` WHERE `name` = ? LIMIT 1',
            ['Mini Forests']
        );

        if ($exists !== false) {
            return;
        }

        // Find the MiniwaldExplorer root folder
        $rootFolderId = $connection->fetchOne(
            'SELECT `id` FROM `media_folder` WHERE `name` = ? AND `parent_id` IS NULL LIMIT 1',
            ['MiniwaldExplorer']
        );

        if ($rootFolderId === false) {
            // Root folder not found, skip
            return;
        }

        // Find an existing image config to reuse (thumbnails enabled)
        $imageConfigId = $connection->fetchOne(
            'SELECT `media_folder_configuration_id` FROM `media_folder` WHERE `name` = ? LIMIT 1',
            ['Species']
        );

        if ($imageConfigId === false) {
            // Create a new config
            $imageConfigId = Uuid::randomBytes();
            $connection->insert('media_folder_configuration', [
                'id' => $imageConfigId,
                'create_thumbnails' => 1,
                'thumbnail_quality' => 80,
                'private' => 0,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
        }

        // Create "Mini Forests" folder under MiniwaldExplorer
        $connection->insert('media_folder', [
            'id' => Uuid::randomBytes(),
            'parent_id' => $rootFolderId,
            'name' => 'Mini Forests',
            'media_folder_configuration_id' => $imageConfigId,
            'use_parent_configuration' => 0,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing to destroy
    }
}
