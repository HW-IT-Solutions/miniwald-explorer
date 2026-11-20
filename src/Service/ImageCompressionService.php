<?php declare(strict_types=1);

namespace InReNa\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ImageCompressionService
{
    private const MAX_WIDTH = 2560;
    private const MAX_HEIGHT = 1440;
    private const JPEG_QUALITY = 85;
    // PNG compression level 6 = good balance of size vs. speed (0–9 scale)
    private const PNG_COMPRESSION = 6;

    public function __construct(
        private readonly EntityRepository $mediaRepository,
        private readonly string $projectDir,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Strip EXIF metadata and optionally resize an observation image.
     *
     * Always performs a GD round-trip so that EXIF data (including GPS) is
     * removed regardless of file size. Resizing only happens when the image
     * exceeds MAX_WIDTH × MAX_HEIGHT.
     */
    public function compressObservationImage(string $mediaId, Context $context): void
    {
        $criteria = new Criteria([$mediaId]);
        /** @var MediaEntity|null $media */
        $media = $this->mediaRepository->search($criteria, $context)->first();

        if (!$media) {
            return;
        }

        if (!$this->isSupportedImageType($media)) {
            return;
        }

        $filePath = $this->getMediaFilePath($media);

        if (!file_exists($filePath)) {
            return;
        }

        try {
            $this->stripExifAndResize($filePath);
        } catch (\Exception $e) {
            $this->logger->error('Failed to strip EXIF / compress image', [
                'mediaId' => $mediaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Load the image via GD, optionally resize it, then re-save it.
     * The GD re-save discards all EXIF/XMP/IPTC metadata unconditionally.
     */
    public function stripExifAndResize(string $filePath): void
    {
        $imageInfo = getimagesize($filePath);

        if (!$imageInfo) {
            throw new \RuntimeException('Unable to get image information');
        }

        [$width, $height, $type] = $imageInfo;

        // Load image based on type
        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG => imagecreatefrompng($filePath),
            default => null,
        };

        if (!$image) {
            throw new \RuntimeException('Unable to load image: unsupported type');
        }

        // Calculate new dimensions maintaining aspect ratio.
        // ratio > 1 means image is already within limits — no resize needed.
        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);

        if ($ratio < 1) {
            // Image exceeds max dimensions — resize it
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled(
                $resized,
                $image,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height
            );

            $this->saveImage($resized, $type, $filePath);
            imagedestroy($resized);
        } else {
            // Image is within size limits — re-save as-is to strip EXIF
            $this->saveImage($image, $type, $filePath);
        }

        imagedestroy($image);
    }

    /**
     * Returns true only for JPEG and PNG — the two types GD can losslessly
     * round-trip while stripping all metadata.
     */
    private function isSupportedImageType(MediaEntity $media): bool
    {
        return in_array($media->getMimeType(), ['image/jpeg', 'image/jpg', 'image/png'], true);
    }

    /**
     * Save a GD image resource back to disk in its original format.
     * JPEG → quality 85, PNG → compression level 6.
     */
    private function saveImage(\GdImage $image, int $type, string $filePath): void
    {
        match ($type) {
            IMAGETYPE_PNG => imagepng($image, $filePath, self::PNG_COMPRESSION),
            default => imagejpeg($image, $filePath, self::JPEG_QUALITY),
        };
    }

    private function getMediaFilePath(MediaEntity $media): string
    {
        // Get the URL and extract the path portion
        $url = $media->getUrl();

        // Parse URL to get the path component
        $parsedUrl = parse_url($url);
        $urlPath = $parsedUrl['path'] ?? '';

        // The URL path starts with /media/, we need the full filesystem path
        return $this->projectDir . '/public' . $urlPath;
    }
}
