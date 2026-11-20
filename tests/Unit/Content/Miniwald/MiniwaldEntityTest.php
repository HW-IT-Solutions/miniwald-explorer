<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Content\Miniwald;

use InReNa\Content\Miniwald\MiniwaldEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MiniwaldEntity::class)]
class MiniwaldEntityTest extends TestCase
{
    private MiniwaldEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new MiniwaldEntity();
    }

    public function testDefaultValues(): void
    {
        static::assertNull($this->entity->getName());
    }

    public function testGetSetName(): void
    {
        $this->entity->setName('Miniwald Inden');
        static::assertSame('Miniwald Inden', $this->entity->getName());
    }

    public function testGetSetNameNull(): void
    {
        $this->entity->setName('Test');
        $this->entity->setName(null);
        static::assertNull($this->entity->getName());
    }

    public function testGetSetNameWithSpecialCharacters(): void
    {
        $this->entity->setName('Miniwald Jülich-Süd');
        static::assertSame('Miniwald Jülich-Süd', $this->entity->getName());
    }
}
