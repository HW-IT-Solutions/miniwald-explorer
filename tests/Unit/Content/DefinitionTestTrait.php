<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content;

use InReNa\Content\GroundMeasurement\GroundMeasurementDefinition;
use InReNa\Content\Miniwald\MiniwaldDefinition;
use InReNa\Content\Observation\ObservationDefinition;
use InReNa\Content\ObservationItem\ObservationItemDefinition;
use InReNa\Content\Photo\PhotoDefinition;
use InReNa\Content\Plant\PlantDefinition;
use InReNa\Content\Plant\PlantTranslationDefinition;
use InReNa\Content\PlantMeasurement\PlantMeasurementDefinition;
use InReNa\Content\Species\SpeciesDefinition;
use InReNa\Content\Species\SpeciesTranslationDefinition;
use InReNa\Content\SpeciesCategory\SpeciesCategoryDefinition;
use InReNa\Content\SpeciesCategory\SpeciesCategoryTranslationDefinition;
use InReNa\Content\Subspecies\SubspeciesDefinition;
use InReNa\Content\Subspecies\SubspeciesTranslationDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Trait providing a StaticDefinitionInstanceRegistry for unit testing entity definitions.
 *
 * This registers all MiniwaldExplorer definitions plus required Shopware definitions
 * (Media, Customer, Language) so that getFields() / defineFields() work without the kernel.
 */
trait DefinitionTestTrait
{
    private static ?StaticDefinitionInstanceRegistry $registry = null;

    protected static function getRegistry(): StaticDefinitionInstanceRegistry
    {
        if (self::$registry === null) {
            $validator = new class implements ValidatorInterface {
                public function validate(mixed $value, mixed $constraints = null, mixed $groups = null): ConstraintViolationList
                {
                    return new ConstraintViolationList();
                }

                public function validateProperty(object $object, string $propertyName, mixed $groups = null): ConstraintViolationList
                {
                    return new ConstraintViolationList();
                }

                public function validatePropertyValue(mixed $objectOrClass, string $propertyName, mixed $value, mixed $groups = null): ConstraintViolationList
                {
                    return new ConstraintViolationList();
                }

                public function getMetadataFor(mixed $value): MetadataInterface
                {
                    throw new \LogicException('Not implemented');
                }

                public function hasMetadataFor(mixed $value): bool
                {
                    return false;
                }

                public function startContext(): ContextualValidatorInterface
                {
                    throw new \LogicException('Not implemented');
                }

                public function inContext(ExecutionContextInterface $context): ContextualValidatorInterface
                {
                    throw new \LogicException('Not implemented');
                }
            };

            $writeGateway = new class implements EntityWriteGatewayInterface {
                public function prefetchExistences(WriteParameterBag $parameterBag): void
                {
                }

                public function getExistence(EntityDefinition $definition, array $primaryKey, array $data, WriteCommandQueue $commandQueue): EntityExistence
                {
                    return new EntityExistence('', [], false, false, false, []);
                }

                public function execute(array $commands, WriteContext $context): void
                {
                }
            };

            self::$registry = new StaticDefinitionInstanceRegistry(
                [
                    // Shopware core definitions
                    MediaDefinition::class,
                    CustomerDefinition::class,
                    LanguageDefinition::class,
                    // MiniwaldExplorer definitions
                    SpeciesCategoryDefinition::class,
                    SpeciesCategoryTranslationDefinition::class,
                    SpeciesDefinition::class,
                    SpeciesTranslationDefinition::class,
                    SubspeciesDefinition::class,
                    SubspeciesTranslationDefinition::class,
                    MiniwaldDefinition::class,
                    PlantDefinition::class,
                    PlantTranslationDefinition::class,
                    ObservationDefinition::class,
                    ObservationItemDefinition::class,
                    GroundMeasurementDefinition::class,
                    PlantMeasurementDefinition::class,
                    PhotoDefinition::class,
                ],
                $validator,
                $writeGateway,
            );
        }

        return self::$registry;
    }
}
