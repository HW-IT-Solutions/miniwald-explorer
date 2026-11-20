<?php declare(strict_types=1);

namespace InReNa\Event;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Flow\Dispatching\Aware\ScalarValuesAware;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Shopware\Core\Framework\Event\FlowEventAware;
use Shopware\Core\Framework\Event\MailAware;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('miniwald-explorer')]
class ObservationSubmittedEvent extends Event implements FlowEventAware, MailAware, SalesChannelAware, ShopwareSalesChannelEvent, ScalarValuesAware
{
    public const EVENT_NAME = 'miniwald.observation.submitted';

    private ?MailRecipientStruct $mailRecipientStruct = null;

    public function __construct(
        private readonly SalesChannelContext $salesChannelContext,
        private readonly string $observationId,
        private readonly CustomerEntity $customer,
        private readonly string $miniwaldName,
        private readonly \DateTimeInterface $observedAt,
        private readonly ?string $weather,
        private readonly ?string $wind,
        private readonly int $itemCount,
        private readonly string $adminEmail,
    ) {
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelContext->getSalesChannelId();
    }

    /**
     * Declares the variable types available in the Flow Builder mail template editor.
     */
    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('observationId', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('customerName', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('customerEmail', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('miniwaldName', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('observedAt', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('weather', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('wind', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('itemCount', new ScalarValueType(ScalarValueType::TYPE_INT));
    }

    /**
     * Provides the actual variable values to the mail template renderer.
     *
     * @return array<string, scalar|array<mixed>|null>
     */
    public function getValues(): array
    {
        return [
            'observationId' => $this->observationId,
            'customerName' => $this->customer->getFirstName() ?? '',
            'customerEmail' => $this->customer->getEmail() ?? '',
            'miniwaldName' => $this->miniwaldName,
            'observedAt' => $this->observedAt->format('d.m.Y H:i'),
            'weather' => $this->weather ?? '',
            'wind' => $this->wind ?? '',
            'itemCount' => $this->itemCount,
        ];
    }

    /**
     * Provides the mail recipient for the "Send Mail" Flow action.
     * The admin email is read from plugin system config (MiniwaldExplorer.config.adminNotificationEmail).
     */
    public function getMailStruct(): MailRecipientStruct
    {
        if ($this->mailRecipientStruct === null) {
            $recipients = [];
            if (!empty($this->adminEmail)) {
                $recipients[$this->adminEmail] = 'MiniwaldExplorer Admin';
            }
            $this->mailRecipientStruct = new MailRecipientStruct($recipients);
        }

        return $this->mailRecipientStruct;
    }
}
