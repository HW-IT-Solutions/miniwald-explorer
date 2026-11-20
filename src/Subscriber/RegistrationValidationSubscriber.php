<?php declare(strict_types=1);

namespace InReNa\Subscriber;

use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Removes validation requirements for address fields during registration.
 *
 * MiniwaldExplorer doesn't need full address data since it's not a shop.
 * The RegistrationSubscriber will auto-fill these fields server-side.
 */
class RegistrationValidationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'framework.validation.address.create' => 'removeAddressValidation',
            'framework.validation.customer.create' => 'removeCustomerValidation',
            // Also handle registration-specific validation events
            BuildValidationEvent::class => 'onBuildValidation',
        ];
    }

    /**
     * Generic handler for all validation events during registration.
     */
    public function onBuildValidation(BuildValidationEvent $event): void
    {
        // Apply to all validation contexts
        $this->removeAddressValidation($event);
        $this->removeCustomerValidation($event);
    }

    /**
     * Remove validation requirements for address fields that we auto-fill.
     */
    public function removeAddressValidation(BuildValidationEvent $event): void
    {
        $definition = $event->getDefinition();

        // Make these fields optional - they will be auto-filled by RegistrationSubscriber
        $this->makeFieldOptional($definition, 'salutationId');
        $this->makeFieldOptional($definition, 'lastName');
        $this->makeFieldOptional($definition, 'street');
        $this->makeFieldOptional($definition, 'zipcode');
        $this->makeFieldOptional($definition, 'city');
        $this->makeFieldOptional($definition, 'countryId');

        // Also handle sub-definitions (e.g., billingAddress, shippingAddress)
        foreach ($definition->getSubDefinitions() as $subDefinition) {
            $this->makeFieldOptional($subDefinition, 'salutationId');
            $this->makeFieldOptional($subDefinition, 'lastName');
            $this->makeFieldOptional($subDefinition, 'street');
            $this->makeFieldOptional($subDefinition, 'zipcode');
            $this->makeFieldOptional($subDefinition, 'city');
            $this->makeFieldOptional($subDefinition, 'countryId');
        }
    }

    /**
     * Remove validation requirements for customer-level fields that we auto-fill.
     */
    public function removeCustomerValidation(BuildValidationEvent $event): void
    {
        $definition = $event->getDefinition();

        // Make these fields optional at customer level too
        $this->makeFieldOptional($definition, 'salutationId');
        $this->makeFieldOptional($definition, 'lastName');
    }

    /**
     * Remove NotBlank constraint from a field to make it optional.
     */
    private function makeFieldOptional(DataValidationDefinition $definition, string $field): void
    {
        $constraints = $definition->getProperty($field);

        if (empty($constraints)) {
            return;
        }

        // Filter out NotBlank constraints
        $filteredConstraints = array_filter($constraints, function ($constraint) {
            return !$constraint instanceof NotBlank;
        });

        // If we removed any constraints, update the definition
        if (count($filteredConstraints) !== count($constraints)) {
            $definition->set($field, ...$filteredConstraints);
        }
    }
}
