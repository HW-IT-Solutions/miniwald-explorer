<?php declare(strict_types=1);

namespace InReNa\Controller\Api;

use InReNa\Content\SpeciesCategory\SpeciesCategoryEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public controller for downloading observation sheet PDFs.
 *
 * These endpoints do NOT require authentication – the PDFs are
 * intended to be freely downloadable from the storefront.
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class PdfController extends AbstractController
{
    public function __construct(
        private readonly EntityRepository $observationSheetRepository,
        private readonly EntityRepository $speciesCategoryRepository,
    ) {
    }

    /**
     * Download a plant or ground observation sheet PDF.
     *
     * @param string $type One of: plant, ground
     */
    #[Route(
        path: '/miniwald/pdf/{type}',
        name: 'frontend.miniwald.pdf.download',
        methods: ['GET'],
        requirements: ['type' => 'plant|ground'],
    )]
    public function download(string $type, Context $context): Response
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', $type));
        $criteria->addAssociation('media');
        $criteria->setLimit(1);

        $sheet = $this->observationSheetRepository->search($criteria, $context)->first();

        return $this->redirectToMedia($sheet, $type);
    }

    /**
     * Download an animal observation sheet PDF by species category slug.
     *
     * The slug is matched against the species category name (case-insensitive,
     * with spaces/special chars replaced by hyphens).
     *
     * @param string $categorySlug URL-friendly slug, e.g. "schmetterlinge"
     */
    #[Route(
        path: '/miniwald/pdf/animal/{categorySlug}',
        name: 'frontend.miniwald.pdf.download.animal',
        requirements: ['categorySlug' => '[a-z0-9\-]+'],
        methods: ['GET'],
    )]
    public function downloadAnimal(string $categorySlug, Context $context): Response
    {
        // Find the species category whose name matches the slug
        $categoryCriteria = new Criteria();
        $categories = $this->speciesCategoryRepository->search($categoryCriteria, $context);

        $matchedCategoryId = null;
        foreach ($categories as $category) {
            /** @var SpeciesCategoryEntity $category */
            $name = $category->getTranslation('name') ?? $category->getName();
            if ($name !== null && $this->slugify($name) === $categorySlug) {
                $matchedCategoryId = $category->getId();
                break;
            }
        }

        if ($matchedCategoryId === null) {
            return new Response('Species category not found: ' . $categorySlug, Response::HTTP_NOT_FOUND);
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', 'animal'));
        $criteria->addFilter(new EqualsFilter('speciesCategoryId', $matchedCategoryId));
        $criteria->addAssociation('media');
        $criteria->setLimit(1);

        $sheet = $this->observationSheetRepository->search($criteria, $context)->first();

        return $this->redirectToMedia($sheet, 'animal/' . $categorySlug);
    }

    /**
     * Redirect to the media URL or return 404.
     */
    private function redirectToMedia(?object $sheet, string $identifier): Response
    {
        if ($sheet === null || $sheet->getMedia() === null) {
            return new Response('PDF not found for: ' . $identifier, Response::HTTP_NOT_FOUND);
        }

        /** @var MediaEntity $media */
        $media = $sheet->getMedia();
        $url = $media->getUrl();

        if (empty($url)) {
            return new Response('PDF media URL not available', Response::HTTP_NOT_FOUND);
        }

        return new RedirectResponse($url);
    }

    /**
     * Convert a string to a URL-friendly slug.
     *
     * Handles German umlauts (ä→ae, ö→oe, ü→ue, ß→ss) and replaces
     * non-alphanumeric characters with hyphens.
     */
    private function slugify(string $text): string
    {
        // German umlaut replacements
        $text = str_replace(
            ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'],
            ['ae', 'oe', 'ue', 'ae', 'oe', 'ue', 'ss'],
            $text
        );

        $text = mb_strtolower($text, 'UTF-8');
        // Replace non-alphanumeric with hyphens
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        // Trim leading/trailing hyphens
        return trim($text, '-');
    }
}
