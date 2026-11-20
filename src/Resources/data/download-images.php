<?php
/**
 * Downloads all species images from Wikimedia Commons into images/
 * Run once on the host: php download-images.php
 * Already-downloaded files are skipped, so re-running retries only failures.
 *
 * Requirements: PHP 8.0+, allow_url_fopen=On (default)
 */
$csvPath = __DIR__ . '/species-data.csv';
$imageDir = __DIR__ . '/images';

if (!is_dir($imageDir) && !mkdir($imageDir, 0755, true)) { // @phpstan-ignore shopware.forbidLocalDiskWrite
    exit("Cannot create images/ directory\n");
}

$ua = 'MiniwaldExplorer/1.0 (InReNa project)';

function slugify(string $value): string
{
    $value = str_replace(
        ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'],
        ['ae', 'oe', 'ue', 'ae', 'oe', 'ue', 'ss'],
        $value
    );
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);

    return trim($value, '-');
}

function mimeToExt(string $mime): string
{
    return match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'image/tiff' => 'tiff',
        default => 'jpg',
    };
}

/**
 * Download a URL with retry + exponential backoff.
 * Returns file contents on success, false on all failures.
 */
function downloadWithRetry(string $url, string $ua): string|false
{
    $delays = [3, 8, 20]; // seconds between retries
    $ctx = stream_context_create([
        'http' => [
            'header' => "User-Agent: {$ua}\r\n",
            'timeout' => 60,
        ],
    ]);

    foreach ($delays as $attempt => $delay) {
        $data = @file_get_contents($url, false, $ctx);
        if ($data !== false && strlen($data) >= 100) {
            return $data;
        }
        $attemptNum = $attempt + 1;
        echo "(attempt {$attemptNum} failed, waiting {$delay}s) ";
        sleep($delay);
    }

    // Final attempt
    $data = @file_get_contents($url, false, $ctx);

    return ($data !== false && strlen($data) >= 100) ? $data : false;
}

// Parse CSV and collect unique Bild-Quelle URLs
$handle = fopen($csvPath, 'r');
if (!$handle) {
    exit("Cannot open CSV: $csvPath\n");
}

$headers = null;
$urls = [];

while (($line = fgetcsv($handle, 0, ',', '"')) !== false) {
    if ($headers === null) {
        $headers = $line;
        continue;
    }
    if (count($line) !== count($headers)) {
        continue;
    }
    $row = array_combine($headers, $line);
    $url = trim($row['Bild-Quelle'] ?? '');
    if (!empty($url)) {
        $urls[$url] = true;
    }
}
fclose($handle);

$urls = array_keys($urls);
echo 'Found ' . count($urls) . " unique image URLs\n\n";

$success = 0;
$fail = 0;
$skip = 0;

$apiCtx = stream_context_create([
    'http' => [
        'header' => "User-Agent: {$ua}\r\n",
        'timeout' => 20,
    ],
]);

foreach ($urls as $commonsPageUrl) {
    // Extract "File:Xyz.jpg" from URL
    if (!preg_match('~/wiki/(File:[^?#]+)~', $commonsPageUrl, $m)) {
        echo "SKIP (no filename): $commonsPageUrl\n";
        ++$skip;
        continue;
    }

    $filename = urldecode($m[1]);                          // "File:Cetonia-aurata.jpg"
    $rawName = preg_replace('/^File:/i', '', $filename);  // "Cetonia-aurata.jpg"
    $rawName = pathinfo($rawName, PATHINFO_FILENAME);     // "Cetonia-aurata"
    $slug = slugify($rawName);                         // "cetonia-aurata"

    echo "[{$slug}] API ... ";

    $apiUrl = 'https://commons.wikimedia.org/w/api.php?' . http_build_query([
        'action' => 'query',
        'titles' => $filename,
        'prop' => 'imageinfo',
        'iiprop' => 'url|mime|size',
        'format' => 'json',
    ]);

    $apiResponse = @file_get_contents($apiUrl, false, $apiCtx);
    if ($apiResponse === false) {
        echo "FAIL (API request failed)\n";
        ++$fail;
        sleep(5);
        continue;
    }

    $data = json_decode($apiResponse, true);
    $pages = $data['query']['pages'] ?? [];
    $page = reset($pages);
    $imageInfo = $page['imageinfo'][0] ?? null;

    if ($imageInfo === null) {
        echo "FAIL (no imageinfo)\n";
        ++$fail;
        sleep(5);
        continue;
    }

    $directUrl = $imageInfo['url'] ?? null;
    $mimeType = $imageInfo['mime'] ?? 'image/jpeg';

    if (empty($directUrl)) {
        echo "FAIL (empty direct URL)\n";
        ++$fail;
        sleep(5);
        continue;
    }

    $ext = mimeToExt($mimeType);
    $outFile = $imageDir . '/' . $slug . '.' . $ext;

    if (file_exists($outFile)) {
        $size = filesize($outFile);
        echo "EXISTS ({$size} bytes): {$slug}.{$ext}\n";
        ++$skip;
        continue;
    }

    echo "downloading {$slug}.{$ext} ... ";

    $imageData = downloadWithRetry($directUrl, $ua);

    if ($imageData === false) {
        echo "FAIL (all attempts failed)\n";
        ++$fail;
        sleep(5);
        continue;
    }

    if (file_put_contents($outFile, $imageData) === false) { // @phpstan-ignore shopware.forbidLocalDiskWrite
        echo "FAIL (cannot write file)\n";
        ++$fail;
        sleep(5);
        continue;
    }

    $size = strlen($imageData);
    echo "OK ({$size} bytes)\n";
    ++$success;

    // Polite delay between successful downloads
    sleep(3);
}

echo "\n";
echo "=============================\n";
echo "Done: {$success} downloaded, {$skip} skipped, {$fail} failed\n";
echo "Files in: {$imageDir}\n";
echo "=============================\n";

foreach (glob($imageDir . '/*') as $f) {
    printf("  %-60s %s\n", basename($f), number_format(filesize($f)) . ' bytes');
}
