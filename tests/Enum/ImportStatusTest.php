<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;

/**
 * @internal
 */
#[CoversClass(ImportStatus::class)]
final class ImportStatusTest extends AbstractEnumTestCase
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

    /**
     * 测试 toArray 方法
     */
    public function testToArray(): void
    {
        $status = ImportStatus::PENDING;
        $array = $status->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('pending', $array['value']);
        $this->assertEquals('等待处理', $array['label']);
    }
}
