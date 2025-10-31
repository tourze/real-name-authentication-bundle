<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;

/**
 * 认证方式枚举测试
 *
 * @internal
 */
#[CoversClass(AuthenticationMethod::class)]
final class AuthenticationMethodTest extends AbstractEnumTestCase
{
    /**
     * 测试所有枚举值存在
     */
    public function testAllEnumValuesExist(): void
    {
        $expectedValues = [
            'id_card_two_elements',
            'carrier_three_elements',
            'bank_card_three_elements',
            'bank_card_four_elements',
            'liveness_detection',
        ];

        $actualValues = array_map(fn ($case) => $case->value, AuthenticationMethod::cases());

        $this->assertEquals($expectedValues, $actualValues);
        $this->assertCount(5, AuthenticationMethod::cases());
    }

    /**
     * 测试标签获取
     */
    public function testGetLabel(): void
    {
        $this->assertEquals('身份证二要素', AuthenticationMethod::ID_CARD_TWO_ELEMENTS->getLabel());
        $this->assertEquals('运营商三要素', AuthenticationMethod::CARRIER_THREE_ELEMENTS->getLabel());
        $this->assertEquals('银行卡三要素', AuthenticationMethod::BANK_CARD_THREE_ELEMENTS->getLabel());
        $this->assertEquals('银行卡四要素', AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS->getLabel());
        $this->assertEquals('活体检测', AuthenticationMethod::LIVENESS_DETECTION->getLabel());
    }

    /**
     * 测试必需字段获取
     */
    public function testGetRequiredFields(): void
    {
        $this->assertEquals(['name', 'id_card'], AuthenticationMethod::ID_CARD_TWO_ELEMENTS->getRequiredFields());
        $this->assertEquals(['name', 'id_card', 'mobile'], AuthenticationMethod::CARRIER_THREE_ELEMENTS->getRequiredFields());
        $this->assertEquals(['name', 'id_card', 'bank_card'], AuthenticationMethod::BANK_CARD_THREE_ELEMENTS->getRequiredFields());
        $this->assertEquals(['name', 'id_card', 'bank_card', 'mobile'], AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS->getRequiredFields());
        $this->assertEquals(['image'], AuthenticationMethod::LIVENESS_DETECTION->getRequiredFields());
    }

    /**
     * 测试个人认证方式判断
     */
    public function testIsPersonal(): void
    {
        foreach (AuthenticationMethod::cases() as $method) {
            $this->assertTrue($method->isPersonal(), "方式 {$method->value} 应该是个人认证方式");
        }
    }

    /**
     * 测试枚举值创建
     */
    public function testEnumFromValue(): void
    {
        $this->assertEquals(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, AuthenticationMethod::from('id_card_two_elements'));
        $this->assertEquals(AuthenticationMethod::CARRIER_THREE_ELEMENTS, AuthenticationMethod::from('carrier_three_elements'));
        $this->assertEquals(AuthenticationMethod::BANK_CARD_THREE_ELEMENTS, AuthenticationMethod::from('bank_card_three_elements'));
        $this->assertEquals(AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS, AuthenticationMethod::from('bank_card_four_elements'));
        $this->assertEquals(AuthenticationMethod::LIVENESS_DETECTION, AuthenticationMethod::from('liveness_detection'));
    }

    /**
     * 测试无效枚举值
     */
    public function testInvalidEnumValue(): void
    {
        $this->expectException(\ValueError::class);
        AuthenticationMethod::from('invalid_method');
    }

    /**
     * 测试枚举实现的接口方法
     */
    public function testInterfaceMethods(): void
    {
        $method = AuthenticationMethod::ID_CARD_TWO_ELEMENTS;

        // 测试是否实现了 Labelable 接口
        $this->assertNotEmpty($method->getLabel());
    }

    /**
     * 测试 toArray 方法
     */
    public function testToArray(): void
    {
        $method = AuthenticationMethod::ID_CARD_TWO_ELEMENTS;
        $array = $method->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('id_card_two_elements', $array['value']);
        $this->assertEquals('身份证二要素', $array['label']);
    }
}
