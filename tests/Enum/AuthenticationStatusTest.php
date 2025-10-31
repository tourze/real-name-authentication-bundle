<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;

/**
 * 认证状态枚举测试
 *
 * @internal
 */
#[CoversClass(AuthenticationStatus::class)]
final class AuthenticationStatusTest extends AbstractEnumTestCase
{
    /**
     * 测试所有状态枚举值
     */
    public function testAllStatusValuesExist(): void
    {
        $expectedValues = [
            'pending',
            'processing',
            'approved',
            'rejected',
            'expired',
        ];

        $actualValues = array_map(fn ($case) => $case->value, AuthenticationStatus::cases());

        $this->assertEquals($expectedValues, $actualValues);
        $this->assertCount(5, AuthenticationStatus::cases());
    }

    /**
     * 测试标签获取
     */
    public function testGetLabel(): void
    {
        $this->assertEquals('待审核', AuthenticationStatus::PENDING->getLabel());
        $this->assertEquals('审核中', AuthenticationStatus::PROCESSING->getLabel());
        $this->assertEquals('已通过', AuthenticationStatus::APPROVED->getLabel());
        $this->assertEquals('已拒绝', AuthenticationStatus::REJECTED->getLabel());
        $this->assertEquals('已过期', AuthenticationStatus::EXPIRED->getLabel());
    }

    /**
     * 测试最终状态判断
     */
    public function testIsFinal(): void
    {
        // 非最终状态
        $this->assertFalse(AuthenticationStatus::PENDING->isFinal());
        $this->assertFalse(AuthenticationStatus::PROCESSING->isFinal());

        // 最终状态
        $this->assertTrue(AuthenticationStatus::APPROVED->isFinal());
        $this->assertTrue(AuthenticationStatus::REJECTED->isFinal());
        $this->assertTrue(AuthenticationStatus::EXPIRED->isFinal());
    }

    /**
     * 测试枚举值创建
     */
    public function testEnumFromValue(): void
    {
        $this->assertEquals(AuthenticationStatus::PENDING, AuthenticationStatus::from('pending'));
        $this->assertEquals(AuthenticationStatus::PROCESSING, AuthenticationStatus::from('processing'));
        $this->assertEquals(AuthenticationStatus::APPROVED, AuthenticationStatus::from('approved'));
        $this->assertEquals(AuthenticationStatus::REJECTED, AuthenticationStatus::from('rejected'));
        $this->assertEquals(AuthenticationStatus::EXPIRED, AuthenticationStatus::from('expired'));
    }

    /**
     * 测试无效枚举值
     */
    public function testInvalidEnumValue(): void
    {
        $this->expectException(\ValueError::class);
        AuthenticationStatus::from('invalid_status');
    }

    /**
     * 测试枚举实现的接口方法
     */
    public function testInterfaceMethods(): void
    {
        $status = AuthenticationStatus::PENDING;

        // 测试是否实现了 Labelable 接口
        $this->assertNotEmpty($status->getLabel());
    }

    /**
     * 测试 toArray 方法
     */
    public function testToArray(): void
    {
        $status = AuthenticationStatus::PENDING;
        $array = $status->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('pending', $array['value']);
        $this->assertEquals('待审核', $array['label']);
    }
}
