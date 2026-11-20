<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Media\MediaType\ImageType;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Backfill the `media_type` column for image media records that were inserted
 * by Migration1777289400SeedSpeciesData without setting it.
 *
 * Shopware's thumbnail generator checks `media_type` to determine whether a
 * media entity is an image. When it is NULL the media is silently skipped.
 */
class Migration1778223600BackfillMediaType extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1778223600;
    }

    public function update(Connection $connection): void
    {
        $serializedImageType = serialize(new ImageType());

        $connection->executeStatement(
            '
                UPDATE media
                SET media_type = :mediaType
                WHERE
                    media_type IS NULL
                    AND mime_type LIKE :mimePattern
            ',
            [
                'mediaType' => $serializedImageType,
                'mimePattern' => 'image/%',
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing to destroy
    }
}
