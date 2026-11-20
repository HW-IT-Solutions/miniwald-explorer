<?php declare(strict_types=1);

namespace InReNa\Controller\Storefront;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves static CMS content pages (Impressum, Datenschutz, FAQ, Kontakt, Mitmachen).
 *
 * Routes are defined here so that path('miniwald.impressum') etc. work in Twig.
 * The GenericPageLoader provides the standard page context (header, footer, meta).
 * Actual page content is rendered by the default Shopware CMS page template.
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class ContentController extends StorefrontController
{
    public function __construct(
        private readonly GenericPageLoaderInterface $genericPageLoader,
    ) {
    }

    #[Route(path: '/impressum', name: 'miniwald.impressum', methods: ['GET'])]
    public function impressum(Request $request, SalesChannelContext $context): Response
    {
        return $this->contentPage($request, $context);
    }

    #[Route(path: '/datenschutz', name: 'miniwald.datenschutz', methods: ['GET'])]
    public function datenschutz(Request $request, SalesChannelContext $context): Response
    {
        return $this->contentPage($request, $context);
    }

    #[Route(path: '/faq', name: 'miniwald.faq', methods: ['GET'])]
    public function faq(Request $request, SalesChannelContext $context): Response
    {
        return $this->contentPage($request, $context);
    }

    #[Route(path: '/kontakt', name: 'miniwald.kontakt', methods: ['GET'])]
    public function kontakt(Request $request, SalesChannelContext $context): Response
    {
        return $this->contentPage($request, $context);
    }

    #[Route(path: '/mitmachen', name: 'miniwald.mitmachen', methods: ['GET'])]
    public function mitmachen(Request $request, SalesChannelContext $context): Response
    {
        return $this->contentPage($request, $context);
    }

    #[Route(path: '/barrierefreiheit', name: 'miniwald.barrierefreiheit', methods: ['GET'])]
    public function barrierefreiheit(Request $request, SalesChannelContext $context): Response
    {
        return $this->contentPage($request, $context);
    }

    #[Route(path: '/ueber-das-projekt', name: 'miniwald.ueber-das-projekt', methods: ['GET'])]
    public function ueberDasProjekt(Request $request, SalesChannelContext $context): Response
    {
        return $this->contentPage($request, $context);
    }

    #[Route(
        path: '/overview',
        name: 'frontend.miniwald.overview',
        methods: ['GET'],
    )]
    public function overview(Request $request, SalesChannelContext $context): Response
    {
        $appName = $this->trans('miniwaldExplorer.meta.appName');

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/overview/index.html.twig',
            ['pageTitle' => $this->trans('miniwaldExplorer.meta.pageTitleHome') . ' | ' . $appName]
        );
    }

    private function contentPage(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->genericPageLoader->load($request, $context);

        return $this->renderStorefront(
            '@Storefront/storefront/page/content/index.html.twig',
            ['page' => $page]
        );
    }
}
