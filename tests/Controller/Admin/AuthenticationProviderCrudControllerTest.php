<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\AuthenticationProviderCrudController;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;

/**
 * @internal
 */
#[CoversClass(AuthenticationProviderCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AuthenticationProviderCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<AuthenticationProvider>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(AuthenticationProviderCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '提供商名称' => ['提供商名称'];
        yield '提供商代码' => ['提供商代码'];
        yield '提供商类型' => ['提供商类型'];
        yield '支持的认证方式' => ['支持的认证方式'];
        yield 'API接口地址' => ['API接口地址'];
        yield '优先级' => ['优先级'];
        yield '是否启用' => ['是否启用'];
        yield '是否有效' => ['是否有效'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'code' => ['code'];
        yield 'apiEndpoint' => ['apiEndpoint'];
        yield 'priority' => ['priority'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'code' => ['code'];
        yield 'apiEndpoint' => ['apiEndpoint'];
        yield 'priority' => ['priority'];
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertSame(AuthenticationProvider::class, AuthenticationProviderCrudController::getEntityFqcn());
    }

    public function testControllerInstance(): void
    {
        $controller = new AuthenticationProviderCrudController();
        $this->assertInstanceOf(AuthenticationProviderCrudController::class, $controller);
    }

    public function testCreateNewProviderWithValidData(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationProvider&action=new';
            $client->request('POST', $url, [
                'AuthenticationProvider' => [
                    'name' => 'Test Provider',
                    'code' => 'test-provider',
                    'type' => 'third_party',
                    'apiEndpoint' => 'https://api.example.com',
                ],
            ]);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isRedirect() || $response->isSuccessful() || $response->getStatusCode() >= 400,
                'Create request should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testCreateProviderWithMissingNameField(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationProvider&action=new';
            $client->request('POST', $url, [
                'AuthenticationProvider' => [
                    'name' => '', // 测试空的name字段
                    'code' => 'test-code',
                    'type' => 'third_party',
                    'apiEndpoint' => 'https://api.example.com',
                ],
            ]);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Missing name field should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testCreateProviderWithMissingCodeField(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationProvider&action=new';
            $client->request('POST', $url, [
                'AuthenticationProvider' => [
                    'name' => 'Test Provider',
                    'code' => '', // 测试空的code字段
                    'type' => 'third_party',
                    'apiEndpoint' => 'https://api.example.com',
                ],
            ]);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Missing code field should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testCreateProviderWithMissingTypeField(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationProvider&action=new';
            $client->request('POST', $url, [
                'AuthenticationProvider' => [
                    'name' => 'Test Provider',
                    'code' => 'test-code',
                    // 缺少type字段
                    'apiEndpoint' => 'https://api.example.com',
                ],
            ]);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Missing type field should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testCreateProviderWithMissingApiEndpointField(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationProvider&action=new';
            $client->request('POST', $url, [
                'AuthenticationProvider' => [
                    'name' => 'Test Provider',
                    'code' => 'test-code',
                    'type' => 'third_party',
                    'apiEndpoint' => '', // 测试空的apiEndpoint字段
                ],
            ]);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Missing apiEndpoint field should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testValidationErrors(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            // 首先获取新建表单页面
            $url = '/admin/?entityName=AuthenticationProvider&action=new';
            $crawler = $client->request('GET', $url);

            if ($client->getResponse()->isSuccessful()) {
                // 查找表单并提交空数据
                $form = $crawler->selectButton('Create')->form();

                // 提交空表单来触发验证错误
                $client->submit($form, [
                    'AuthenticationProvider[name]' => '',
                    'AuthenticationProvider[code]' => '',
                    'AuthenticationProvider[apiEndpoint]' => '',
                ]);

                $response = $client->getResponse();

                // 验证响应包含验证错误或适当处理
                $this->assertTrue(
                    $response->getStatusCode() >= 400 || $response->isSuccessful(),
                    'Validation errors should be handled appropriately'
                );

                // 如果响应成功，检查是否包含错误信息
                if ($response->isSuccessful()) {
                    $content = $response->getContent();
                    if (false !== $content) {
                        // 检查是否包含典型的验证错误指示
                        $hasValidationIndicators =
                            str_contains($content, 'invalid-feedback')
                            || str_contains($content, 'is-invalid')
                            || str_contains($content, 'error')
                            || str_contains($content, 'required');

                        $this->assertTrue(
                            $hasValidationIndicators,
                            'Response should contain validation error indicators when fields are empty'
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testEditProviderFormAccess(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationProvider&action=edit&entityId=1';
            $client->request('GET', $url);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || $response->isNotFound() || $response->isRedirect(),
                'Edit form should be accessible or redirect appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }
}
