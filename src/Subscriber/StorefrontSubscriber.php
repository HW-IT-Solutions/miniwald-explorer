<?php declare(strict_types=1);

namespace InReNa\Subscriber;

use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Blocks e-commerce routes (cart, checkout, product listings, search, wishlist)
 * since MiniwaldExplorer is a Citizen Science app, not a shop.
 *
 * Also redirects post-login and post-register to frontend.miniwald.observation.
 * Also injects pageTitle for Shopware-owned routes (login/register).
 */
class StorefrontSubscriber implements EventSubscriberInterface
{
    /**
     * Route name prefixes that should be blocked.
     */
    private const BLOCKED_ROUTE_PREFIXES = [
        'frontend.checkout.',
        'frontend.cart.',
        'frontend.detail.',       // Product detail pages
        'frontend.search.',       // Search functionality
        'frontend.wishlist.',     // Wishlist
        'frontend.cookie.',       // Cookie consent for shop features
        'store-api.checkout.',
        'store-api.cart.',
        'store-api.product.',
        'store-api.wishlist.',
    ];

    /**
     * After login or register, Shopware redirects to frontend.account.home.page.
     * We intercept that redirect and send the user to frontend.miniwald.observation instead.
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
            KernelEvents::RESPONSE => ['onKernelResponse', 10],
            StorefrontRenderEvent::class => ['onStorefrontRender', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route', '');

        if ($route === null || $route === '') {
            return;
        }

        foreach (self::BLOCKED_ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($route, $prefix)) {
                throw new NotFoundHttpException('This page is not available.');
            }
        }
    }

    public function onStorefrontRender(StorefrontRenderEvent $event): void
    {
        // Skip if pageTitle already set by our own controllers
        if ($event->getParameter('pageTitle') !== null) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route', '');
        $appName = $this->translator->trans('miniwaldExplorer.meta.appName');

        $titleKey = match ($route) {
            'frontend.account.login.page', 'frontend.account.register.page' => 'miniwaldExplorer.meta.pageTitleLogin',
            'frontend.miniwald.profile', 'frontend.miniwald.profile.password', 'frontend.miniwald.profile.delete' => 'miniwaldExplorer.profile.pageTitle',
            default => null,
        };

        if ($titleKey !== null) {
            $event->setParameter('pageTitle', $this->translator->trans($titleKey) . ' | ' . $appName);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        // Only intercept redirects to the account home page (post-login / post-register / post-logout)
        if (!$response instanceof RedirectResponse) {
            return;
        }

        $targetUrl = $response->getTargetUrl();
        $request = $event->getRequest();
        $route = $request->attributes->get('_route', '');

        // Post-logout: Shopware redirects to the home page.
        // If the logout link carried a returnUrl query param, redirect there instead.
        if ($route === 'frontend.account.logout.page') {
            $returnUrl = $request->query->get('returnUrl', '');
            if ($this->isSafeReturnUrl($returnUrl)) {
                $event->setResponse(new RedirectResponse($returnUrl));

                return;
            }
            // Default post-logout: go to observation page
            $event->setResponse(new RedirectResponse($this->router->generate('frontend.miniwald.observation')));

            return;
        }

        // Post-login / post-register: Shopware redirects to /account.
        // If the login form carried a returnUrl hidden field, redirect there instead.
        if (str_contains($targetUrl, '/account') && !str_contains($targetUrl, '/account/login') && !str_contains($targetUrl, '/account/register')) {
            $returnUrl = $request->request->get('returnUrl', '');

            if ($this->isSafeReturnUrl($returnUrl)) {
                $event->setResponse(new RedirectResponse($returnUrl));
            } else {
                $event->setResponse(new RedirectResponse($this->router->generate('frontend.miniwald.observation')));
            }
        }
    }

    /**
     * Validates that a returnUrl is safe to redirect to:
     * - Must be a non-empty string
     * - Must start with / (relative URL, no protocol)
     * - Must not contain // (prevents protocol-relative URLs like //evil.com)
     * - Must not point to /account (prevents redirect loops)
     * - Must not contain newlines (header injection prevention)
     */
    private function isSafeReturnUrl(mixed $returnUrl): bool
    {
        if (!is_string($returnUrl) || $returnUrl === '') {
            return false;
        }
        if (!str_starts_with($returnUrl, '/')) {
            return false;
        }
        if (str_contains($returnUrl, '//')) {
            return false;
        }
        if (str_contains($returnUrl, "\n") || str_contains($returnUrl, "\r")) {
            return false;
        }
        if (str_starts_with($returnUrl, '/account')) {
            return false;
        }

        return true;
    }
}
