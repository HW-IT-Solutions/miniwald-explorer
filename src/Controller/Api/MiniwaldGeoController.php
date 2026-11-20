<?php declare(strict_types=1);

namespace InReNa\Controller\Api;

use InReNa\Service\MiniwaldGeoService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public storefront endpoint for GPS-based Miniwald detection.
 *
 * Accepts the observer's GPS coordinates and returns the nearest Miniwald
 * if the observer is within its configured detection radius.
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class MiniwaldGeoController extends AbstractController
{
    public function __construct(
        private readonly MiniwaldGeoService $miniwaldGeoService
    ) {
    }

    /**
     * Detect the nearest Miniwald based on GPS coordinates.
     *
     * Request body (JSON):
     *   { "latitude": 50.901600, "longitude": 6.230825 }
     *
     * Response (match found):
     *   { "miniwaldId": "...", "name": "Aldenhoven", "distance": 42.5 }
     *
     * Response (no match):
     *   { "miniwaldId": null }
     */
    #[Route(
        path: '/miniwald/detect',
        name: 'frontend.miniwald.geo.detect',
        methods: ['POST'],
    )]
    public function detect(Request $request, Context $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(
                ['error' => 'Invalid JSON body'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return new JsonResponse(
                ['error' => 'latitude and longitude are required numeric fields'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return new JsonResponse(
                ['error' => 'latitude must be between -90 and 90, longitude between -180 and 180'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $result = $this->miniwaldGeoService->findNearestMiniwald($latitude, $longitude, $context);

        if ($result === null) {
            return new JsonResponse(['miniwaldId' => null]);
        }

        return new JsonResponse($result);
    }
}
