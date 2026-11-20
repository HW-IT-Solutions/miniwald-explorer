<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Subscriber;

use InReNa\Subscriber\RegistrationSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Event\DataMappingEvent;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;

#[CoversClass(RegistrationSubscriber::class)]
class RegistrationSubscriberTest extends TestCase
{
    private RegistrationSubscriber $subscriber;
    private EntityRepository $salutationRepository;
    private EntityRepository $countryRepository;
    private SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        $this->salutationRepository = $this->createStub(EntityRepository::class);
        $this->countryRepository = $this->createStub(EntityRepository::class);
        $this->systemConfigService = $this->createStub(SystemConfigService::class);

        $this->subscriber = new RegistrationSubscriber(
            $this->salutationRepository,
            $this->countryRepository,
            $this->systemConfigService,
        );
    }

    #[Test]
    public function subscribesToCorrectEvents(): void
    {
        $events = RegistrationSubscriber::getSubscribedEvents();

        static::assertArrayHasKey(CustomerEvents::MAPPING_REGISTER_CUSTOMER, $events);
        static::assertArrayHasKey(CustomerEvents::MAPPING_REGISTER_ADDRESS_BILLING, $events);
        static::assertSame('onCustomerRegisterMapping', $events[CustomerEvents::MAPPING_REGISTER_CUSTOMER]);
        static::assertSame('onBillingAddressMapping', $events[CustomerEvents::MAPPING_REGISTER_ADDRESS_BILLING]);
    }

    #[Test]
    public function onCustomerRegisterMappingStoresDataConsent(): void
    {
        $dataBag = new DataBag(['miniwaldDataConsent' => '1']);
        $context = Context::createDefaultContext();
        $output = [];

        $salutationId = 'abc123-salutation-id';
        $idSearchResult = $this->createStub(IdSearchResult::class);
        $idSearchResult->method('firstId')->willReturn($salutationId);
        $this->salutationRepository->method('searchIds')->willReturn($idSearchResult);

        $event = new DataMappingEvent($dataBag, $output, $context);
        $this->subscriber->onCustomerRegisterMapping($event);

        $result = $event->getOutput();
        static::assertTrue($result['customFields']['miniwald_explorer_data_consent']);
        static::assertNotEmpty($result['customFields']['miniwald_explorer_data_consent_date']);
        static::assertSame($salutationId, $result['salutationId']);
    }

    #[Test]
    public function onCustomerRegisterMappingAutoFillsSalutation(): void
    {
        $dataBag = new DataBag();
        $context = Context::createDefaultContext();
        $output = [];

        $salutationId = 'abc123-salutation-id';
        $idSearchResult = $this->createStub(IdSearchResult::class);
        $idSearchResult->method('firstId')->willReturn($salutationId);
        $this->salutationRepository->method('searchIds')->willReturn($idSearchResult);

        $event = new DataMappingEvent($dataBag, $output, $context);
        $this->subscriber->onCustomerRegisterMapping($event);

        $result = $event->getOutput();
        static::assertSame($salutationId, $result['salutationId']);
    }

    #[Test]
    public function onCustomerRegisterMappingDoesNotOverrideSalutation(): void
    {
        $dataBag = new DataBag();
        $context = Context::createDefaultContext();
        $existingSalutationId = 'existing-salutation-id';
        $output = ['salutationId' => $existingSalutationId];

        $event = new DataMappingEvent($dataBag, $output, $context);
        $this->subscriber->onCustomerRegisterMapping($event);

        $result = $event->getOutput();
        static::assertSame($existingSalutationId, $result['salutationId']);
    }

    #[Test]
    public function onBillingAddressMappingFillsDummyData(): void
    {
        $dataBag = new DataBag();
        $context = Context::createDefaultContext();
        $output = [];

        $salutationId = 'abc123-salutation-id';
        $salutationResult = $this->createStub(IdSearchResult::class);
        $salutationResult->method('firstId')->willReturn($salutationId);
        $this->salutationRepository->method('searchIds')->willReturn($salutationResult);

        $countryId = 'germany-country-id';
        $countryResult = $this->createStub(IdSearchResult::class);
        $countryResult->method('firstId')->willReturn($countryId);
        $this->countryRepository->method('searchIds')->willReturn($countryResult);

        $event = new DataMappingEvent($dataBag, $output, $context);
        $this->subscriber->onBillingAddressMapping($event);

        $result = $event->getOutput();
        static::assertSame('MiniwaldExplorer', $result['street']);
        static::assertSame('00000', $result['zipcode']);
        static::assertSame('MiniwaldExplorer', $result['city']);
        static::assertSame($countryId, $result['countryId']);
        static::assertSame($salutationId, $result['salutationId']);
    }

    #[Test]
    public function onBillingAddressMappingDoesNotOverrideExistingValues(): void
    {
        $dataBag = new DataBag();
        $context = Context::createDefaultContext();
        $output = [
            'street' => 'Existing Street',
            'zipcode' => '12345',
            'city' => 'Existing City',
            'countryId' => 'existing-country-id',
            'salutationId' => 'existing-salutation-id',
        ];

        $event = new DataMappingEvent($dataBag, $output, $context);
        $this->subscriber->onBillingAddressMapping($event);

        $result = $event->getOutput();
        static::assertSame('Existing Street', $result['street']);
        static::assertSame('12345', $result['zipcode']);
        static::assertSame('Existing City', $result['city']);
        static::assertSame('existing-country-id', $result['countryId']);
        static::assertSame('existing-salutation-id', $result['salutationId']);
    }
}
