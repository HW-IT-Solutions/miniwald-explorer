<?php declare(strict_types=1);

namespace InReNa\Controller\Storefront;

use InReNa\Content\ObservationSheet\ObservationSheetDefinition;
use InReNa\Content\ObservationSheet\ObservationSheetEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Renders the public /download page listing all uploaded observation sheet PDFs.
 *
 * Only sheets that have an associated media (PDF uploaded) are shown.
 * Order: animal sheets sorted by species_category.position, then plant, then ground.
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class DownloadController extends StorefrontController
{
    public function __construct(
        private readonly EntityRepository $observationSheetRepository,
    ) {
    }

    #[Route(path: '/download', name: 'frontend.miniwald.download', methods: ['GET'])]
    public function index(SalesChannelContext $context): Response
    {
        $frameworkContext = $context->getContext();

        // Load all sheets that have a media assigned, with associations
        $criteria = new Criteria();
        $criteria->addFilter(
            new NotFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('mediaId', null),
            ])
        );
        $criteria->addAssociation('media');
        $criteria->addAssociation('speciesCategory');
        $criteria->addAssociation('speciesCategory.image');

        $sheets = $this->observationSheetRepository->search($criteria, $frameworkContext);

        // Build structured card list: animal (sorted by category position), then plant, then ground
        $animalCards = [];
        $plantCard = null;
        $groundCard = null;

        foreach ($sheets as $sheet) {
            /** @var ObservationSheetEntity $sheet */
            $type = $sheet->getType();

            if ($type === ObservationSheetDefinition::TYPE_ANIMAL) {
                $category = $sheet->getSpeciesCategory();
                $position = $category?->getPosition() ?? 999;
                $animalCards[$position] = [
                    'type' => 'animal',
                    'name' => $category?->getTranslation('name') ?? $category?->getName() ?? '',
                    'imageUrl' => $category?->getImage()?->getUrl(),
                    'slug' => $this->slugify($category?->getTranslation('name') ?? $category?->getName() ?? ''),
                    'position' => $position,
                ];
            } elseif ($type === ObservationSheetDefinition::TYPE_PLANT) {
                $plantCard = [
                    'type' => 'plant',
                    'name' => null, // filled by snippet in Twig
                    'imageUrl' => null, // static asset used in Twig
                    'slug' => null,
                ];
            } elseif ($type === ObservationSheetDefinition::TYPE_GROUND) {
                $groundCard = [
                    'type' => 'ground',
                    'name' => null,
                    'imageUrl' => null,
                    'slug' => null,
                ];
            }
        }

        ksort($animalCards);

        $cards = array_values($animalCards);
        if ($plantCard !== null) {
            $cards[] = $plantCard;
        }
        if ($groundCard !== null) {
            $cards[] = $groundCard;
        }

        $appName = $this->trans('miniwaldExplorer.meta.appName');

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/download/index.html.twig',
            [
                'cards' => $cards,
                'pageTitle' => $this->trans('miniwaldExplorer.meta.pageTitleDownload') . ' | ' . $appName,
            ]
        );
    }

    /**
     * Convert a string to a URL-friendly slug (mirrors PdfController::slugify).
     */
    private function slugify(string $text): string
    {
        $text = str_replace(
            ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'],
            ['ae', 'oe', 'ue', 'ae', 'oe', 'ue', 'ss'],
            $text
        );
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        return trim($text, '-');
    }
}
