<?php declare(strict_types=1);

namespace InReNa\Controller\Storefront;

use InReNa\Content\ObservationItem\ObservationItemEntity;
use InReNa\Service\CsvExportService;
use InReNa\Service\Observation\ObservationFilter;
use InReNa\Service\Observation\ObservationQueryService;
use InReNa\Service\Observation\ObservationView;
use InReNa\Service\Observation\PublicFilterInput;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CollectionController extends StorefrontController
{
    private const LAST_OBSERVATIONS_LIMIT = 10;
    private const OBSERVATIONS_PER_PAGE = 20;

    public function __construct(
        private readonly ObservationQueryService $observationQueryService,
        private readonly EntityRepository $miniwaldRepository,
        private readonly EntityRepository $speciesCategoryRepository,
        private readonly EntityRepository $speciesRepository,
        private readonly EntityRepository $plantRepository,
        private readonly EntityRepository $observationItemRepository,
        private readonly CsvExportService $csvExportService,
    ) {
    }

    #[Route(path: '/collection', name: 'frontend.miniwald.collection', methods: ['GET'])]
    public function index(Request $request, SalesChannelContext $context): Response
    {
        $ctx = $context->getContext();

        $result = $this->observationQueryService->query(
            new ObservationFilter(
                limit: self::LAST_OBSERVATIONS_LIMIT,
                statuses: ['submitted', 'verified'],
            ),
            $ctx
        );

        $miniwaldCriteria = (new Criteria())
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            ->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
        $miniwaelder = $this->miniwaldRepository->search($miniwaldCriteria, $ctx)->getEntities();

        $categoryCriteria = (new Criteria())
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            ->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));
        $categories = $this->speciesCategoryRepository->search($categoryCriteria, $ctx)->getEntities();

        $appName = $this->trans('miniwaldExplorer.meta.appName');

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/collection/index.html.twig',
            [
                'observations' => $result->items,
                'miniwaelder' => $miniwaelder,
                'categories' => $categories,
                'pageTitle' => $this->trans('miniwaldExplorer.meta.pageTitleCollection') . ' | ' . $appName,
            ]
        );
    }

    #[Route(path: '/collection/observations/export.csv', name: 'frontend.miniwald.collection.observations.export.csv', methods: ['GET'])]
    public function exportObservationsCsv(Request $request, SalesChannelContext $context): Response
    {
        $query = $request->query->all();
        $filters = [
            'miniwaldId' => PublicFilterInput::miniwald($query['miniwald'] ?? null),
            'dateFrom' => PublicFilterInput::date($query['dateFrom'] ?? null),
            'dateTo' => PublicFilterInput::date($query['dateTo'] ?? null),
        ];

        return $this->csvExportService->export(
            $filters,
            $request->getSchemeAndHttpHost(),
            $context->getContext(),
            true // anonymize: omit observer_name for public storefront export
        );
    }

    #[Route(path: '/collection/observations', name: 'frontend.miniwald.collection.observations', methods: ['GET'])]
    public function observations(Request $request, SalesChannelContext $context): Response
    {
        $ctx = $context->getContext();

        $obsData = $this->buildObservationViewData($request, $context);

        $categoryCriteria = (new Criteria())
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));
        $categories = $this->speciesCategoryRepository->search($categoryCriteria, $ctx)->getEntities();

        $miniwaldCriteria = (new Criteria())
            ->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
        $miniwaelder = $this->miniwaldRepository->search($miniwaldCriteria, $ctx)->getEntities();

        $appName = $this->trans('miniwaldExplorer.meta.appName');

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/collection/observations.html.twig',
            array_merge($obsData, [
                'categories' => $categories,
                'miniwaelder' => $miniwaelder,
                'pageTitle' => $this->trans('miniwaldExplorer.meta.pageTitleObservations') . ' | ' . $appName,
            ])
        );
    }

    #[Route(path: '/collection/observations/{id}', name: 'frontend.miniwald.collection.observation.detail', methods: ['GET'])]
    public function observationDetail(string $id, Request $request, SalesChannelContext $context): Response
    {
        if (!Uuid::isValid($id)) {
            return $this->redirectMissingObservation();
        }

        $ctx = $context->getContext();

        $criteria = (new Criteria([$id]))
            ->addAssociation('species')
            ->addAssociation('species.image')
            ->addAssociation('species.image.thumbnails')
            ->addAssociation('species.speciesCategory')
            ->addAssociation('subspecies')
            ->addAssociation('photos')
            ->addAssociation('photos.media')
            ->addAssociation('photos.media.thumbnails')
            ->addAssociation('observation')
            ->addAssociation('observation.miniwald')
            ->addAssociation('observation.customer');

        /** @var ObservationItemEntity|null $item */
        $item = $this->observationItemRepository->search($criteria, $ctx)->first();
        if ($item === null || !in_array($item->getObservation()?->getStatus(), ['submitted', 'verified'], true)) {
            return $this->redirectMissingObservation();
        }

        $appName = $this->trans('miniwaldExplorer.meta.appName');
        $speciesName = $item->getSubspecies()?->getTranslation('name')
            ?? $item->getSpecies()?->getTranslation('name')
            ?? $item->getSpecies()?->getName()
            ?? $this->trans('miniwaldExplorer.meta.pageTitleObservations');

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/collection/observation-detail.html.twig',
            [
                'item' => $item,
                'pageTitle' => $speciesName . ' | ' . $appName,
            ]
        );
    }

    #[Route(path: '/collection/mini-forests/{id}', name: 'frontend.miniwald.collection.miniforest', methods: ['GET'])]
    public function miniforestDetail(string $id, Request $request, SalesChannelContext $context): Response
    {
        $ctx = $context->getContext();

        $criteria = (new Criteria([$id]))
            ->addAssociation('image')
            ->addAssociation('image.thumbnails');
        /** @var \InReNa\Content\Miniwald\MiniwaldEntity|null $miniwald */
        $miniwald = $this->miniwaldRepository->search($criteria, $ctx)->first();
        if ($miniwald === null) {
            throw new NotFoundHttpException('Mini forest not found.');
        }

        $obsData = $this->buildObservationViewData($request, $context, fixedMiniwaldId: $id);

        $appName = $this->trans('miniwaldExplorer.meta.appName');
        $miniwaldName = $miniwald->getTranslation('name') ?? $miniwald->getName() ?? '';

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/collection/miniforest-detail.html.twig',
            array_merge($obsData, [
                'miniwald' => $miniwald,
                'pageTitle' => $miniwaldName . ' | ' . $appName,
            ])
        );
    }

    #[Route(path: '/collection/species/{id}', name: 'frontend.miniwald.collection.category', methods: ['GET'])]
    public function categoryDetail(string $id, Request $request, SalesChannelContext $context): Response
    {
        $ctx = $context->getContext();

        $categoryCriteria = (new Criteria([$id]))
            ->addAssociation('image')
            ->addAssociation('image.thumbnails');
        /** @var \InReNa\Content\SpeciesCategory\SpeciesCategoryEntity|null $category */
        $category = $this->speciesCategoryRepository->search($categoryCriteria, $ctx)->first();
        if ($category === null) {
            throw new NotFoundHttpException('Species category not found.');
        }

        $speciesCriteria = (new Criteria())
            ->addFilter(new EqualsFilter('speciesCategoryId', $id))
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            ->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));
        $species = $this->speciesRepository->search($speciesCriteria, $ctx)->getEntities();

        $obsData = $this->buildObservationViewData($request, $context, fixedCategoryId: $id);

        $appName = $this->trans('miniwaldExplorer.meta.appName');
        $categoryName = $category->getTranslation('name') ?? $category->getName() ?? '';

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/collection/category-detail.html.twig',
            array_merge($obsData, [
                'category' => $category,
                'species' => $species,
                'pageTitle' => $categoryName . ' | ' . $appName,
            ])
        );
    }

    #[Route(path: '/collection/species/{categoryId}/{speciesId}', name: 'frontend.miniwald.collection.species.detail', methods: ['GET'])]
    public function speciesDetail(string $categoryId, string $speciesId, Request $request, SalesChannelContext $context): Response
    {
        $ctx = $context->getContext();

        $criteria = (new Criteria([$speciesId]))
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            ->addAssociation('speciesCategory')
            ->addAssociation('subspecies')
            ->addAssociation('subspecies.image')
            ->addAssociation('subspecies.image.thumbnails');
        /** @var \InReNa\Content\Species\SpeciesEntity|null $species */
        $species = $this->speciesRepository->search($criteria, $ctx)->first();
        if ($species === null || $species->getSpeciesCategoryId() !== $categoryId) {
            throw new NotFoundHttpException('Species not found.');
        }

        $obsData = $this->buildObservationViewData($request, $context, fixedSpeciesId: $speciesId);

        $appName = $this->trans('miniwaldExplorer.meta.appName');
        $speciesName = $species->getTranslation('name') ?? $species->getName() ?? '';

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/collection/species-detail.html.twig',
            array_merge($obsData, [
                'species' => $species,
                'pageTitle' => $speciesName . ' | ' . $appName,
            ])
        );
    }

    #[Route(path: '/collection/mini-forests', name: 'frontend.miniwald.collection.mini-forests', methods: ['GET'])]
    public function miniForests(Request $request, SalesChannelContext $context): Response
    {
        $ctx = $context->getContext();

        $miniwaldCriteria = (new Criteria())
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            ->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
        $miniwaelder = $this->miniwaldRepository->search($miniwaldCriteria, $ctx)->getEntities();

        $appName = $this->trans('miniwaldExplorer.meta.appName');

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/collection/mini-forests.html.twig',
            [
                'miniwaelder' => $miniwaelder,
                'pageTitle' => $this->trans('miniwaldExplorer.meta.pageTitleMiniForests') . ' | ' . $appName,
            ]
        );
    }

    #[Route(path: '/collection/species-overview', name: 'frontend.miniwald.collection.species-overview', methods: ['GET'])]
    public function speciesOverview(Request $request, SalesChannelContext $context): Response
    {
        $ctx = $context->getContext();

        $categoryCriteria = (new Criteria())
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            ->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));
        $categories = $this->speciesCategoryRepository->search($categoryCriteria, $ctx)->getEntities();

        $appName = $this->trans('miniwaldExplorer.meta.appName');

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/collection/species-overview.html.twig',
            [
                'categories' => $categories,
                'pageTitle' => $this->trans('miniwaldExplorer.meta.pageTitleSpeciesOverview') . ' | ' . $appName,
            ]
        );
    }

    #[Route(path: '/collection/plants', name: 'frontend.miniwald.collection.plants', methods: ['GET'])]
    public function plants(Request $request, SalesChannelContext $context): Response
    {
        $ctx = $context->getContext();

        $miniwaldCriteria = (new Criteria())
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            ->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
        $miniwaelder = $this->miniwaldRepository->search($miniwaldCriteria, $ctx)->getEntities();

        $selectedMiniwaldId = (string) $request->query->get('miniwald', '') ?: null;

        $selectedMiniwald = null;
        $plants = null;

        if ($selectedMiniwaldId !== null) {
            // Verify the miniwald exists
            $mwCriteria = new Criteria([$selectedMiniwaldId]);
            /** @var \InReNa\Content\Miniwald\MiniwaldEntity|null $selectedMiniwald */
            $selectedMiniwald = $this->miniwaldRepository->search($mwCriteria, $ctx)->first();

            if ($selectedMiniwald !== null) {
                $plantCriteria = (new Criteria())
                    ->addFilter(new EqualsFilter('active', true))
                    ->addFilter(new EqualsFilter('miniwaldId', $selectedMiniwaldId))
                    ->addAssociation('image')
                    ->addAssociation('image.thumbnails')
                    ->addSorting(new FieldSorting('sortOrder', FieldSorting::ASCENDING));
                $plants = $this->plantRepository->search($plantCriteria, $ctx)->getEntities();
            }
        }

        $appName = $this->trans('miniwaldExplorer.meta.appName');

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/collection/plants.html.twig',
            [
                'miniwaelder' => $miniwaelder,
                'selectedMiniwald' => $selectedMiniwald,
                'plants' => $plants,
                'pageTitle' => $this->trans('miniwaldExplorer.meta.pageTitlePlants') . ' | ' . $appName,
            ]
        );
    }

    #[Route(path: '/collection/plants/{id}', name: 'frontend.miniwald.collection.plant.detail', methods: ['GET'])]
    public function plantDetail(string $id, Request $request, SalesChannelContext $context): Response
    {
        $ctx = $context->getContext();

        $criteria = (new Criteria([$id]))
            ->addAssociation('image')
            ->addAssociation('image.thumbnails')
            ->addAssociation('miniwald');
        /** @var \InReNa\Content\Plant\PlantEntity|null $plant */
        $plant = $this->plantRepository->search($criteria, $ctx)->first();
        if ($plant === null) {
            throw new NotFoundHttpException('Plant not found.');
        }

        $obsData = $this->buildObservationViewData($request, $context, fixedPlantId: $id);

        $appName = $this->trans('miniwaldExplorer.meta.appName');
        $plantName = $plant->getTranslation('name') ?? $plant->getName() ?? '';

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/collection/plant-detail.html.twig',
            array_merge($obsData, [
                'plant' => $plant,
                'pageTitle' => $plantName . ' | ' . $appName,
            ])
        );
    }

    private function redirectMissingObservation(): Response
    {
        $this->addFlash('warning', $this->trans('miniwaldExplorer.collection.observationNotFound'));

        return $this->redirectToRoute('frontend.miniwald.collection.observations');
    }

    /**
     * Builds the observation view data array shared by all actions that display
     * a paginated, filtered observation list.
     *
     * Fixed filter parameters (miniwald / category / species / plant) are set by the
     * caller and cannot be overridden via query string. All other filters
     * (dateFrom, dateTo, order, page, onlyOwn) are read from the request.
     *
     * Returns:
     *   observations  - ObservationView[]
     *   total         - int
     *   page          - int
     *   totalPages    - int
     *   filters       - array{category, miniwald, dateFrom, dateTo, order, onlyOwn}
     *
     * @return array{observations: list<ObservationView>, total: int, page: int, totalPages: int, filters: array<string, mixed>}
     */
    private function buildObservationViewData(
        Request $request,
        SalesChannelContext $context,
        ?string $fixedMiniwaldId = null,
        ?string $fixedCategoryId = null,
        ?string $fixedSpeciesId = null,
        ?string $fixedPlantId = null,
    ): array {
        $ctx = $context->getContext();

        $query = $request->query->all();
        $page = PublicFilterInput::page($query['page'] ?? 1);
        $dateFrom = PublicFilterInput::date($query['dateFrom'] ?? null);
        $dateTo = PublicFilterInput::date($query['dateTo'] ?? null);
        $order = $query['order'] ?? null;
        $orderStr = is_string($order) && strtoupper($order) === 'ASC'
            ? FieldSorting::ASCENDING : FieldSorting::DESCENDING;

        // When a fixed filter is provided the query-string value is ignored.
        $miniwaldId = $fixedMiniwaldId ?? PublicFilterInput::miniwald($query['miniwald'] ?? null);
        $categoryParam = $fixedCategoryId ?? (($query['category'] ?? null) === 'plants'
            ? 'plants' : PublicFilterInput::uuid($query['category'] ?? null));

        // 'plants' is a pseudo-category (plants are not a SpeciesCategory): it selects
        // plant measurements instead of a species category.
        $plantsOnly = $categoryParam === 'plants';
        $categoryId = $plantsOnly ? null : $categoryParam;
        $speciesId = $fixedSpeciesId ?? PublicFilterInput::uuid($query['species'] ?? null);
        $plantId = $fixedPlantId;

        // "Only own observations" filter: only active when user is logged in and param is set.
        $customer = $context->getCustomer();
        $onlyOwn = $customer !== null && in_array($query['onlyOwn'] ?? null, ['1', 1, true], true);
        $customerId = $onlyOwn ? $customer->getId() : null;

        $filter = new ObservationFilter(
            limit: self::OBSERVATIONS_PER_PAGE,
            offset: ($page - 1) * self::OBSERVATIONS_PER_PAGE,
            miniwaldId: $miniwaldId,
            speciesCategoryId: $categoryId,
            speciesId: $speciesId,
            plantId: $plantId,
            plantsOnly: $plantsOnly,
            customerId: $customerId,
            dateFrom: $dateFrom !== null ? new \DateTimeImmutable($dateFrom) : null,
            dateTo: $dateTo !== null ? new \DateTimeImmutable($dateTo) : null,
            order: $orderStr,
            statuses: ['submitted', 'verified'],
        );

        $result = $this->observationQueryService->query($filter, $ctx);
        $totalPages = (int) ceil($result->total / self::OBSERVATIONS_PER_PAGE);

        return [
            'observations' => $result->items,
            'total' => $result->total,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => [
                'category' => $categoryParam ?? '',
                'miniwald' => $miniwaldId ?? '',
                'dateFrom' => $dateFrom ?? '',
                'dateTo' => $dateTo ?? '',
                'order' => strtolower($orderStr),
                'onlyOwn' => $onlyOwn,
            ],
        ];
    }
}
