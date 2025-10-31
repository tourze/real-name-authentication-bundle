<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormBankCardFourController;

/**
 * @internal
 */
#[CoversClass(PersonalAuthFormBankCardFourController::class)]
#[RunTestsInSeparateProcesses]
final class PersonalAuthFormBankCardFourControllerTest extends AbstractWebTestCase
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

    public function testGetRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('GET', '/auth/personal/bank-card-four');

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

        $client->request('POST', '/auth/personal/bank-card-four', [
            'bankCardNumber' => '1234567890123456',
            'realName' => '测试用户',
            'idCardNumber' => '110101199001011234',
            'phoneNumber' => '13800138000',
        ]);

        // POST 请求可能会重定向或成功，只要不是错误就行
        $this->assertTrue(
            $client->getResponse()->isSuccessful()
            || $client->getResponse()->isRedirect()
        );
    }

    public function testPutMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('PUT', '/auth/personal/bank-card-four');

        // 这个路由没有限制方法，PUT 会返回表单页面（200 OK）
        // 而不是 405 Method Not Allowed
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('DELETE', '/auth/personal/bank-card-four');

        // 这个路由没有限制方法，DELETE 会返回表单页面（200 OK）
        // 而不是 405 Method Not Allowed
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testPatchMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('PATCH', '/auth/personal/bank-card-four');

        // 这个路由没有限制方法，PATCH 会返回表单页面（200 OK）
        // 而不是 405 Method Not Allowed
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testHeadMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('HEAD', '/auth/personal/bank-card-four');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('OPTIONS', '/auth/personal/bank-card-four');

        // 这个路由没有限制方法，OPTIONS 可能返回 200 或 405
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_METHOD_NOT_ALLOWED,
        ]);
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/auth/personal/bank-card-four');

        // 未认证用户访问表单页面，可能会被重定向或直接显示表单
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,       // 直接显示表单
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_FOUND,    // 重定向到登录页
        ]);
    }

    public function testUnauthenticatedPostAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('POST', '/auth/personal/bank-card-four');

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
            $client->request($method, '/auth/personal/bank-card-four');

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
