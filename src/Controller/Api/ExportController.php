<?php declare(strict_types=1);

namespace InReNa\Controller\Api;

use InReNa\Service\CsvExportService;
use InReNa\Service\JsonExportService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API controller for observation data export.
 *
 * Provides CSV and JSON export endpoints with filter support.
 * All endpoints require admin API authentication.
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly CsvExportService $csvExportService,
        private readonly JsonExportService $jsonExportService,
    ) {
    }

    /**
     * Export observations as CSV file download.
     *
     * Only verified observations are exported (hardcoded filter).
     *
     * Query parameters (all optional):
     * - dateFrom: ISO date string
     * - dateTo: ISO date string
     * - type: animals|plants|ground
     * - customerId: UUID
     * - miniwaldId: UUID
     */
    #[Route(
        path: '/api/_action/miniwald/export/csv',
        name: 'api.action.miniwald.export.csv',
        methods: ['GET'],
    )]
    public function exportCsv(Request $request, Context $context): Response
    {
        $filters = $this->extractFilters($request);
        $baseUrl = $this->getBaseUrl($request);

        return $this->csvExportService->export($filters, $baseUrl, $context);
    }

    /**
     * Export observations as JSON (paginated API response).
     *
     * Only verified observations are exported (hardcoded filter).
     *
     * Query parameters (all optional):
     * - dateFrom: ISO date string
     * - dateTo: ISO date string
     * - type: animals|plants|ground
     * - customerId: UUID
     * - miniwaldId: UUID
     * - page: int (default 1)
     * - limit: int (default 100, max 500)
     */
    #[Route(
        path: '/api/_action/miniwald/export/json',
        name: 'api.action.miniwald.export.json',
        methods: ['GET'],
    )]
    public function exportJson(Request $request, Context $context): Response
    {
        $filters = $this->extractFilters($request);
        $baseUrl = $this->getBaseUrl($request);

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(500, max(1, $request->query->getInt('limit', 100)));

        return $this->jsonExportService->export($filters, $baseUrl, $context, $page, $limit);
    }

    /**
     * Export observations as downloadable JSON file.
     *
     * Same filters as the JSON API endpoint, but returns a file download
     * with all matching observations (no pagination).
     */
    #[Route(
        path: '/api/_action/miniwald/export/json-file',
        name: 'api.action.miniwald.export.json.file',
        methods: ['GET'],
    )]
    public function exportJsonFile(Request $request, Context $context): Response
    {
        $filters = $this->extractFilters($request);
        $baseUrl = $this->getBaseUrl($request);

        return $this->jsonExportService->exportFile($filters, $baseUrl, $context);
    }

    /**
     * Extract filter parameters from the request.
     *
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request): array
    {
        return [
            'dateFrom' => $request->query->get('dateFrom'),
            'dateTo' => $request->query->get('dateTo'),
            'type' => $request->query->get('type'),
            'customerId' => $request->query->get('customerId'),
            'miniwaldId' => $request->query->get('miniwaldId'),
        ];
    }

    /**
     * Get the base URL from the request for generating photo URLs.
     */
    private function getBaseUrl(Request $request): string
    {
        return $request->getSchemeAndHttpHost();
    }
}
