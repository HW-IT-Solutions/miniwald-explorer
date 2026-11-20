<?php declare(strict_types=1);

namespace InReNa\Controller\Storefront;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class ProfileController extends StorefrontController
{
    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $observationRepository,
    ) {
    }

    #[Route(path: '/profile', name: 'frontend.miniwald.profile', methods: ['GET'])]
    public function index(Request $request, SalesChannelContext $context): Response
    {
        $customer = $context->getCustomer();
        if ($customer === null) {
            return $this->redirectToRoute('frontend.account.login.page', [
                'returnUrl' => '/profile',
            ]);
        }

        $appName = $this->trans('miniwaldExplorer.meta.appName');

        return $this->renderStorefront(
            '@MiniwaldExplorer/storefront/page/profile/index.html.twig',
            [
                'customer' => $customer,
                'pageTitle' => $this->trans('miniwaldExplorer.profile.pageTitle') . ' | ' . $appName,
            ]
        );
    }

    #[Route(path: '/profile/data', name: 'frontend.miniwald.profile.data', methods: ['POST'])]
    public function updateData(Request $request, SalesChannelContext $context): Response
    {
        $customer = $context->getCustomer();
        if ($customer === null) {
            return $this->redirectToRoute('frontend.account.login.page');
        }

        $name = trim((string) $request->request->get('name', ''));

        if ($name === '') {
            $this->addFlash('danger', $this->trans('miniwaldExplorer.profile.nameRequired'));

            return $this->redirectToRoute('frontend.miniwald.profile');
        }

        $this->customerRepository->update(
            [['id' => $customer->getId(), 'firstName' => $name]],
            $context->getContext()
        );

        $this->addFlash('success', $this->trans('miniwaldExplorer.profile.dataChanged'));

        return $this->redirectToRoute('frontend.miniwald.profile');
    }

    #[Route(path: '/profile/password', name: 'frontend.miniwald.profile.password', methods: ['POST'])]
    public function changePassword(Request $request, SalesChannelContext $context): Response
    {
        $customer = $context->getCustomer();
        if ($customer === null) {
            return $this->redirectToRoute('frontend.account.login.page');
        }

        $currentPassword = (string) $request->request->get('currentPassword', '');
        $newPassword = (string) $request->request->get('newPassword', '');
        $newPasswordConfirm = (string) $request->request->get('newPasswordConfirm', '');

        // Validate new password confirmation
        if ($newPassword !== $newPasswordConfirm) {
            $this->addFlash('danger', $this->trans('miniwaldExplorer.profile.passwordMismatch'));

            return $this->redirectToRoute('frontend.miniwald.profile');
        }

        // Validate minimum length
        if (strlen($newPassword) < 8) {
            $this->addFlash('danger', $this->trans('miniwaldExplorer.profile.passwordTooShort'));

            return $this->redirectToRoute('frontend.miniwald.profile');
        }

        // Verify current password
        $storedHash = $customer->getPassword();
        if ($storedHash === null || !password_verify($currentPassword, $storedHash)) {
            $this->addFlash('danger', $this->trans('miniwaldExplorer.profile.passwordError'));

            return $this->redirectToRoute('frontend.miniwald.profile');
        }

        // Update password — Shopware DAL hashes it automatically
        $this->customerRepository->update(
            [['id' => $customer->getId(), 'password' => $newPassword]],
            $context->getContext()
        );

        $this->addFlash('success', $this->trans('miniwaldExplorer.profile.passwordChanged'));

        return $this->redirectToRoute('frontend.miniwald.profile');
    }

    #[Route(path: '/profile/delete', name: 'frontend.miniwald.profile.delete', methods: ['POST'])]
    public function deleteAccount(Request $request, SalesChannelContext $context): Response
    {
        $customer = $context->getCustomer();
        if ($customer === null) {
            return $this->redirectToRoute('frontend.account.login.page');
        }

        $confirmPassword = (string) $request->request->get('confirmPassword', '');

        // Verify password before deletion
        $storedHash = $customer->getPassword();
        if ($storedHash === null || !password_verify($confirmPassword, $storedHash)) {
            $this->addFlash('danger', $this->trans('miniwaldExplorer.profile.passwordError'));

            return $this->redirectToRoute('frontend.miniwald.profile');
        }

        $ctx = $context->getContext();
        $customerId = $customer->getId();

        // Anonymize observations: set customer_id to null to preserve scientific data
        $observationCriteria = (new Criteria())
            ->addFilter(new EqualsFilter('customerId', $customerId));
        $observations = $this->observationRepository->searchIds($observationCriteria, $ctx);

        if ($observations->getTotal() > 0) {
            $updates = array_map(
                static fn (string $id) => ['id' => $id, 'customerId' => null],
                $observations->getIds()
            );
            $this->observationRepository->update($updates, $ctx);
        }

        // Delete the customer account
        $this->customerRepository->delete([['id' => $customerId]], $ctx);

        // Redirect to logout to clear the session, then to overview
        return $this->redirectToRoute('frontend.account.logout.page');
    }
}
