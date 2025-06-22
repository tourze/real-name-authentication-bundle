<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;

/**
 * 认证提供商实体测试
 */
class AuthenticationProviderTest extends TestCase
{
    /**
     * 测试实体创建和基本属性
     */
    public function test_entity_creation_and_basic_properties(): void
    {
        $provider = new AuthenticationProvider();

        // 验证默认值
        $this->assertNotEmpty($provider->getId());
        $this->assertTrue($provider->isActive());
        $this->assertTrue($provider->isValid());
        $this->assertEquals(0, $provider->getPriority());
        $this->assertEmpty($provider->getSupportedMethods());
        $this->assertEmpty($provider->getConfig());
        // 时间戳会在持久化时由 Doctrine 自动设置
        $this->assertNull($provider->getCreateTime());
        $this->assertNull($provider->getUpdateTime());
    }

    /**
     * 测试设置和获取名称
     */
    public function test_set_and_get_name(): void
    {
        $provider = new AuthenticationProvider();
        $name = '测试认证提供商';

        $provider->setName($name);
        $this->assertEquals($name, $provider->getName());
    }

    /**
     * 测试设置和获取代码
     */
    public function test_set_and_get_code(): void
    {
        $provider = new AuthenticationProvider();
        $code = 'test_provider';

        $provider->setCode($code);
        $this->assertEquals($code, $provider->getCode());
    }

    /**
     * 测试设置和获取类型
     */
    public function test_set_and_get_type(): void
    {
        $provider = new AuthenticationProvider();
        $type = ProviderType::GOVERNMENT;

        $provider->setType($type);
        $this->assertEquals($type, $provider->getType());
    }

    /**
     * 测试设置和获取支持的认证方式
     */
    public function test_set_and_get_supported_methods(): void
    {
        $provider = new AuthenticationProvider();
        $methods = [
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value,
            AuthenticationMethod::CARRIER_THREE_ELEMENTS->value,
        ];

        $provider->setSupportedMethods($methods);
        $this->assertEquals($methods, $provider->getSupportedMethods());
    }

