<?php declare(strict_types=1);

namespace InReNa\Controller\Storefront;

use InReNa\Content\Miniwald\MiniwaldEntity;
use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Content\Plant\PlantEntity;
use InReNa\Content\Species\SpeciesEntity;
use InReNa\Content\Subspecies\SubspeciesEntity;
use InReNa\Event\ObservationSubmittedEvent;
use InReNa\Service\MediaFolderService;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class ObservationController extends StorefrontController
{
    public function __construct(
        private readonly EntityRepository $speciesCategoryRepository,
        private readonly EntityRepository $speciesRepository,
        private readonly EntityRepository $subspeciesRepository,
        private readonly EntityRepository $miniwaldRepository,
        private readonly EntityRepository $observationRepository,
        private readonly EntityRepository $observationItemRepository,
        private readonly EntityRepository $groundMeasurementRepository,
        private readonly EntityRepository $plantRepository,
        private readonly EntityRepository $plantMeasurementRepository,
        private readonly EntityRepository $photoRepository,
        private readonly FileSaver $fileSaver,
        private readonly MediaFolderService $mediaFolderService,
        private readonly EntityRepository $mediaRepository,
        private readonly EntityRepository $customerRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    #[Route(
        path: '/observation',
        name: 'frontend.miniwald.observation',
        methods: ['GET'],
    )]
    public function index(Request $request, SalesChannelContext $context): Response
    {
        $customer = $context->getCustomer();

        $categoryCriteria = (new Criteria())
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            ->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));

        $categories = $this->speciesCategoryRepository
            ->search($categoryCriteria, $context->getContext())
            ->getEntities();

        $speciesCriteria = (new Criteria())
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            // Subspecies are not shown as cards; they only feed the dropdown on the
            // custom species card, so name + position are all that is needed.
            ->addAssociation('subspecies')
            ->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));

        $speciesCriteria->getAssociation('subspecies')
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));

        $species = $this->speciesRepository
            ->search($speciesCriteria, $context->getContext())
            ->getEntities();

        $speciesByCategory = [];
        foreach ($species as $entry) {
            /** @var SpeciesEntity $entry */
            $categoryId = $entry->getSpeciesCategoryId();
            if ($categoryId === null) {
                continue;
            }
            $speciesByCategory[$categoryId][] = $entry;
        }

        $miniwaldCriteria = (new Criteria())
            ->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));

        $miniwaelder = $this->miniwaldRepository
            ->search($miniwaldCriteria, $context->getContext())
            ->getEntities();

        // Customer-specific data — only available when logged in
        $customerName = '';
        $hideInstructionInsects = false;
        $hideInstructionBirds = false;
        $hideInstructionButterflies = false;
        $hideInstructionSoil = false;
        $hideInstructionGround = false;
        $hideInstructionPlants = false;

        if ($customer !== null) {
            $customerName = trim($customer->getFirstName());
            $customFields = $customer->getCustomFields() ?? [];
            $hideInstructionInsects = (bool) ($customFields['miniwald_hide_instruction_insects'] ?? false);
            $hideInstructionBirds = (bool) ($customFields['miniwald_hide_instruction_birds'] ?? false);
            $hideInstructionButterflies = (bool) ($customFields['miniwald_hide_instruction_butterflies'] ?? false);
            $hideInstructionSoil = (bool) ($customFields['miniwald_hide_instruction_soil'] ?? false);
            $hideInstructionGround = (bool) ($customFields['miniwald_hide_instruction_ground'] ?? false);
            $hideInstructionPlants = (bool) ($customFields['miniwald_hide_instruction_plants'] ?? false);
        }

        $appName = $this->trans('miniwaldExplorer.meta.appName');

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/observation/index.html.twig',
            [
                'categories' => $categories,
                'speciesByCategory' => $speciesByCategory,
                'miniwaelder' => $miniwaelder,
                'customerName' => $customerName,
                'isLoggedIn' => $customer !== null,
                'hideInstructionInsects' => $hideInstructionInsects,
                'hideInstructionBirds' => $hideInstructionBirds,
                'hideInstructionButterflies' => $hideInstructionButterflies,
                'hideInstructionSoil' => $hideInstructionSoil,
                'hideInstructionGround' => $hideInstructionGround,
                'hideInstructionPlants' => $hideInstructionPlants,
                'pageTitle' => $this->trans('miniwaldExplorer.meta.pageTitleObservation') . ' | ' . $appName,
            ]
        );
    }

    #[Route(
        path: '/observation/hide-instruction',
        name: 'frontend.miniwald.observation.hide_instruction',
        methods: ['POST'],
    )]
    public function hideInstruction(Request $request, SalesChannelContext $context): JsonResponse
    {
        $customer = $context->getCustomer();
        if ($customer === null) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? null;

        $fieldMap = [
            'insects'     => 'miniwald_hide_instruction_insects',
            'birds'       => 'miniwald_hide_instruction_birds',
            'butterflies' => 'miniwald_hide_instruction_butterflies',
            'soil'        => 'miniwald_hide_instruction_soil',
            'ground'      => 'miniwald_hide_instruction_ground',
            'plants'      => 'miniwald_hide_instruction_plants',
        ];

        if (!isset($fieldMap[$type])) {
            return new JsonResponse(['error' => 'Invalid type'], Response::HTTP_BAD_REQUEST);
        }

        $this->customerRepository->update([
            [
                'id' => $customer->getId(),
                'customFields' => [$fieldMap[$type] => true],
            ],
        ], $context->getContext());

        return new JsonResponse(['success' => true]);
    }

    #[Route(
        path: '/observation/plants-for-miniwald',
        name: 'frontend.miniwald.observation.plants_for_miniwald',
        methods: ['POST'],
    )]
    public function plantsForMiniwald(Request $request, SalesChannelContext $context): JsonResponse
    {
        $customer = $context->getCustomer();
        if ($customer === null) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $miniwaldId = $data['miniwaldId'] ?? null;

        $criteria = new Criteria();
        if (!empty($miniwaldId)) {
            $criteria->addFilter(new EqualsFilter('miniwaldId', $miniwaldId));
        }
        $criteria
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            ->addSorting(new FieldSorting('sortOrder', FieldSorting::ASCENDING));

        $plants = $this->plantRepository
            ->search($criteria, $context->getContext())
            ->getEntities();

        $result = [];
        foreach ($plants as $plant) {
            /** @var PlantEntity $plant */
            $imageUrl = null;
            if ($plant->getImage() !== null) {
                $imageUrl = $plant->getImage()->getUrl();
            }
            $result[] = [
                'id' => $plant->getId(),
                'name' => $plant->getName() ?? '',
                'scientificName' => $plant->getScientificName() ?? '',
                'position' => $plant->getPosition() ?? '',
                'imageUrl' => $imageUrl,
            ];
        }

        return new JsonResponse(['plants' => $result]);
    }

    #[Route(
        path: '/observation/save',
        name: 'frontend.miniwald.observation.save',
        methods: ['POST'],
    )]
    public function save(Request $request, SalesChannelContext $context): JsonResponse
    {
        $customer = $context->getCustomer();
        if ($customer === null) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $miniwaldId = $data['miniwaldId'] ?? null;
        $observedAt = $data['observedAt'] ?? null;
        $latitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;
        $weather = $data['weather'] ?? null;
        $wind = $data['wind'] ?? null;
        $items = $data['items'] ?? [];

        // Explicit type from the wizard; fall back to deriving it from the
        // payload shape so older cached clients keep working
        $type = $data['type'] ?? null;
        if (!in_array($type, ObservationDefinition::TYPES, true)) {
            $type = match (true) {
                !empty($items) => ObservationDefinition::TYPE_ANIMAL,
                !empty($data['plantMeasurements']) => ObservationDefinition::TYPE_PLANT,
                !empty($data['groundMeasurements']) => ObservationDefinition::TYPE_GROUND,
                default => null,
            };
        }

        // Species category only applies to animal observations
        $speciesCategoryId = null;
        if ($type === ObservationDefinition::TYPE_ANIMAL) {
            $rawCategoryId = $data['speciesCategoryId'] ?? null;
            if (is_string($rawCategoryId) && Uuid::isValid($rawCategoryId)) {
                $exists = $this->speciesCategoryRepository
                    ->searchIds(new Criteria([$rawCategoryId]), $context->getContext())
                    ->firstId();
                $speciesCategoryId = $exists !== null ? $rawCategoryId : null;
            }
        }

        if (empty($observedAt)) {
            $observedAt = (new \DateTime())->format(\DateTime::ATOM);
        }

        try {
            $observedAtDate = new \DateTime($observedAt);
        } catch (\Exception) {
            $observedAtDate = new \DateTime();
        }

        $observationId = Uuid::randomHex();

        $this->observationRepository->create([
            [
                'id' => $observationId,
                'customerId' => $customer->getId(),
                'miniwaldId' => $miniwaldId ?: null,
                'type' => $type,
                'speciesCategoryId' => $speciesCategoryId,
                'observedAt' => $observedAtDate,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'weather' => $weather ?: null,
                'wind' => $wind ?: null,
                'status' => 'submitted',
                'submittedAt' => new \DateTime(),
            ],
        ], $context->getContext());

        // Subspecies can be picked on the custom species card. Resolve every submitted
        // subspecies id in one query: unknown ids are dropped, known ones determine the
        // species id themselves — the one sent by the client is not trusted here.
        $speciesIdBySubspeciesId = $this->resolveSubspecies($items, $context);

        $itemRecords = [];
        foreach ($items as $item) {
            $speciesId = $item['speciesId'] ?? null;
            $subspeciesId = $item['subspeciesId'] ?? null;
            $clientId = $item['clientId'] ?? null;
            $customSpeciesName = isset($item['customSpeciesName']) ? trim((string) $item['customSpeciesName']) : null;
            $count = isset($item['count']) ? (int) $item['count'] : 0;
            $remarks = isset($item['remarks']) ? trim((string) $item['remarks']) : null;

            if (!empty($subspeciesId) && isset($speciesIdBySubspeciesId[$subspeciesId])) {
                // A known subspecies fully identifies the item: it replaces both the
                // client-sent species id and any free-text name.
                $speciesId = $speciesIdBySubspeciesId[$subspeciesId];
                $customSpeciesName = null;
            } else {
                // Unknown subspecies id — fall back to the species id / free-text name
                $subspeciesId = null;
            }

            // Require either a known speciesId or a custom free-text name
            if (empty($speciesId) && empty($customSpeciesName)) {
                continue;
            }
            $record = [
                'id' => Uuid::randomHex(),
                'observationId' => $observationId,
                'speciesId' => $speciesId ?: null,
                'subspeciesId' => $subspeciesId,
                'count' => max(0, $count),
                '_clientId' => $clientId,   // transient: echoed back to JS, not persisted
            ];
            if (!empty($customSpeciesName)) {
                $record['customSpeciesName'] = $customSpeciesName;
            }
            if (!empty($remarks)) {
                $record['remarks'] = $remarks;
            }
            $itemRecords[] = $record;
        }

        if (!empty($itemRecords)) {
            // Strip the transient _clientId field before persisting — DAL rejects unknown fields
            $persistRecords = array_map(function (array $r) {
                unset($r['_clientId']);

                return $r;
            }, $itemRecords);
            $this->observationItemRepository->create($persistRecords, $context->getContext());
        }

        // Persist ground measurement items (1–3, one per location type)
        $groundMeasurementsPayload = $data['groundMeasurements'] ?? [];
        $allowedLocationTypes = ['wood_center', 'wood_edge', 'street_curb'];
        $seenLocationTypes = [];
        $groundRecords = [];

        if (is_array($groundMeasurementsPayload)) {
            foreach ($groundMeasurementsPayload as $gm) {
                $locationType = $gm['locationType'] ?? null;
                if (!in_array($locationType, $allowedLocationTypes, true)) {
                    continue;
                }
                // Enforce one per type
                if (in_array($locationType, $seenLocationTypes, true)) {
                    continue;
                }
                $seenLocationTypes[] = $locationType;

                $record = [
                    'id' => Uuid::randomHex(),
                    'observationId' => $observationId,
                    'locationType' => $locationType,
                ];

                if (isset($gm['airTemperature']) && is_numeric($gm['airTemperature'])) {
                    $record['airTemperature'] = (float) $gm['airTemperature'];
                }
                if (isset($gm['groundTemperature']) && is_numeric($gm['groundTemperature'])) {
                    $record['groundTemperature'] = (float) $gm['groundTemperature'];
                }
                if (isset($gm['moisture']) && is_numeric($gm['moisture'])) {
                    $record['moisture'] = (float) $gm['moisture'];
                }
                if (isset($gm['humidity']) && is_numeric($gm['humidity'])) {
                    $record['humidity'] = (float) $gm['humidity'];
                }
                if (isset($gm['phValue']) && is_numeric($gm['phValue'])) {
                    $record['phValue'] = (float) $gm['phValue'];
                }
                if (!empty($gm['lightConditions'])) {
                    $record['lightConditions'] = (string) $gm['lightConditions'];
                }
                if (!empty($gm['remarks'])) {
                    $record['remarks'] = (string) $gm['remarks'];
                }

                $groundRecords[] = $record;
            }
        }

        if (!empty($groundRecords)) {
            $this->groundMeasurementRepository->create($groundRecords, $context->getContext());
        }

        // Persist plant measurements (1:N — one per plant card)
        $plantMeasurementsPayload = $data['plantMeasurements'] ?? [];
        $plantRecords = [];

        if (is_array($plantMeasurementsPayload)) {
            foreach ($plantMeasurementsPayload as $pm) {
                $plantId = !empty($pm['plantId']) ? $pm['plantId'] : null;
                $customPlantName = isset($pm['customPlantName']) ? trim((string) $pm['customPlantName']) : null;

                // Skip empty entries (no plant reference and no custom name)
                if ($plantId === null && empty($customPlantName)) {
                    continue;
                }

                $record = [
                    'id' => Uuid::randomHex(),
                    'observationId' => $observationId,
                    'plantId' => $plantId,
                ];

                if (!empty($customPlantName)) {
                    $record['customPlantName'] = $customPlantName;
                }
                if (isset($pm['heightCm']) && is_numeric($pm['heightCm'])) {
                    $record['heightCm'] = (int) $pm['heightCm'];
                }
                if (isset($pm['trunkCircumferenceCm']) && is_numeric($pm['trunkCircumferenceCm'])) {
                    $record['trunkCircumferenceCm'] = (float) $pm['trunkCircumferenceCm'];
                }
                if (isset($pm['crownDiameterCm']) && is_numeric($pm['crownDiameterCm'])) {
                    $record['crownDiameterCm'] = (int) $pm['crownDiameterCm'];
                }
                if (!empty($pm['remarks'])) {
                    $record['remarks'] = trim((string) $pm['remarks']);
                }

                $plantRecords[] = $record;
            }
        }

        if (!empty($plantRecords)) {
            $this->plantMeasurementRepository->create($plantRecords, $context->getContext());
        }

        // Build items map for the JS to correlate speciesId/clientId → itemId for photo uploads
        $itemsForResponse = array_map(fn ($r) => [
            'speciesId' => $r['speciesId'],
            'subspeciesId' => $r['subspeciesId'],
            'clientId' => $r['_clientId'] ?? null,
            'itemId' => $r['id'],
        ], $itemRecords);

        // Resolve miniwald name for the notification
        $miniwaldName = '';
        if (!empty($miniwaldId)) {
            $miniwaldCriteria = new Criteria([$miniwaldId]);
            $miniwaldResult = $this->miniwaldRepository->search($miniwaldCriteria, $context->getContext())->first();
            if ($miniwaldResult instanceof MiniwaldEntity) {
                $miniwaldName = $miniwaldResult->getName() ?? '';
            }
        }

        // Dispatch Flow event (kept for extensibility)
        $adminEmail = (string) ($this->systemConfigService->get('MiniwaldExplorer.config.adminNotificationEmail') ?? '');
        $event = new ObservationSubmittedEvent(
            $context,
            $observationId,
            $customer,
            $miniwaldName,
            $observedAtDate,
            $weather ?: null,
            $wind ?: null,
            count($itemRecords),
            $adminEmail,
        );
        $this->eventDispatcher->dispatch($event, ObservationSubmittedEvent::EVENT_NAME);

        return new JsonResponse([
            'success' => true,
            'observationId' => $observationId,
            'items' => $itemsForResponse,
        ]);
    }

    #[Route(
        path: '/observation/upload-photo',
        name: 'frontend.miniwald.observation.upload_photo',
        methods: ['POST'],
    )]
    public function uploadPhoto(Request $request, SalesChannelContext $context): JsonResponse
    {
        $customer = $context->getCustomer();
        if ($customer === null) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $observationItemId = $request->request->getString('observationItemId');
        $photoId = $request->request->getString('photoId');
        $file = $request->files->get('file');

        if (!Uuid::isValid($observationItemId) || ($photoId !== '' && !Uuid::isValid($photoId)) || $file === null) {
            return new JsonResponse(['error' => 'Missing or invalid observationItemId, photoId or file'], Response::HTTP_BAD_REQUEST);
        }

        // Cached older storefront bundles do not send a photoId. Their uploads
        // keep working, while new bundles provide a stable id across retries.
        $photoId = $photoId ?: Uuid::randomHex();

        $ctx = $context->getContext();
        $itemCriteria = (new Criteria([$observationItemId]))->addAssociation('observation');
        $item = $this->observationItemRepository->search($itemCriteria, $ctx)->first();
        if ($item === null || $item->getObservation()?->getCustomerId() !== $customer->getId()) {
            return new JsonResponse(['error' => 'Observation item not found'], Response::HTTP_NOT_FOUND);
        }

        // The client keeps this id for every retry. A lost response can therefore
        // be answered without creating another photo or media record.
        $existing = $this->photoRepository->search(new Criteria([$photoId]), $ctx)->first();
        if ($existing !== null) {
            if ($existing->getObservationItemId() !== $observationItemId) {
                return new JsonResponse(['error' => 'Photo id already used'], Response::HTTP_CONFLICT);
            }

            return new JsonResponse(['success' => true, 'photoId' => $photoId]);
        }

        // Validate mime type — only JPEG and PNG are accepted.
        // GD can strip EXIF from these two formats; other formats (webp, heic, gif)
        // cannot be reliably sanitized server-side with the current GD pipeline.
        // The frontend canvas already converts everything to JPEG before upload,
        // so this list covers all legitimate upload paths.
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
        $mimeType = $file->getMimeType() ?? '';
        if (!in_array($mimeType, $allowedMimes, true)) {
            return new JsonResponse(['error' => 'Invalid file type'], Response::HTTP_BAD_REQUEST);
        }

        // Max 20 MB safety net (client-side canvas resize handles most cases before this)
        if ($file->getSize() > 20 * 1024 * 1024) {
            return new JsonResponse(['error' => 'File too large'], Response::HTTP_BAD_REQUEST);
        }

        // Resolve media folder
        $folderId = $this->mediaFolderService->getFolderIdByPath('MiniwaldExplorer/Observations', $ctx);

        // Create a media entity
        $mediaId = Uuid::randomHex();
        $mediaData = ['id' => $mediaId];
        if ($folderId !== null) {
            $mediaData['mediaFolderId'] = $folderId;
        }
        $this->mediaRepository->create([$mediaData], $ctx);

        // Persist the file via FileSaver
        // Use $mediaId as destination filename to guarantee uniqueness (no duplicate-name errors)
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $mediaFile = new MediaFile(
            $file->getPathname(),
            $mimeType,
            $extension,
            $file->getSize(),
        );

        try {
            $this->fileSaver->persistFileToMedia($mediaFile, $mediaId, $mediaId, $ctx);
        } catch (\Exception $e) {
            // Clean up orphaned media record on failure
            $this->mediaRepository->delete([['id' => $mediaId]], $ctx);

            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Concurrent retries can both pass the first lookup. The photo primary
        // key permits only one insert; the loser removes its own unused media.
        try {
            $this->photoRepository->create([[
                'id' => $photoId,
                'observationItemId' => $observationItemId,
                'mediaId' => $mediaId,
                'sortOrder' => 0,
                'isApproved' => false,
            ]], $ctx);
        } catch (\Throwable) {
            $this->mediaRepository->delete([['id' => $mediaId]], $ctx);
            $existing = $this->photoRepository->search(new Criteria([$photoId]), $ctx)->first();
            if ($existing !== null && $existing->getObservationItemId() === $observationItemId) {
                return new JsonResponse(['success' => true, 'photoId' => $photoId]);
            }

            return new JsonResponse(['error' => 'Photo upload failed'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse(['success' => true, 'photoId' => $photoId]);
    }

    /**
     * Resolve the subspecies ids submitted with the items in a single query.
     *
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<string, string> subspecies id => species id of its parent
     */
    private function resolveSubspecies(array $items, SalesChannelContext $context): array
    {
        $ids = [];
        foreach ($items as $item) {
            $subspeciesId = $item['subspeciesId'] ?? null;
            if (\is_string($subspeciesId) && Uuid::isValid($subspeciesId)) {
                $ids[$subspeciesId] = true;
            }
        }

        if (empty($ids)) {
            return [];
        }

        $criteria = new Criteria(array_keys($ids));
        $criteria->addFilter(new EqualsFilter('active', true));

        $found = $this->subspeciesRepository
            ->search($criteria, $context->getContext())
            ->getEntities();

        $map = [];
        /** @var SubspeciesEntity $subspecies */
        foreach ($found as $subspecies) {
            $parentSpeciesId = $subspecies->getSpeciesId();
            if ($parentSpeciesId !== null) {
                $map[$subspecies->getId()] = $parentSpeciesId;
            }
        }

        return $map;
    }
}
