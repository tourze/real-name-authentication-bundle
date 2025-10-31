<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormStatusController;

/**
 * @internal
 */
#[CoversClass(PersonalAuthFormStatusController::class)]
#[RunTestsInSeparateProcesses]
final class PersonalAuthFormStatusControllerTest extends AbstractWebTestCase
{
    public function testGetRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $client->request('GET', '/auth/personal/status/test123');

        // 由于认证记录不存在，控制器会重定向到认证首页
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isRedirect(),
            sprintf('Expected redirect response, got %d', $response->getStatusCode())
        );
        $this->assertEquals('/auth/personal', $response->headers->get('Location'));
    }

    public function testInvalidAuthIdHandling(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $client->request('GET', '/auth/personal/status/invalid');

        // 期望重定向或错误处理
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isRedirect(),
            sprintf('Expected redirect response, got %d', $response->getStatusCode())
        );
        $this->assertEquals('/auth/personal', $response->headers->get('Location'));
    }

    public function testPostMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('POST', '/auth/personal/status/test123');
    }

    public function testPutMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PUT', '/auth/personal/status/test123');
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('DELETE', '/auth/personal/status/test123');
    }

    public function testPatchMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PATCH', '/auth/personal/status/test123');
    }

    public function testHeadMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $client->request('HEAD', '/auth/personal/status/test123');

        // HEAD 请求可能也会返回302，因为认证记录不存在
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_FOUND,
        ]);
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('OPTIONS', '/auth/personal/status/test123');
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/auth/personal/status/test123');

        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK, // 可能直接显示页面
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_FOUND, // 重定向到登录页
        ]);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $client->catchExceptions(false);

        try {
            $client->request($method, '/auth/personal/status/test123');

            // 如果没有抛出异常，检查状态码
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }
}
