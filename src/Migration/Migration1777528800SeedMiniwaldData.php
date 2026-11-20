<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1777528800SeedMiniwaldData extends MigrationStep
{
    private const NAMES = [
        'Aldenhoven Siersdorf',
        'Eschweiler Dürwiss',
        'Inden',
        'Jülich',
        'Langerwehe',
        'Linnich',
        'Niederzier',
    ];

    public function getCreationTimestamp(): int
    {
        return 1777528800;
    }

    public function update(Connection $connection): void
    {
        $now = (new \DateTime())->format('Y-m-d H:i:s.v');

        foreach (self::NAMES as $name) {
            $exists = $connection->fetchOne(
                'SELECT 1 FROM `miniwald_explorer_miniwald` WHERE `name` = ? LIMIT 1',
                [$name]
            );

            if ($exists !== false) {
                continue;
            }

            $connection->insert('miniwald_explorer_miniwald', [
                'id' => Uuid::randomBytes(),
                'name' => $name,
                'latitude' => null,
                'longitude' => null,
                'radius_meters' => 500,
                'created_at' => $now,
            ]);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing to destroy
    }
}
