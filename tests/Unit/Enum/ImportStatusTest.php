<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Enum\ImportStatus
 */
class ImportStatusTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(ImportStatus::class));
    }

    public function testEnumValues(): void
    {
        $cases = ImportStatus::cases();
        $this->assertGreaterThan(0, count($cases));
    }
}