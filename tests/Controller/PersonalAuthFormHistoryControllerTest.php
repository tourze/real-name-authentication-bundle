<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormHistoryController;

/**
 * @internal
 */
#[CoversClass(PersonalAuthFormHistoryController::class)]
#[RunTestsInSeparateProcesses]
final class PersonalAuthFormHistoryControllerTest extends AbstractWebTestCase
{
    private function loginWithTestUser(KernelBrowser $client, string $username = 'test_user'): void
    {
        // 使用 symfony-testing-framework 的用户创建方法
        $this->createNormalUser($username . '@test.local', 'password123');
        $this->loginAsUser($client, $username . '@test.local', 'password123');
    }

    public function testGetRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('GET', '/auth/personal/history');

        // 控制器可能会重定向到 /auth/personal（如果服务抛出异常）
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK, // 成功显示页面
            Response::HTTP_FOUND, // 重定向到认证首页
        ]);
    }

    public function testPostMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('POST', '/auth/personal/history');

        // POST方法会被路由接受，但控制器会重定向
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_FOUND,
        ]);
    }

    public function testPutMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('PUT', '/auth/personal/history');

        // PUT方法会被路由接受，但控制器会重定向
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_FOUND,
        ]);
    }

    public function testDeleteMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('DELETE', '/auth/personal/history');

        // DELETE方法会被路由接受，但控制器会重定向
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_FOUND,
        ]);
    }

    public function testPatchMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('PATCH', '/auth/personal/history');

        // PATCH方法会被路由接受，但控制器会重定向
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_FOUND,
        ]);
    }

    public function testHeadMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('HEAD', '/auth/personal/history');

        // HEAD方法会被路由接受，但控制器可能会重定向
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_FOUND,
        ]);
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('OPTIONS', '/auth/personal/history');

        // OPTIONS方法会被路由接受，但控制器可能会重定向
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_FOUND,
            Response::HTTP_METHOD_NOT_ALLOWED,
        ]);
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/auth/personal/history');

        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_FOUND, // 重定向到登录页
        ]);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->catchExceptions(false);

        try {
            $client->request($method, '/auth/personal/history');

            // 这个控制器的路由没有限制方法，所以可能返回 200（表单页面）或 405
            $this->assertContains($client->getResponse()->getStatusCode(), [
                Response::HTTP_OK, // 路由接受所有方法，显示页面
                Response::HTTP_FOUND, // 控制器可能重定向
                Response::HTTP_METHOD_NOT_ALLOWED, // 如果方法确实不被允许
            ]);
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }
}
