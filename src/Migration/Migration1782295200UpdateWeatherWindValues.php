<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Remaps weather and wind enum values in miniwald_explorer_observation
 * to the new 4-value sets.
 *
 * Weather mapping (6 old → 4 new):
 *   sunny        → sunny        (unchanged)
 *   partly_cloudy → partly_cloudy (unchanged, now means "leicht bewölkt")
 *   cloudy       → cloudy       (unchanged, now means "stark bewölkt")
 *   rain         → rainy        (renamed)
 *   thunderstorm → rainy        (merged)
 *   fog          → cloudy       (merged)
 *   rainy        → rainy        (storefront already used this key)
 *
 * Wind mapping (4 old → 4 new, keys renamed):
 *   calm     → calm         (unchanged)
 *   light    → light_wind   (renamed)
 *   moderate → moderate_wind (renamed)
 *   strong   → strong_wind  (renamed)
 */
class Migration1782295200UpdateWeatherWindValues extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1782295200;
    }

    public function update(Connection $connection): void
    {
        // ── Weather ──────────────────────────────────────────────────────────

        // rain → rainy
        $connection->executeStatement(
            "UPDATE `miniwald_explorer_observation`
             SET `weather` = 'rainy'
             WHERE `weather` = 'rain'"
        );

        // thunderstorm → rainy (closest match)
        $connection->executeStatement(
            "UPDATE `miniwald_explorer_observation`
             SET `weather` = 'rainy'
             WHERE `weather` = 'thunderstorm'"
        );

        // fog → cloudy (closest match)
        $connection->executeStatement(
            "UPDATE `miniwald_explorer_observation`
             SET `weather` = 'cloudy'
             WHERE `weather` = 'fog'"
        );

        // ── Wind ─────────────────────────────────────────────────────────────

        // light → light_wind
        $connection->executeStatement(
            "UPDATE `miniwald_explorer_observation`
             SET `wind` = 'light_wind'
             WHERE `wind` = 'light'"
        );

        // moderate → moderate_wind
        $connection->executeStatement(
            "UPDATE `miniwald_explorer_observation`
             SET `wind` = 'moderate_wind'
             WHERE `wind` = 'moderate'"
        );

        // strong → strong_wind
        $connection->executeStatement(
            "UPDATE `miniwald_explorer_observation`
             SET `wind` = 'strong_wind'
             WHERE `wind` = 'strong'"
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // intentionally empty
    }
}
