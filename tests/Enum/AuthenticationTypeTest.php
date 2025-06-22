<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;

/**
 * 认证类型枚举测试
 */
class AuthenticationTypeTest extends TestCase
{
    /**
     * 测试个人认证类型
     */
    public function test_personal_type_exists(): void
    {
        $this->assertEquals('personal', AuthenticationType::PERSONAL->value);
        $this->assertCount(1, AuthenticationType::cases());
    }

    /**
     * 测试标签获取
     */
    public function test_get_label(): void
    {
        $this->assertEquals('个人认证', AuthenticationType::PERSONAL->getLabel());
    }

    /**
     * 测试枚举值创建
     */
    public function test_enum_from_value(): void
    {
        $this->assertEquals(AuthenticationType::PERSONAL, AuthenticationType::from('personal'));
    }

    /**
     * 测试无效枚举值
     */
    public function test_invalid_enum_value(): void
    {
        $this->expectException(\ValueError::class);
        AuthenticationType::from('invalid_type');
    }

    /**
     * 测试枚举实现的接口方法
     */
    public function test_interface_methods(): void
    {
        $type = AuthenticationType::PERSONAL;
        
        // 测试是否实现了 Labelable 接口
        $this->assertNotEmpty($type->getLabel());
    }
} 