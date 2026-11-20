# Miniwald-Explorer

Citizen-Science-Plugin für Shopware 6.7. Schulklassen und weitere Teilnehmende können Beobachtungen in Miniwäldern erfassen, Pflanzen und Arten zuordnen und Messungen auswerten. Das Plugin enthält die Storefront, Administration, Datenbankmigrationen, Exporte und Tests.

## Installation

Eine bestehende Shopware-6.7-Installation mit PHP 8.3 oder neuer wird vorausgesetzt. Das Repository als `custom/plugins/MiniwaldExplorer` einbinden. Im Hauptverzeichnis der Shopware-Installation ausführen:

```sh
bin/console plugin:refresh
bin/console plugin:install --activate MiniwaldExplorer
bin/build-administration.sh
bin/build-storefront.sh
```

## Inhalte und Karte

Das Repository enthält den Anwendungscode unter der [MIT-Lizenz](LICENSE). Fotos, Projektlogos, Schriftdateien und redaktionelle Artenbeschreibungen werden separat bereitgestellt. Betreiber können eigene freigegebene Medien an den in den Templates angegebenen Pfaden und eigene Artdaten in `src/Resources/data/species-data.csv` vor der ersten Installation hinterlegen.

Die optionale Kartenansicht nutzt MapLibre GL JS. Für ihre Aktivierung die Dateien `maplibre-gl.js` und `maplibre-gl.css` aus der Distribution [maplibre-gl 5.23.0](https://github.com/maplibre/maplibre-gl-js/releases/tag/v5.23.0) unter `src/Resources/public/assets/map/` bereitstellen, `bin/console assets:install` ausführen und in der Pluginkonfiguration einen eigenen Kartenstil eintragen. Bei Weitergabe dieser Bibliothek sind deren Lizenz- und Attributionshinweise zu beachten.

## Tests

Im Pluginverzeichnis führt `node --test tests/Storefront/*.test.mjs` den Storefront-Test aus. Die PHP-Tests unter `tests/Unit` und `tests/Integration` verwenden PHPUnit und die Shopware-Testumgebung. Integrationstests in einer eigenen Testdatenbank ausführen.