    /**
     * 测试认证方式支持判断
     */
    public function test_supports_method(): void
    {
        $provider = new AuthenticationProvider();
        $provider->setSupportedMethods([
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value,
            AuthenticationMethod::BANK_CARD_THREE_ELEMENTS->value,
        ]);

        $this->assertTrue($provider->supportsMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS));
        $this->assertTrue($provider->supportsMethod(AuthenticationMethod::BANK_CARD_THREE_ELEMENTS));
        $this->assertFalse($provider->supportsMethod(AuthenticationMethod::CARRIER_THREE_ELEMENTS));
        $this->assertFalse($provider->supportsMethod(AuthenticationMethod::LIVENESS_DETECTION));
    }

    /**
     * 测试设置和获取API端点
     */
    public function test_set_and_get_api_endpoint(): void
    {
        $provider = new AuthenticationProvider();
        $endpoint = 'https://api.example.com/auth';

        $provider->setApiEndpoint($endpoint);
        $this->assertEquals($endpoint, $provider->getApiEndpoint());
    }

    /**
     * 测试配置值获取和设置
     */
    public function test_config_value_operations(): void
    {
        $provider = new AuthenticationProvider();
        
        // 测试设置和获取配置
        $config = [
            'api_key' => 'test_key_123',
            'timeout' => 30,
            'retry_count' => 3,
            'debug' => true,
        ];
        $provider->setConfig($config);
        $this->assertEquals($config, $provider->getConfig());

        // 测试获取单个配置值
        $this->assertEquals('test_key_123', $provider->getConfigValue('api_key'));
        $this->assertEquals(30, $provider->getConfigValue('timeout'));
        $this->assertEquals(3, $provider->getConfigValue('retry_count'));
        $this->assertTrue($provider->getConfigValue('debug'));

        // 测试获取不存在的配置值
        $this->assertNull($provider->getConfigValue('non_existent'));
        $this->assertEquals('default_value', $provider->getConfigValue('non_existent', 'default_value'));

        // 测试设置单个配置值
        $provider->setConfigValue('new_key', 'new_value');
        $this->assertEquals('new_value', $provider->getConfigValue('new_key'));
        
        // 验证配置已更新
        $updatedConfig = $provider->getConfig();
        $this->assertArrayHasKey('new_key', $updatedConfig);
        $this->assertEquals('new_value', $updatedConfig['new_key']);
    }

    /**
     * 测试设置和获取优先级
     */
    public function test_set_and_get_priority(): void
    {
        $provider = new AuthenticationProvider();
        $priority = 85;

        $provider->setPriority($priority);
        $this->assertEquals($priority, $provider->getPriority());
    }

    /**
     * 测试设置和获取激活状态
     */
    public function test_set_and_get_active(): void
    {
        $provider = new AuthenticationProvider();

        // 默认是激活的
        $this->assertTrue($provider->isActive());

        $provider->setActive(false);
        $this->assertFalse($provider->isActive());

        $provider->setActive(true);
        $this->assertTrue($provider->isActive());
    }

    /**
     * 测试设置和获取有效性
     */
    public function test_set_and_get_valid(): void
    {
        $provider = new AuthenticationProvider();

        // 默认是有效的
        $this->assertTrue($provider->isValid());

        $provider->setValid(false);
        $this->assertFalse($provider->isValid());

        $provider->setValid(true);
        $this->assertTrue($provider->isValid());
    }

    /**
     * 测试toString方法
     */
    public function test_to_string(): void
    {
        $provider = new AuthenticationProvider();
        $provider->setName('测试提供商');
        $provider->setType(ProviderType::GOVERNMENT);

        $expected = '测试提供商 (政府部门)';
        $this->assertEquals($expected, (string)$provider);
    }

    /**
     * 测试结果集合getter
     */
    public function test_get_results(): void
    {
        $provider = new AuthenticationProvider();
        $results = $provider->getResults();

        $this->assertNotNull($results);
        $this->assertCount(0, $results);
    }

    /**
     * 测试完整的提供商配置
     */
    public function test_complete_provider_configuration(): void
    {
        $provider = new AuthenticationProvider();
        
        $provider->setName('阿里云身份认证');
        $provider->setCode('aliyun_auth');
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setSupportedMethods([
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value,
            AuthenticationMethod::LIVENESS_DETECTION->value,
        ]);
        $provider->setApiEndpoint('https://eid.cn-shanghai.aliyuncs.com');
        $provider->setConfig([
            'access_key_id' => 'test_access_key',
            'access_key_secret' => 'test_secret',
            'region' => 'cn-shanghai',
            'timeout' => 30,
        ]);
        $provider->setPriority(90);
        $provider->setActive(true);

        // 验证所有设置
        $this->assertEquals('阿里云身份认证', $provider->getName());
        $this->assertEquals('aliyun_auth', $provider->getCode());
        $this->assertEquals(ProviderType::THIRD_PARTY, $provider->getType());
        $this->assertEquals('https://eid.cn-shanghai.aliyuncs.com', $provider->getApiEndpoint());
        $this->assertEquals(90, $provider->getPriority());
        $this->assertTrue($provider->isActive());
        
        // 验证支持的方式
        $this->assertTrue($provider->supportsMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS));
        $this->assertTrue($provider->supportsMethod(AuthenticationMethod::LIVENESS_DETECTION));
        $this->assertFalse($provider->supportsMethod(AuthenticationMethod::CARRIER_THREE_ELEMENTS));
        
        // 验证配置
        $this->assertEquals('test_access_key', $provider->getConfigValue('access_key_id'));
        $this->assertEquals('cn-shanghai', $provider->getConfigValue('region'));
        $this->assertEquals(30, $provider->getConfigValue('timeout'));
    }
} 