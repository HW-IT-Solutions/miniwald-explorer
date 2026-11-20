<?php declare(strict_types=1);

namespace InReNa\Controller\Api;

use InReNa\Content\Photo\PhotoEntity;
use InReNa\Service\PhotoAuthTokenService;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for token-based photo access.
 *
 * Photos are not publicly accessible. Access requires a valid HMAC-SHA256
 * auth token generated during export. Tokens are time-limited (24h).
 *
 * This endpoint does NOT require admin API authentication — the token
 * itself serves as the authentication mechanism.
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class PhotoController extends AbstractController
{
    public function __construct(
        private readonly PhotoAuthTokenService $tokenService,
        private readonly EntityRepository $photoRepository,
    ) {
    }

    /**
     * Serve a photo by ID with token-based authentication.
     *
     * The token must be provided as a query parameter and must be valid
     * (correct HMAC signature and not expired).
     *
     * Returns a redirect to the actual media URL if the token is valid.
     */
    #[Route(
        path: '/api/_action/miniwald/photo/{photoId}',
        name: 'api.action.miniwald.photo',
        methods: ['GET'],
        defaults: ['auth_required' => false],
    )]
    public function getPhoto(string $photoId, Request $request, Context $context): Response
    {
        $token = $request->query->getString('token');

        if (empty($token)) {
            throw new AccessDeniedHttpException('Missing auth token.');
        }

        if (!$this->tokenService->validateToken($photoId, $token)) {
            throw new AccessDeniedHttpException('Invalid or expired auth token.');
        }

        // Load the photo with its media association
        $criteria = new Criteria([$photoId]);
        $criteria->addAssociation('media');

        /** @var PhotoEntity|null $photo */
        $photo = $this->photoRepository->search($criteria, $context)->first();

        if ($photo === null) {
            throw new NotFoundHttpException('Photo not found.');
        }

        /** @var MediaEntity|null $media */
        $media = $photo->getMedia();

        if ($media === null) {
            throw new NotFoundHttpException('Media file not found.');
        }

        $url = $media->getUrl();

        if (empty($url)) {
            throw new NotFoundHttpException('Media URL not available.');
        }

        // Redirect to the actual media URL
        return new RedirectResponse($url, Response::HTTP_FOUND);
    }
}
