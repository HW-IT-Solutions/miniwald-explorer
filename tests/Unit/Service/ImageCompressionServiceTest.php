<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Service;

use InReNa\Service\ImageCompressionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

/**
 * @covers \InReNa\Service\ImageCompressionService
 */
class ImageCompressionServiceTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/miniwald_exif_test_' . uniqid('', true);
        mkdir($this->tmpDir . '/public', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    /**
     * Create a minimal JPEG with a fake EXIF GPS block embedded.
     * We inject a raw APP1/EXIF segment so exif_read_data() can find GPS data.
     */
    private function createJpegWithExif(string $path): void
    {
        // Build a 10×10 red JPEG via GD
        $img = imagecreatetruecolor(10, 10);
        imagefilledrectangle($img, 0, 0, 9, 9, imagecolorallocate($img, 255, 0, 0));

        ob_start();
        imagejpeg($img, null, 95);
        $jpegData = ob_get_clean();
        imagedestroy($img);

        // Minimal EXIF APP1 segment containing a GPS IFD with GPSLatitudeRef = "N"
        $exifApp1 = $this->buildMinimalExifWithGps();

        // Insert APP1 after the SOI marker (first 2 bytes: FF D8)
        $soi  = substr($jpegData, 0, 2);
        $rest = substr($jpegData, 2);
        file_put_contents($path, $soi . $exifApp1 . $rest);
    }

    /**
     * Build a minimal EXIF APP1 marker segment with a GPS latitude tag.
     * Format: FF E1 <length 2 bytes big-endian> "Exif\0\0" <TIFF header + IFD>
     */
    private function buildMinimalExifWithGps(): string
    {
        // TIFF header: little-endian, magic 42, IFD0 offset = 8
        $tiff = pack('vvV', 0x4949, 42, 8);

        // GPS IFD: 1 entry — GPSLatitudeRef = "N\0"
        $gpsIfd  = pack('v', 1);               // entry count
        $gpsIfd .= pack('vvV', 0x0001, 2, 2);  // tag, type ASCII, count 2
        $gpsIfd .= "N\0\0\0";                  // value (fits in 4 bytes)
        $gpsIfd .= pack('V', 0);               // next IFD offset = 0

        // IFD0: 1 entry — GPSInfoIFDPointer (tag 0x8825) pointing to GPS IFD
        $gpsIfdOffset = 8 + 2 + 12 + 4;       // after IFD0
        $ifd0  = pack('v', 1);
        $ifd0 .= pack('vvV', 0x8825, 4, 1);   // tag LONG, count 1
        $ifd0 .= pack('V', $gpsIfdOffset);     // value = GPS IFD offset
        $ifd0 .= pack('V', 0);                 // next IFD offset = 0

        $payload = "Exif\0\0" . $tiff . $ifd0 . $gpsIfd;
        $length  = strlen($payload) + 2;       // length includes the 2 length bytes

        return "\xFF\xE1" . pack('n', $length) . $payload;
    }

    /**
     * Create a minimal PNG (GD-generated, no extra metadata).
     */
    private function createPng(string $path): void
    {
        $img = imagecreatetruecolor(10, 10);
        imagefilledrectangle($img, 0, 0, 9, 9, imagecolorallocate($img, 0, 255, 0));
        imagepng($img, $path, 0);
        imagedestroy($img);
    }

    /**
     * Build a service instance wired to a fake media entity pointing at $filePath.
     * The service resolves: projectDir + '/public' + '/' + basename($filePath)
     *
     * @return ImageCompressionService
     */
    private function buildService(string $filePath, string $mimeType, int $fileSize): ImageCompressionService
    {
        $repo   = $this->createStub(EntityRepository::class);
        $logger = $this->createStub(LoggerInterface::class);

        $media = new MediaEntity();
        $media->setId('test-media-id');
        $media->setMimeType($mimeType);
        $media->setFileSize($fileSize);
        // URL path must match: projectDir + '/public' + urlPath = $filePath
        // projectDir = $this->tmpDir, so urlPath = '/' . basename($filePath)
        $media->setUrl('http://localhost/' . basename($filePath));

        $searchResult = $this->createStub(EntitySearchResult::class);
        $searchResult->method('first')->willReturn($media);
        $repo->method('search')->willReturn($searchResult);

        // projectDir is set so that: projectDir . '/public' . urlPath = filePath
        // urlPath = '/' . basename($filePath)
        // filePath = $this->tmpDir . '/public/' . basename($filePath)
        // → projectDir = $this->tmpDir
        return new ImageCompressionService($repo, $this->tmpDir, $logger);
    }

    // ─── tests ───────────────────────────────────────────────────────────────

    /**
     * A PNG must be re-saved as PNG (not converted to JPEG).
     */
    public function testPngIsResavedAsPng(): void
    {
        $filePath = $this->tmpDir . '/public/test.png';
        $this->createPng($filePath);

        $fileSize = (int) filesize($filePath);
        self::assertLessThan(1048576, $fileSize, 'Test fixture must be < 1 MB');

        $service = $this->buildService($filePath, 'image/png', $fileSize);
        $service->compressObservationImage('test-media-id', Context::createDefaultContext());

        $info = getimagesize($filePath);
        self::assertNotFalse($info, 'Output must be a valid image');
        self::assertSame(IMAGETYPE_PNG, $info[2], 'PNG must be re-saved as PNG, not converted to JPEG');
    }

    /**
     * Unsupported MIME types (webp, gif, heic) must be skipped — file unchanged.
     */
    public function testUnsupportedMimeTypeIsSkipped(): void
    {
        $filePath = $this->tmpDir . '/public/test.webp';
        file_put_contents($filePath, 'fake-webp-data');
        $originalContent = file_get_contents($filePath);

        $service = $this->buildService($filePath, 'image/webp', strlen($originalContent));
        $service->compressObservationImage('test-media-id', Context::createDefaultContext());

        self::assertSame($originalContent, file_get_contents($filePath), 'Unsupported file must be untouched');
    }

    /**
     * A large JPEG (> MAX dimensions) must be resized to fit within 2560×1440.
     */
    public function testLargeJpegIsResized(): void
    {
        $filePath = $this->tmpDir . '/public/large.jpg';

        // Create a 2600×1500 image (exceeds MAX_WIDTH=2560, MAX_HEIGHT=1440)
        $img = imagecreatetruecolor(2600, 1500);
        imagefilledrectangle($img, 0, 0, 2599, 1499, imagecolorallocate($img, 100, 150, 200));
        imagejpeg($img, $filePath, 95);
        imagedestroy($img);

        $fileSize = (int) filesize($filePath);

        $service = $this->buildService($filePath, 'image/jpeg', $fileSize);
        $service->compressObservationImage('test-media-id', Context::createDefaultContext());

        $info = getimagesize($filePath);
        self::assertNotFalse($info);
        self::assertLessThanOrEqual(2560, $info[0], 'Width must be within MAX_WIDTH after resize');
        self::assertLessThanOrEqual(1440, $info[1], 'Height must be within MAX_HEIGHT after resize');
    }

    /**
     * stripExifAndResize() is public — verify it works directly on a file path.
     */
    public function testStripExifAndResizeDirectly(): void
    {
        $filePath = $this->tmpDir . '/public/direct.jpg';

        $img = imagecreatetruecolor(100, 100);
        imagefilledrectangle($img, 0, 0, 99, 99, imagecolorallocate($img, 200, 100, 50));
        imagejpeg($img, $filePath, 90);
        imagedestroy($img);

        $repo   = $this->createStub(EntityRepository::class);
        $logger = $this->createStub(LoggerInterface::class);
        $service = new ImageCompressionService($repo, $this->tmpDir, $logger);

        // Must not throw
        $service->stripExifAndResize($filePath);

        $info = getimagesize($filePath);
        self::assertNotFalse($info, 'Output must be a valid image after direct strip');
        self::assertSame(IMAGETYPE_JPEG, $info[2]);
    }
}
