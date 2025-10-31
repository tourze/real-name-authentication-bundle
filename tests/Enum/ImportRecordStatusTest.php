<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;

/**
 * @internal
 */
#[CoversClass(ImportRecordStatus::class)]
final class ImportRecordStatusTest extends AbstractEnumTestCase
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

    /**
     * 测试 toArray 方法
     */
    public function testToArray(): void
    {
        $status = ImportRecordStatus::PENDING;
        $array = $status->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('pending', $array['value']);
        $this->assertEquals('等待处理', $array['label']);
    }
}
