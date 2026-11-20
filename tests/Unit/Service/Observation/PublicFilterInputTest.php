<?php declare(strict_types=1);

namespace InReNa\Tests\Unit\Service\Observation;

use InReNa\Service\Observation\PublicFilterInput;
use PHPUnit\Framework\TestCase;

class PublicFilterInputTest extends TestCase
{
    public function testValidDatesRemainUsableInBothBoundaries(): void
    {
        self::assertSame('2026-10-05', PublicFilterInput::date('2026-10-05'));
        self::assertSame('2024-02-29', PublicFilterInput::date('2024-02-29'));
    }

    public function testMalformedDatesAreIgnored(): void
    {
        foreach (['ASDF', '2026-02-30', '2026-1-05', '2026-10-05T12:00:00Z', ['2026-10-05']] as $value) {
            self::assertNull(PublicFilterInput::date($value));
        }
    }

    public function testUuidAndOtherMiniwaldStayValid(): void
    {
        $id = '0123456789abcdef0123456789abcdef';
        self::assertSame($id, PublicFilterInput::uuid($id));
        self::assertSame($id, PublicFilterInput::miniwald($id));
        self::assertSame('other', PublicFilterInput::miniwald('other'));
    }

    public function testMalformedIdentifiersAndPagesAreIgnored(): void
    {
        foreach (['ASDF', ['ASDF'], 'other', ''] as $value) {
            self::assertNull(PublicFilterInput::uuid($value));
        }
        self::assertNull(PublicFilterInput::miniwald('ASDF'));
        self::assertSame(2, PublicFilterInput::page('2'));
        self::assertSame(1, PublicFilterInput::page(['2']));
        self::assertSame(1, PublicFilterInput::page('999999999999999999999999'));
    }
}
