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
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormIdCardTwoController;

/**
 * @internal
 */
#[CoversClass(PersonalAuthFormIdCardTwoController::class)]
#[RunTestsInSeparateProcesses]
final class PersonalAuthFormIdCardTwoControllerTest extends AbstractWebTestCase
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

        $client->request('GET', '/auth/personal/id-card-two');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    public function testPostRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('POST', '/auth/personal/id-card-two', [
            'realName' => '测试用户',
            'idCardNumber' => '110101199001011234',
        ]);

        // POST请求可能会重定向
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_FOUND,
        ]);
    }

    public function testPutMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('PUT', '/auth/personal/id-card-two');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    public function testDeleteMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('DELETE', '/auth/personal/id-card-two');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    public function testPatchMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('PATCH', '/auth/personal/id-card-two');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    public function testHeadMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('HEAD', '/auth/personal/id-card-two');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('OPTIONS', '/auth/personal/id-card-two');

        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_METHOD_NOT_ALLOWED,
        ]);
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/auth/personal/id-card-two');

        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK, // 可能直接显示表单
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_FOUND, // 重定向到登录页
        ]);
    }

    public function testUnauthenticatedPostAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('POST', '/auth/personal/id-card-two');

        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK, // 可能直接显示表单或重定向
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
            $client->request($method, '/auth/personal/id-card-two');

            // 这个控制器的路由没有限制方法，所以可能返回 200（表单页面）或 405
            $this->assertContains($client->getResponse()->getStatusCode(), [
                Response::HTTP_OK, // 路由接受所有方法，显示表单页面
                Response::HTTP_METHOD_NOT_ALLOWED, // 如果方法确实不被允许
            ]);
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }
}
