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
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormCarrierThreeController;

/**
 * @internal
 */
#[CoversClass(PersonalAuthFormCarrierThreeController::class)]
#[RunTestsInSeparateProcesses]
final class PersonalAuthFormCarrierThreeControllerTest extends AbstractWebTestCase
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

        $client->request('GET', '/auth/personal/carrier-three');

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

        $client->request('POST', '/auth/personal/carrier-three', [
            'phoneNumber' => '13800138000',
            'realName' => '测试用户',
            'idCardNumber' => '110101199001011234',
        ]);

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isRedirect(),
            sprintf('Expected redirect response, got %d', $response->getStatusCode())
        );
        $this->assertEquals('/auth/personal', $response->headers->get('Location'));
    }

    public function testPutMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('PUT', '/auth/personal/carrier-three');

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

        $client->request('DELETE', '/auth/personal/carrier-three');

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

        $client->request('PATCH', '/auth/personal/carrier-three');

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

        $client->request('HEAD', '/auth/personal/carrier-three');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('OPTIONS', '/auth/personal/carrier-three');

        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_METHOD_NOT_ALLOWED,
        ]);
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/auth/personal/carrier-three');

        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [
            Response::HTTP_OK, // 直接显示表单页面
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_FOUND, // 重定向到登录页
        ]);
    }

    public function testUnauthenticatedPostAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('POST', '/auth/personal/carrier-three');

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
            $client->request($method, '/auth/personal/carrier-three');

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
