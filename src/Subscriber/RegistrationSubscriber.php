<?php declare(strict_types=1);

namespace InReNa\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Event\DataMappingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Simplifies the registration process for MiniwaldExplorer.
 *
 * - Auto-fills dummy address data (street, city, zip) since this is not a shop
 * - Auto-fills salutation with "not_specified"
 * - Stores data consent custom field from the registration form
 */
class RegistrationSubscriber implements EventSubscriberInterface
{
    private const DUMMY_STREET = 'MiniwaldExplorer';
    private const DUMMY_ZIPCODE = '00000';
    private const DUMMY_CITY = 'MiniwaldExplorer';
    private const DUMMY_LASTNAME = '-';

    public function __construct(
        private readonly EntityRepository $salutationRepository,
        private readonly EntityRepository $countryRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'checkout.customer.sales_channel.register.customer' => 'onCustomerRegisterMapping',
            'checkout.customer.sales_channel.register.billing_address' => 'onBillingAddressMapping',
        ];
    }

    /**
     * Auto-fill customer-level fields during registration.
     */
    public function onCustomerRegisterMapping(DataMappingEvent $event): void
    {
        $output = $event->getOutput();
        $input = $event->getInput();

        // Auto-fill salutation to "not_specified" if not provided
        if (empty($output['salutationId'])) {
            $salutationId = $this->getNotSpecifiedSalutationId($event);
            if ($salutationId !== null) {
                $output['salutationId'] = $salutationId;
            }
        }

        // Store data consent in custom fields
        $dataConsent = $input->get('miniwaldDataConsent');
        if ($dataConsent !== null) {
            $customFields = $output['customFields'] ?? [];
            $customFields['miniwald_explorer_data_consent'] = (bool) $dataConsent;
            $customFields['miniwald_explorer_data_consent_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
            $output['customFields'] = $customFields;
        }

        $event->setOutput($output);
    }

    /**
     * Auto-fill billing address with dummy data during registration.
     */
    public function onBillingAddressMapping(DataMappingEvent $event): void
    {
        $output = $event->getOutput();

        // Auto-fill lastName with dummy value if not provided
        // Note: lastName is required by Shopware's database schema, cannot be empty
        if (empty($output['lastName'])) {
            $output['lastName'] = self::DUMMY_LASTNAME;
        }

        // Auto-fill dummy address fields
        if (empty($output['street'])) {
            $output['street'] = self::DUMMY_STREET;
        }

        if (empty($output['zipcode'])) {
            $output['zipcode'] = self::DUMMY_ZIPCODE;
        }

        if (empty($output['city'])) {
            $output['city'] = self::DUMMY_CITY;
        }

        // Auto-fill country to Germany if not set
        if (empty($output['countryId'])) {
            $countryId = $this->getGermanyCountryId($event);
            if ($countryId !== null) {
                $output['countryId'] = $countryId;
            }
        }

        // Auto-fill salutation for address
        if (empty($output['salutationId'])) {
            $salutationId = $this->getNotSpecifiedSalutationId($event);
            if ($salutationId !== null) {
                $output['salutationId'] = $salutationId;
            }
        }

        $event->setOutput($output);
    }

    private function getNotSpecifiedSalutationId(DataMappingEvent $event): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', 'not_specified'));
        $criteria->setLimit(1);

        $result = $this->salutationRepository->searchIds($criteria, $event->getContext());

        return $result->firstId();
    }

    private function getGermanyCountryId(DataMappingEvent $event): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', 'DE'));
        $criteria->setLimit(1);

        $result = $this->countryRepository->searchIds($criteria, $event->getContext());

        return $result->firstId();
    }
}
