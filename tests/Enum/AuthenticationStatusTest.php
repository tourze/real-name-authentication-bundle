<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;

/**
 * 认证状态枚举测试
 */
class AuthenticationStatusTest extends TestCase
{
    /**
     * 测试所有状态枚举值
     */
    public function test_all_status_values_exist(): void
    {
        $expectedValues = [
            'pending',
            'processing',
            'approved',
            'rejected',
            'expired',
        ];

        $actualValues = array_map(fn($case) => $case->value, AuthenticationStatus::cases());

        $this->assertEquals($expectedValues, $actualValues);
        $this->assertCount(5, AuthenticationStatus::cases());
    }

    /**
     * 测试标签获取
     */
    public function test_get_label(): void
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
    public function test_is_final(): void
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
    public function test_enum_from_value(): void
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
    public function test_invalid_enum_value(): void
    {
        $this->expectException(\ValueError::class);
        AuthenticationStatus::from('invalid_status');
    }

    /**
     * 测试枚举实现的接口方法
     */
    public function test_interface_methods(): void
    {
        $status = AuthenticationStatus::PENDING;
        
        // 测试是否实现了 Labelable 接口
        $this->assertNotEmpty($status->getLabel());
    }
} 