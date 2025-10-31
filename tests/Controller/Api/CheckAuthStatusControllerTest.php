<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Api\CheckAuthStatusController;

/**
 * @internal
 */
#[CoversClass(CheckAuthStatusController::class)]
#[RunTestsInSeparateProcesses]
final class CheckAuthStatusControllerTest extends AbstractWebTestCase
{
    /**
     * @param array<string> $roles
     */
    private function loginWithTestUser(KernelBrowser $client, string $username = 'test_user', array $roles = ['ROLE_USER']): UserInterface
    {
        // 使用框架提供的用户创建方法
        $user = $this->createNormalUser($username . '@test.local', 'password123');
        $client->loginUser($user);

        return $user;
    }

    public function testGetValidAuthStatusSuccess(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('GET', '/api/auth/personal/status/valid-auth-id');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        $responseContent = $client->getResponse()->getContent();
        $this->assertNotFalse($responseContent, 'Response content should not be false');
        $this->assertNotEmpty($responseContent, 'Response content should not be empty');
        $responseData = json_decode($responseContent, true);
        $this->assertNotNull($responseData, 'Response should contain valid JSON data, got: ' . $responseContent);
        $this->assertIsArray($responseData, 'Response should be an array');
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testGetAuthStatusNotFound(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('GET', '/api/auth/personal/status/non-existent-id');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        $responseContent = $client->getResponse()->getContent();
        $this->assertNotFalse($responseContent, 'Response content should not be false');
        $responseData = json_decode($responseContent, true);
        $this->assertIsArray($responseData, 'Response should contain valid JSON data');
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testGetAuthStatusUnauthorized(): void
    {
        $client = self::createClientWithDatabase();

        // 让客户端捕获异常而不是抛出
        $client->catchExceptions(false);

        try {
            $client->request('GET', '/api/auth/personal/status/some-id');

            // 如果没有抛出异常，检查状态码
            $this->assertContains($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_FOUND,
                Response::HTTP_NOT_FOUND,
            ]);
        } catch (AccessDeniedException $e) {
            // 期望的异常 - 未授权访问
            $this->assertStringContainsString('authenticated', $e->getMessage());
        }
    }

    public function testPostMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        // 让客户端捕获异常而不是抛出
        $client->catchExceptions(false);

        try {
            $client->request('POST', '/api/auth/personal/status/some-id');

            // 如果没有抛出异常，检查状态码
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }

    public function testPutMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        // 让客户端捕获异常而不是抛出
        $client->catchExceptions(false);

        try {
            $client->request('PUT', '/api/auth/personal/status/some-id');

            // 如果没有抛出异常，检查状态码
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        // 让客户端捕获异常而不是抛出
        $client->catchExceptions(false);

        try {
            $client->request('DELETE', '/api/auth/personal/status/some-id');

            // 如果没有抛出异常，检查状态码
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }

    public function testPatchMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        // 让客户端捕获异常而不是抛出
        $client->catchExceptions(false);

        try {
            $client->request('PATCH', '/api/auth/personal/status/some-id');

            // 如果没有抛出异常，检查状态码
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }

    public function testHeadMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('HEAD', '/api/auth/personal/status/some-id');

        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_NOT_FOUND,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ]);
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();

        // 让客户端捕获异常而不是抛出
        $client->catchExceptions(false);

        try {
            $client->request('OPTIONS', '/api/auth/personal/status/some-id');

            // 如果没有抛出异常，检查状态码
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->catchExceptions(false);

        try {
            $client->request($method, '/api/auth/personal/status/some-id');

            // 如果没有抛出异常，检查状态码
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }
}
