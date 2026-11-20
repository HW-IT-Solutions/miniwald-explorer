<?php declare(strict_types=1);

namespace InReNa\Subscriber;

use InReNa\Service\ImageCompressionService;
use InReNa\Service\MediaFolderService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\Event\MediaUploadedEvent;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ObservationPhotoSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ImageCompressionService $compressionService,
        private readonly MediaFolderService $folderService,
        private readonly LoggerInterface $logger,
        private readonly EntityRepository $mediaRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MediaUploadedEvent::class => 'onMediaUpload',
        ];
    }

    public function onMediaUpload(MediaUploadedEvent $event): void
    {
        $mediaId = $event->getMediaId();

        try {
            // Fetch the media entity with all necessary data
            $criteria = new Criteria([$mediaId]);
            $criteria->addAssociation('mediaFolder');

            /** @var MediaEntity|null $media */
            $media = $this->mediaRepository->search($criteria, $event->getContext())->first();

            if (!$media) {
                $this->logger->warning('Media entity not found', [
                    'mediaId' => $mediaId,
                ]);

                return;
            }

            // Check if this media belongs to Observations folder
            if (!$media->getMediaFolderId()) {
                return;
            }

            $observationsFolderId = $this->folderService->getFolderIdByPath(
                'MiniwaldExplorer/Observations',
                $event->getContext()
            );

            if (!$observationsFolderId || $media->getMediaFolderId() !== $observationsFolderId) {
                return;
            }

            // Only process images
            if (!str_contains($media->getMimeType() ?? '', 'image')) {
                return;
            }

            $this->compressionService->compressObservationImage(
                $mediaId,
                $event->getContext()
            );
        } catch (\Exception $e) {
            $this->logger->error('Error processing observation photo upload', [
                'mediaId' => $mediaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
