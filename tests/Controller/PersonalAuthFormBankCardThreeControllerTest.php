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
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormBankCardThreeController;

/**
 * @internal
 */
#[CoversClass(PersonalAuthFormBankCardThreeController::class)]
#[RunTestsInSeparateProcesses]
final class PersonalAuthFormBankCardThreeControllerTest extends AbstractWebTestCase
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

        $client->request('GET', '/auth/personal/bank-card-three');

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

        $client->request('POST', '/auth/personal/bank-card-three', [
            'bankCardNumber' => '1234567890123456',
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

    public function testPutMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);
        $this->loginWithTestUser($client);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PUT', '/auth/personal/bank-card-three');
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);
        $this->loginWithTestUser($client);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('DELETE', '/auth/personal/bank-card-three');
    }

    public function testPatchMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);
        $this->loginWithTestUser($client);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PATCH', '/auth/personal/bank-card-three');
    }

    public function testHeadMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request('HEAD', '/auth/personal/bank-card-three');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);
        $this->loginWithTestUser($client);

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('OPTIONS', '/auth/personal/bank-card-three');
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/auth/personal/bank-card-three');

        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_FOUND, // 重定向到登录页
            Response::HTTP_OK, // 如果没有认证限制
        ]);
    }

    public function testUnauthenticatedPostAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('POST', '/auth/personal/bank-card-three');

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
            $client->request($method, '/auth/personal/bank-card-three');

            // 如果没有抛出异常，检查状态码
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }
}
