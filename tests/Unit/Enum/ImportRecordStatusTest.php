<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus
 */
class ImportRecordStatusTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(ImportRecordStatus::class));
    }

    public function testEnumValues(): void
    {
        $cases = ImportRecordStatus::cases();
        $this->assertGreaterThan(0, count($cases));
    }
}