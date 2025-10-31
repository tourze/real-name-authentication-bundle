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
use Tourze\RealNameAuthenticationBundle\Controller\Api\GetSupportedMethodsController;

/**
 * @internal
 */
#[CoversClass(GetSupportedMethodsController::class)]
#[RunTestsInSeparateProcesses]
final class GetSupportedMethodsControllerTest extends AbstractWebTestCase
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

    public function testGetSupportedMethodsSuccess(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('GET', '/api/auth/personal/methods');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $responseData = json_decode(false !== $content ? $content : '', true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertTrue($responseData['success']);
        $this->assertIsArray($responseData['data']);
    }

    public function testGetSupportedMethodsWithAuthentication(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('GET', '/api/auth/personal/methods');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $responseData = json_decode(false !== $content ? $content : '', true);
        /** @var array<string, mixed> $responseData */
        $this->assertTrue($responseData['success']);
        $this->assertIsArray($responseData['data']);
    }

    public function testResponseStructure(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('GET', '/api/auth/personal/methods');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $responseData = json_decode(false !== $content ? $content : '', true);
        $this->assertIsArray($responseData);

        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertTrue($responseData['success']);
        $this->assertIsArray($responseData['data']);

        if (count($responseData['data']) > 0) {
            foreach ($responseData['data'] as $method) {
                $this->assertIsArray($method);
                $this->assertArrayHasKey('value', $method);
                $this->assertArrayHasKey('label', $method);
                $this->assertArrayHasKey('requiredFields', $method);
                $this->assertIsString($method['value']);
                $this->assertIsString($method['label']);
                $this->assertIsArray($method['requiredFields']);
            }
        }
    }

    public function testPostMethodNotAllowed(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/auth/personal/methods');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testPutMethodNotAllowed(): void
    {
        $client = self::createClient();

        $client->request('PUT', '/api/auth/personal/methods');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $client = self::createClient();

        $client->request('DELETE', '/api/auth/personal/methods');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testPatchMethodNotAllowed(): void
    {
        $client = self::createClient();

        $client->request('PATCH', '/api/auth/personal/methods');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testHeadMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('HEAD', '/api/auth/personal/methods');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();

        // 让客户端捕获异常而不是抛出
        $client->catchExceptions(false);

        try {
            $client->request('OPTIONS', '/api/auth/personal/methods');

            $this->assertContains($client->getResponse()->getStatusCode(), [
                Response::HTTP_OK,
                Response::HTTP_METHOD_NOT_ALLOWED,
            ]);
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = self::createClientWithDatabase();

        // 让客户端捕获异常而不是抛出
        $client->catchExceptions(false);

        try {
            $client->request('GET', '/api/auth/personal/methods');

            // 如果没有抛出异常，检查状态码
            $this->assertContains($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_FORBIDDEN,
                Response::HTTP_FOUND,
            ]);
        } catch (AccessDeniedException $e) {
            // 期望的异常 - 未授权访问
            $this->assertStringContainsString('authenticated', $e->getMessage());
        }
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->catchExceptions(false);

        try {
            $client->request($method, '/api/auth/personal/methods');

            // 如果没有抛出异常，检查状态码
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }
}
