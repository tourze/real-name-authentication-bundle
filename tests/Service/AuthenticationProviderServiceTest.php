<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;
use Tourze\RealNameAuthenticationBundle\Service\AuthenticationProviderService;

/**
 * @internal
 */
#[CoversClass(AuthenticationProviderService::class)]
#[RunTestsInSeparateProcesses]
final class AuthenticationProviderServiceTest extends AbstractIntegrationTestCase
{
    private AuthenticationProviderService $service;

    protected function onSetUp(): void
    {
        // 从容器获取服务实例（集成测试模式）
        $this->service = self::getService(AuthenticationProviderService::class);
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(AuthenticationProviderService::class, $this->service);
    }

    public function testGetAvailableProviders(): void
    {
        $method = AuthenticationMethod::ID_CARD_TWO_ELEMENTS;

        // 使用真实的repository获取数据
        $result = $this->service->getAvailableProviders($method);

        // 验证返回的是数组
        $this->assertIsArray($result);

        // 如果有provider支持该方法，验证它们的方法列表包含ID_CARD_TWO_ELEMENTS
        foreach ($result as $provider) {
            $this->assertInstanceOf(AuthenticationProvider::class, $provider);
            $this->assertContains('id_card_two_elements', $provider->getSupportedMethods());
        }
    }

    public function testSelectBestProvider(): void
    {
        $method = AuthenticationMethod::ID_CARD_TWO_ELEMENTS;

        // 使用真实的服务选择最佳provider
        $result = $this->service->selectBestProvider($method);

        // 验证结果类型
        if (null === $result) {
            $this->assertTrue(true); // 如果是null，这是有效的情况
        } else {
            $this->assertInstanceOf(AuthenticationProvider::class, $result);
            // 验证它支持对应的方法
            $this->assertContains('id_card_two_elements', $result->getSupportedMethods());
        }
    }

    public function testExecuteVerification(): void
    {
        // 创建测试用的provider
        $provider = new AuthenticationProvider();
        $provider->setName('测试提供商');
        $provider->setCode('test_provider');
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setSupportedMethods(['id_card_two_elements']);
        $provider->setApiEndpoint('https://api.example.com/verify');
        $provider->setConfig([
            'app_id' => 'test_app_id',
            'app_secret' => 'test_app_secret',
            'api_key' => 'test_api_key',
        ]);

        // 测试数据
        $verificationData = [
            'name' => '张三',
            'id_card' => '110101199001011234',
        ];

        // 执行验证（由于使用真实的HttpClient，外部API会失败）
        $result = $this->service->executeVerification($provider, $verificationData);

        // 验证返回结果是AuthenticationResult实例
        $this->assertInstanceOf(AuthenticationResult::class, $result);

        // 验证基本属性
        $this->assertNotEmpty($result->getRequestId());
        $this->assertGreaterThan(0, $result->getProcessingTime());
        $this->assertSame($provider, $result->getProvider());
        $this->assertIsArray($result->getResponseData());

        // 由于外部API不可达，结果应该是失败的
        $this->assertFalse($result->isSuccess());
        $this->assertSame('PROVIDER_ERROR', $result->getErrorCode());
        $this->assertNotNull($result->getErrorMessage());
    }

    public function testExecuteVerificationWithEmptyData(): void
    {
        // 创建测试用的provider
        $provider = new AuthenticationProvider();
        $provider->setName('测试提供商');
        $provider->setCode('test_provider_empty');
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setSupportedMethods(['id_card_two_elements']);
        $provider->setApiEndpoint('https://api.example.com/verify');
        $provider->setConfig([]);

        // 空测试数据
        $verificationData = [];

        // 执行验证
        $result = $this->service->executeVerification($provider, $verificationData);

        // 验证返回结果
        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertNotEmpty($result->getRequestId());
        $this->assertFalse($result->isSuccess());
    }

    public function testExecuteVerificationWithMinimalConfig(): void
    {
        // 创建最小配置的provider
        $provider = new AuthenticationProvider();
        $provider->setName('最小配置提供商');
        $provider->setCode('minimal_provider');
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setSupportedMethods(['id_card_two_elements']);
        $provider->setApiEndpoint('https://api.example.com/verify');
        $provider->setConfig([]);

        // 测试数据
        $verificationData = [
            'name' => '李四',
            'id_card' => '110101199002021007',
        ];

        // 执行验证
        $result = $this->service->executeVerification($provider, $verificationData);

        // 验证返回结果
        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertNotEmpty($result->getRequestId());
        $this->assertGreaterThan(0, $result->getProcessingTime());
    }

    public function testHandleProviderResponse(): void
    {
        $provider = new AuthenticationProvider();
        $provider->setName('测试提供商');
        $provider->setCode('test_provider');
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setSupportedMethods(['id_card_two_elements']);
        $provider->setApiEndpoint('https://api.example.com/verify');
        $provider->setConfig([]);

        // 测试成功响应
        $successResponse = [
            'code' => '200',
            'confidence' => 0.95,
        ];

        $result = $this->service->handleProviderResponse($successResponse, $provider);

        $this->assertTrue($result['success']);
        $this->assertEquals(0.95, $result['confidence']);
        $this->assertNull($result['error_code']);
        $this->assertNull($result['error_message']);

        // 测试失败响应
        $failureResponse = [
            'code' => '400',
            'error_code' => 'INVALID_ID_CARD',
            'error_message' => '身份证号码无效',
            'confidence' => 0.1,
        ];

        $result = $this->service->handleProviderResponse($failureResponse, $provider);

        $this->assertFalse($result['success']);
        $this->assertEquals(0.1, $result['confidence']);
        $this->assertEquals('INVALID_ID_CARD', $result['error_code']);
        $this->assertEquals('身份证号码无效', $result['error_message']);

        // 测试使用success字段的响应
        $successResponse2 = [
            'success' => true,
            'score' => 0.88,
        ];

        $result = $this->service->handleProviderResponse($successResponse2, $provider);

        $this->assertTrue($result['success']);
        $this->assertEquals(0.88, $result['confidence']);

        // 测试默认错误处理
        $emptyResponse = [];

        $result = $this->service->handleProviderResponse($emptyResponse, $provider);

        $this->assertFalse($result['success']);
        $this->assertNull($result['confidence']);
        $this->assertEquals('UNKNOWN_ERROR', $result['error_code']);
        $this->assertEquals('认证失败', $result['error_message']);
    }

    public function testLogProviderUsage(): void
    {
        $provider = new AuthenticationProvider();
        $provider->setName('测试提供商');
        $provider->setCode('test_provider');
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setSupportedMethods(['id_card_two_elements']);
        $provider->setApiEndpoint('https://api.example.com/verify');
        $provider->setConfig([]);

        // 测试成功使用记录
        $this->service->logProviderUsage($provider, true);

        // 测试失败使用记录
        $this->service->logProviderUsage($provider, false);

        // 由于logProviderUsage方法只记录日志且捕获了所有异常，
        // 这里我们只能验证方法不会抛出异常
        $this->assertTrue(true);
    }
}
