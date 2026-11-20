<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1779780600MakeSpeciesScientificNameNullable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1779780600;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `miniwald_explorer_species`
                MODIFY COLUMN `scientific_name` VARCHAR(255) NULL
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
