<?php declare(strict_types=1);

namespace InReNa\Content\Miniwald;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MiniwaldTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'miniwald_explorer_miniwald_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getParentDefinitionClass(): string
    {
        return MiniwaldDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new LongTextField('description', 'description'))->addFlags(new ApiAware()),
        ]);
    }
}
