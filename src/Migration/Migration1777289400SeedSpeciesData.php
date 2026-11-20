<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1777289400SeedSpeciesData extends MigrationStep
{
    private const CSV_PATH = __DIR__ . '/../Resources/data/species-data.csv';
    private const IMAGES_PATH = __DIR__ . '/../Resources/data/images';

    // Folder paths within the MiniwaldExplorer media folder hierarchy
    private const FOLDER_CATEGORIES = 'MiniwaldExplorer/Species/Categories';
    private const FOLDER_SPECIES = 'MiniwaldExplorer/Species/Species';
    private const FOLDER_SUBSPECIES = 'MiniwaldExplorer/Species/Subspecies';

    private string $publicPath = '';

    public function getCreationTimestamp(): int
    {
        return 1777289400;
    }

    public function update(Connection $connection): void
    {
        $this->publicPath = $this->resolvePublicPath();

        $languageId = $this->getDefaultLanguageId($connection);
        $rows = $this->parseCsv(self::CSV_PATH);

        if (empty($rows)) {
            return;
        }

        // --- Pass 1: Insert categories (deduplicated, ordered by first appearance) ---
        $categoryPosition = 1;
        $seenCategories = [];

        foreach ($rows as $row) {
            $kategorie = trim($row['Kategorie']);
            if (isset($seenCategories[$kategorie])) {
                continue;
            }
            $seenCategories[$kategorie] = true;
            $this->insertCategoryIfNotExists(
                $kategorie,
                $row['Bild-Quelle'],
                $categoryPosition,
                $languageId,
                $connection
            );
            ++$categoryPosition;
        }

        // --- Pass 2: Insert species + subspecies with global position counters ---
        $speciesPosition = 1;
        $subspeciesPosition = 1;

        foreach ($rows as $row) {
            $kategorie = trim($row['Kategorie']);
            $tierart = trim($row['Tierart']);
            $empfohlen = trim($row['Empfohlene Spezies']);
            $lateinisch = trim($row['Lateinischer Name']);
            $beschreibung = trim($row['Kurzbeschreibung']);
            $merkmale = trim($row['Merkmale']);
            $bildQuelle = trim($row['Bild-Quelle']);

            $categoryId = $this->getCategoryIdByName($kategorie, $connection);
            if ($categoryId === null) {
                error_log("[SeedSpeciesData] Category not found: {$kategorie}");
                continue;
            }

            // When Tierart == Empfohlene Spezies: no subspecies, latin name goes on species.
            // When they differ: subspecies gets the latin name, species scientific_name stays ''.
            $speciesScientificName = ($tierart === $empfohlen) ? $lateinisch : '';

            $speciesId = $this->insertSpeciesIfNotExists(
                $tierart,
                $categoryId,
                $beschreibung,
                $merkmale,
                $bildQuelle,
                $speciesPosition,
                $speciesScientificName,
                $languageId,
                $connection
            );
            ++$speciesPosition;

            // Create subspecies only when Tierart differs from Empfohlene Spezies
            if ($tierart !== $empfohlen && $speciesId !== null) {
                $this->insertSubspeciesIfNotExists(
                    $empfohlen,
                    $lateinisch,
                    $speciesId,
                    $beschreibung,
                    $merkmale,
                    $bildQuelle,
                    $subspeciesPosition,
                    $languageId,
                    $connection
                );
                ++$subspeciesPosition;
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // Nothing to destroy
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getDefaultLanguageId(Connection $connection): string
    {
        $id = $connection->fetchOne(
            'SELECT id FROM language WHERE name = \'Deutsch\' LIMIT 1'
        );

        if (!$id) {
            $id = $connection->fetchOne(
                'SELECT id FROM language ORDER BY created_at LIMIT 1'
            );
        }

        if (!$id) {
            throw new \RuntimeException('[SeedSpeciesData] No language found in database.');
        }

        return $id;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseCsv(string $path): array
    {
        if (!file_exists($path)) {
            error_log("[SeedSpeciesData] CSV file not found: {$path}");

            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            error_log("[SeedSpeciesData] Cannot open CSV: {$path}");

            return [];
        }

        $rows = [];
        $headers = null;

        while (($line = fgetcsv($handle, 0, ',', '"')) !== false) {
            if ($headers === null) {
                $headers = $line;
                continue;
            }
            if (count($line) !== count($headers)) {
                continue;
            }
            $rows[] = array_combine($headers, $line);
        }

        fclose($handle);

        return $rows;
    }

    private function getCategoryIdByName(string $name, Connection $connection): ?string
    {
        $id = $connection->fetchOne(
            'SELECT sc.id
             FROM miniwald_explorer_species_category sc
             JOIN miniwald_explorer_species_category_translation sct
               ON sct.miniwald_explorer_species_category_id = sc.id
             WHERE sct.name = ?
             LIMIT 1',
            [$name]
        );

        return $id !== false ? $id : null;
    }

    /**
     * Looks up a media folder by hierarchical path, e.g. "MiniwaldExplorer/Species/Categories".
     * Returns binary folder ID or null if not found.
     */
    private function getFolderIdByPath(string $folderPath, Connection $connection): ?string
    {
        $parts = explode('/', $folderPath);
        $parentId = null;

        foreach ($parts as $part) {
            if ($parentId === null) {
                $id = $connection->fetchOne(
                    'SELECT id FROM media_folder WHERE name = ? AND parent_id IS NULL LIMIT 1',
                    [$part]
                );
            } else {
                $id = $connection->fetchOne(
                    'SELECT id FROM media_folder WHERE name = ? AND parent_id = ? LIMIT 1',
                    [$part, $parentId]
                );
            }

            if ($id === false || $id === null) {
                error_log("[SeedSpeciesData] Media folder not found: {$part} (path: {$folderPath})");

                return null;
            }

            $parentId = $id;
        }

        return $parentId;
    }

    /**
     * Imports a locally-stored image (from Resources/data/images/) into Shopware's media system.
     *
     * The image filename is derived from the Wikimedia Commons page URL using the same
     * slugify logic used by download-images.php, so filenames always match.
     *
     * Path formula (FilenamePathStrategy):
     *   media/{md5_p1}/{md5_p2}/{md5_p3}/{uploadedAt_unix}/{slug}.{ext}
     *
     * Returns binary media ID on success, null on failure.
     */
    private function importImageFromLocalFile(
        string $commonsPageUrl,
        string $folderPath,
        Connection $connection
    ): ?string {
        if (empty($commonsPageUrl)) {
            return null;
        }

        // Extract "File:Xyz.jpg" from URL like https://commons.wikimedia.org/wiki/File:Xyz.jpg
        if (!preg_match('~/wiki/(File:[^?#]+)~', $commonsPageUrl, $m)) {
            error_log("[SeedSpeciesData] Cannot extract filename from: {$commonsPageUrl}");

            return null;
        }

        $filename = urldecode($m[1]);                          // "File:Cetonia-aurata.jpg"
        $rawName = preg_replace('~^File:~i', '', $filename);  // "Cetonia-aurata.jpg"
        $origExt = strtolower(pathinfo($rawName, PATHINFO_EXTENSION)); // "jpg"
        $rawName = pathinfo($rawName, PATHINFO_FILENAME);     // "Cetonia-aurata"
        $slug = $this->slugify($rawName);                  // "cetonia-aurata"

        if (empty($slug)) {
            error_log("[SeedSpeciesData] Empty slug for: {$commonsPageUrl}");

            return null;
        }

        // Normalise extension (jpeg → jpg)
        $extension = match ($origExt) {
            'jpeg' => 'jpg',
            'jpg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'webp' => 'webp',
            'svg' => 'svg',
            'tiff' => 'tiff',
            default => 'jpg',
        };

        // Locate local image file
        $localFile = self::IMAGES_PATH . '/' . $slug . '.' . $extension;
        if (!file_exists($localFile)) {
            error_log("[SeedSpeciesData] Local image not found: {$localFile}");

            return null;
        }

        $imageData = file_get_contents($localFile);
        if ($imageData === false || strlen($imageData) < 100) {
            error_log("[SeedSpeciesData] Cannot read local image: {$localFile}");

            return null;
        }

        $fileSize = strlen($imageData);
        $mimeType = $this->extensionToMime($extension);

        // Compute Shopware FilenamePathStrategy path:
        // media/{md5_p1}/{md5_p2}/{md5_p3}/{uploadedAt_unix}/{slug}.{ext}
        $uploadedAt = time();
        $hash = md5($slug);
        $p1 = $this->applyHashReplacement(substr($hash, 0, 2));
        $p2 = $this->applyHashReplacement(substr($hash, 2, 2));
        $p3 = $this->applyHashReplacement(substr($hash, 4, 2));
        $mediaPath = "media/{$p1}/{$p2}/{$p3}/{$uploadedAt}/{$slug}.{$extension}";

        // Write file to public/{mediaPath}
        $absolutePath = $this->publicPath . '/' . $mediaPath;
        $dir = dirname($absolutePath);

        // @phpstan-ignore shopware.forbidLocalDiskWrite
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            error_log("[SeedSpeciesData] Cannot create directory: {$dir}");

            return null;
        }

        // @phpstan-ignore shopware.forbidLocalDiskWrite
        if (file_put_contents($absolutePath, $imageData) === false) {
            error_log("[SeedSpeciesData] Cannot write image to: {$absolutePath}");

            return null;
        }

        // Resolve folder ID by hierarchical path
        $folderId = $this->getFolderIdByPath($folderPath, $connection);

        // Generate UUID for media record
        $mediaId = Uuid::randomBytes();

        $uploadedAtFormatted = (new \DateTime('@' . $uploadedAt))->format('Y-m-d H:i:s.v');
        $now = (new \DateTime())->format('Y-m-d H:i:s.v');

        try {
            $connection->insert('media', [
                'id' => $mediaId,
                'media_folder_id' => $folderId,
                'mime_type' => $mimeType,
                'file_extension' => $extension,
                'file_name' => $slug,
                'file_size' => $fileSize,
                'path' => $mediaPath,
                'uploaded_at' => $uploadedAtFormatted,
                'created_at' => $now,
            ]);
        } catch (\Throwable $e) {
            error_log('[SeedSpeciesData] media INSERT failed: ' . $e->getMessage());
            @unlink($absolutePath); // @phpstan-ignore shopware.forbidLocalDiskWrite

            return null;
        }

        return $mediaId;
    }

    private function insertCategoryIfNotExists(
        string $name,
        string $bildQuelle,
        int $position,
        string $languageId,
        Connection $connection
    ): string {
        $existing = $this->getCategoryIdByName($name, $connection);
        if ($existing !== null) {
            return $existing;
        }

        $imageId = $this->importImageFromLocalFile($bildQuelle, self::FOLDER_CATEGORIES, $connection);

        $id = Uuid::randomBytes();
        $now = (new \DateTime())->format('Y-m-d H:i:s.v');

        $connection->insert('miniwald_explorer_species_category', [
            'id' => $id,
            'image_id' => $imageId,
            'position' => $position,
            'active' => 1,
            'created_at' => $now,
        ]);

        $connection->insert('miniwald_explorer_species_category_translation', [
            'miniwald_explorer_species_category_id' => $id,
            'language_id' => $languageId,
            'name' => $name,
            'created_at' => $now,
        ]);

        return Uuid::fromBytesToHex($id);
    }

    private function insertSpeciesIfNotExists(
        string $name,
        string $categoryId,
        string $description,
        string $identificationTips,
        string $bildQuelle,
        int $position,
        string $scientificName,
        string $languageId,
        Connection $connection
    ): ?string {
        $existing = $connection->fetchOne(
            'SELECT s.id
             FROM miniwald_explorer_species s
             JOIN miniwald_explorer_species_translation st
               ON st.miniwald_explorer_species_id = s.id
             WHERE st.name = ?
               AND s.species_category_id = ?
             LIMIT 1',
            [$name, $categoryId]
        );

        if ($existing !== false) {
            return $existing;
        }

        $imageId = $this->importImageFromLocalFile($bildQuelle, self::FOLDER_SPECIES, $connection);

        $id = Uuid::randomBytes();
        $now = (new \DateTime())->format('Y-m-d H:i:s.v');

        try {
            $connection->insert('miniwald_explorer_species', [
                'id' => $id,
                'species_category_id' => $categoryId,
                'image_id' => $imageId,
                'scientific_name' => $scientificName,  // latin name when no subspecies, else ''
                'position' => $position,
                'active' => 1,
                'created_at' => $now,
            ]);

            $connection->insert('miniwald_explorer_species_translation', [
                'miniwald_explorer_species_id' => $id,
                'language_id' => $languageId,
                'name' => $name,
                'description' => $description,
                'identification_tips' => $identificationTips,
                'created_at' => $now,
            ]);
        } catch (\Throwable $e) {
            error_log("[SeedSpeciesData] species INSERT failed for '{$name}': " . $e->getMessage());

            return null;
        }

        return $id;
    }

    private function insertSubspeciesIfNotExists(
        string $name,
        string $scientificName,
        string $speciesId,
        string $description,
        string $identificationTips,
        string $bildQuelle,
        int $position,
        string $languageId,
        Connection $connection
    ): void {
        // Skip if subspecies with this name already exists under this species
        $existing = $connection->fetchOne(
            'SELECT sub.id
             FROM miniwald_explorer_subspecies sub
             JOIN miniwald_explorer_subspecies_translation subt
               ON subt.miniwald_explorer_subspecies_id = sub.id
             WHERE subt.name = ?
               AND sub.species_id = ?
             LIMIT 1',
            [$name, $speciesId]
        );

        if ($existing !== false) {
            return;
        }

        // Reuse the species image — avoids importing the same image twice
        $speciesImageId = $connection->fetchOne(
            'SELECT image_id FROM miniwald_explorer_species WHERE id = ? LIMIT 1',
            [$speciesId]
        );

        // If species has no image, try importing (edge case)
        if ($speciesImageId === false || $speciesImageId === null) {
            $speciesImageId = $this->importImageFromLocalFile($bildQuelle, self::FOLDER_SUBSPECIES, $connection);
        }

        $id = Uuid::randomBytes();
        $now = (new \DateTime())->format('Y-m-d H:i:s.v');

        try {
            $connection->insert('miniwald_explorer_subspecies', [
                'id' => $id,
                'species_id' => $speciesId,
                'image_id' => $speciesImageId ?: null,
                'scientific_name' => $scientificName,
                'position' => $position,
                'active' => 1,
                'created_at' => $now,
            ]);

            $connection->insert('miniwald_explorer_subspecies_translation', [
                'miniwald_explorer_subspecies_id' => $id,
                'language_id' => $languageId,
                'name' => $name,
                'description' => $description,
                'identification_tips' => $identificationTips,
                'created_at' => $now,
            ]);
        } catch (\Throwable $e) {
            error_log("[SeedSpeciesData] subspecies INSERT failed for '{$name}': " . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Path / slug utilities
    // -------------------------------------------------------------------------

    /**
     * Converts a string to a URL-safe slug.
     * "Cetonia-aurata" → "cetonia-aurata"
     * "Heuschrecke MG 6232" → "heuschrecke-mg-6232"
     */
    private function slugify(string $value): string
    {
        // Transliterate common German characters
        $value = str_replace(
            ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'],
            ['ae', 'oe', 'ue', 'ae', 'oe', 'ue', 'ss'],
            $value
        );

        $value = strtolower($value);
        // Replace non-alphanumeric characters with hyphens
        $value = preg_replace('~[^a-z0-9]+~', '-', $value);

        // Trim leading/trailing hyphens
        return trim($value, '-');
    }

    /**
     * Applies Shopware's hash character replacement: 'ad' → 'g0'.
     * See AbstractMediaPathStrategy::replaceCharacters()
     */
    private function applyHashReplacement(string $pair): string
    {
        return $pair === 'ad' ? 'g0' : $pair;
    }

    private function extensionToMime(string $ext): string
    {
        return match ($ext) {
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'tiff' => 'image/tiff',
            default => 'image/jpeg',
        };
    }

    /**
     * Resolves the absolute path to the public/ directory.
     * This file lives at: <project>/custom/static-plugins/MiniwaldExplorer/src/Migration/
     * Project root is 6 levels up from __FILE__.
     */
    private function resolvePublicPath(): string
    {
        $projectRoot = dirname(__FILE__, 6);
        $path = $projectRoot . '/public';

        if (!is_dir($path)) {
            throw new \RuntimeException("[SeedSpeciesData] public/ directory not found at: {$path}");
        }

        return $path;
    }
}
