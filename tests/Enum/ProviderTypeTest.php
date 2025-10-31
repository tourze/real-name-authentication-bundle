<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;

/**
 * 提供商类型枚举测试
 *
 * @internal
 */
#[CoversClass(ProviderType::class)]
final class ProviderTypeTest extends AbstractEnumTestCase
{
    /**
     * 测试所有提供商类型
     */
    public function testAllProviderTypesExist(): void
    {
        $expectedValues = [
            'government',
            'bank_union',
            'carrier',
            'third_party',
        ];

        $actualValues = array_map(fn ($case) => $case->value, ProviderType::cases());

        $this->assertEquals($expectedValues, $actualValues);
        $this->assertCount(4, ProviderType::cases());
    }

    /**
     * 测试标签获取
     */
    public function testGetLabel(): void
    {
        $this->assertEquals('政府部门', ProviderType::GOVERNMENT->getLabel());
        $this->assertEquals('银联', ProviderType::BANK_UNION->getLabel());
        $this->assertEquals('运营商', ProviderType::CARRIER->getLabel());
        $this->assertEquals('第三方', ProviderType::THIRD_PARTY->getLabel());
    }

    /**
     * 测试支持的认证方式
     */
    public function testGetSupportedMethods(): void
    {
        // 政府部门支持身份证二要素
        $governmentMethods = ProviderType::GOVERNMENT->getSupportedMethods();
        $this->assertContains(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $governmentMethods);
        $this->assertCount(1, $governmentMethods);

        // 银联支持银行卡认证
        $bankUnionMethods = ProviderType::BANK_UNION->getSupportedMethods();
        $this->assertContains(AuthenticationMethod::BANK_CARD_THREE_ELEMENTS, $bankUnionMethods);
        $this->assertContains(AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS, $bankUnionMethods);
        $this->assertCount(2, $bankUnionMethods);

        // 运营商支持三要素
        $carrierMethods = ProviderType::CARRIER->getSupportedMethods();
        $this->assertContains(AuthenticationMethod::CARRIER_THREE_ELEMENTS, $carrierMethods);
        $this->assertCount(1, $carrierMethods);

        // 第三方支持活体检测
        $thirdPartyMethods = ProviderType::THIRD_PARTY->getSupportedMethods();
        $this->assertContains(AuthenticationMethod::LIVENESS_DETECTION, $thirdPartyMethods);
        $this->assertCount(1, $thirdPartyMethods);
    }

    /**
     * 测试枚举值创建
     */
    public function testEnumFromValue(): void
    {
        $this->assertEquals(ProviderType::GOVERNMENT, ProviderType::from('government'));
        $this->assertEquals(ProviderType::BANK_UNION, ProviderType::from('bank_union'));
        $this->assertEquals(ProviderType::CARRIER, ProviderType::from('carrier'));
        $this->assertEquals(ProviderType::THIRD_PARTY, ProviderType::from('third_party'));
    }

    /**
     * 测试无效枚举值
     */
    public function testInvalidEnumValue(): void
    {
        $this->expectException(\ValueError::class);
        ProviderType::from('invalid_provider_type');
    }

    /**
     * 测试枚举实现的接口方法
     */
    public function testInterfaceMethods(): void
    {
        $type = ProviderType::GOVERNMENT;

        // 测试是否实现了 Labelable 接口
        $this->assertNotEmpty($type->getLabel());

        // 测试支持方式返回值类型
        $methods = $type->getSupportedMethods();
        $this->assertNotEmpty($methods);

        foreach ($methods as $method) {
            $this->assertInstanceOf(AuthenticationMethod::class, $method);
        }
    }

    /**
     * 测试 toArray 方法
     */
    public function testToArray(): void
    {
        $type = ProviderType::GOVERNMENT;
        $array = $type->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('government', $array['value']);
        $this->assertEquals('政府部门', $array['label']);
    }
}
