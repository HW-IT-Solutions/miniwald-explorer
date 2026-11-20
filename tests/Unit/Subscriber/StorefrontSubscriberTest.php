<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Subscriber;

use InReNa\Subscriber\StorefrontSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(StorefrontSubscriber::class)]
class StorefrontSubscriberTest extends TestCase
{
    private StorefrontSubscriber $subscriber;

    protected function setUp(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $this->subscriber = new StorefrontSubscriber($router, $translator);
    }

    #[Test]
    public function subscribesToKernelRequestEvent(): void
    {
        $events = StorefrontSubscriber::getSubscribedEvents();

        static::assertArrayHasKey(KernelEvents::REQUEST, $events);
        static::assertSame(['onKernelRequest', 10], $events[KernelEvents::REQUEST]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function blockedRouteProvider(): array
    {
        return [
            'checkout confirm' => ['frontend.checkout.confirm.page'],
            'checkout finish' => ['frontend.checkout.finish.page'],
            'cart page' => ['frontend.cart.offcanvas'],
            'cart add' => ['frontend.cart.add'],
            'product detail' => ['frontend.detail.page'],
            'search page' => ['frontend.search.page'],
            'search suggest' => ['frontend.search.suggest'],
            'wishlist page' => ['frontend.wishlist.page.load'],
            'store-api checkout' => ['store-api.checkout.cart.read'],
            'store-api cart' => ['store-api.cart.items.add'],
            'store-api product' => ['store-api.product.search'],
            'store-api wishlist' => ['store-api.wishlist.merge'],
        ];
    }

    #[Test]
    #[DataProvider('blockedRouteProvider')]
    public function blocksEcommerceRoutes(string $routeName): void
    {
        $request = new Request();
        $request->attributes->set('_route', $routeName);

        $kernel = $this->createStub(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->expectException(NotFoundHttpException::class);
        $this->subscriber->onKernelRequest($event);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allowedRouteProvider(): array
    {
        return [
            'homepage' => ['frontend.home.page'],
            'account login' => ['frontend.account.login.page'],
            'account register' => ['frontend.account.register.page'],
            'account profile' => ['frontend.account.profile.page'],
            'cms page' => ['frontend.cms.page'],
        ];
    }

    #[Test]
    #[DataProvider('allowedRouteProvider')]
    public function allowsNonEcommerceRoutes(string $routeName): void
    {
        $request = new Request();
        $request->attributes->set('_route', $routeName);

        $kernel = $this->createStub(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should not throw
        $this->subscriber->onKernelRequest($event);
        static::assertTrue(true); // If we get here, no exception was thrown
    }

    #[Test]
    public function ignoresSubRequests(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'frontend.checkout.confirm.page');

        $kernel = $this->createStub(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        // Should not throw for sub-requests
        $this->subscriber->onKernelRequest($event);
        static::assertTrue(true);
    }

    #[Test]
    public function ignoresRequestsWithoutRoute(): void
    {
        $request = new Request();
        // No _route attribute set

        $kernel = $this->createStub(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should not throw
        $this->subscriber->onKernelRequest($event);
        static::assertTrue(true);
    }

    #[Test]
    public function ignoresRequestsWithEmptyRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', '');

        $kernel = $this->createStub(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should not throw
        $this->subscriber->onKernelRequest($event);
        static::assertTrue(true);
    }
}
