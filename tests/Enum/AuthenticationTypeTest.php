<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;

/**
 * 认证类型枚举测试
 *
 * @internal
 */
#[CoversClass(AuthenticationType::class)]
final class AuthenticationTypeTest extends AbstractEnumTestCase
{
    /**
     * 测试个人认证类型
     */
    public function testPersonalTypeExists(): void
    {
        $this->assertEquals('personal', AuthenticationType::PERSONAL->value);
        $this->assertCount(1, AuthenticationType::cases());
    }

    /**
     * 测试标签获取
     */
    public function testGetLabel(): void
    {
        $this->assertEquals('个人认证', AuthenticationType::PERSONAL->getLabel());
    }

    /**
     * 测试枚举值创建
     */
    public function testEnumFromValue(): void
    {
        $this->assertEquals(AuthenticationType::PERSONAL, AuthenticationType::from('personal'));
    }

    /**
     * 测试无效枚举值
     */
    public function testInvalidEnumValue(): void
    {
        $this->expectException(\ValueError::class);
        AuthenticationType::from('invalid_type');
    }

    /**
     * 测试枚举实现的接口方法
     */
    public function testInterfaceMethods(): void
    {
        $type = AuthenticationType::PERSONAL;

        // 测试是否实现了 Labelable 接口
        $this->assertNotEmpty($type->getLabel());
    }

    /**
     * 测试 toArray 方法
     */
    public function testToArray(): void
    {
        $type = AuthenticationType::PERSONAL;
        $array = $type->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('personal', $array['value']);
        $this->assertEquals('个人认证', $array['label']);
    }
}
