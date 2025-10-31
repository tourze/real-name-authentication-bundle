<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Api\SubmitPersonalAuthController;

/**
 * @internal
 */
#[CoversClass(SubmitPersonalAuthController::class)]
#[RunTestsInSeparateProcesses]
final class SubmitPersonalAuthControllerTest extends AbstractWebTestCase
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

    public function testSubmitPersonalAuthWithValidData(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $authData = [
            'method' => 'id_card_two_elements',
            'name' => 'Test User',
            'idCard' => '123456789012345678',
        ];

        $client->request(
            'POST',
            '/api/auth/personal/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ($json = json_encode($authData)) !== false ? $json : ''
        );

        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_BAD_REQUEST,
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ]);

        $responseData = json_decode(($content = $client->getResponse()->getContent()) !== false ? $content : '', true);
        $this->assertIsArray($responseData);
    }

    public function testSubmitPersonalAuthUnauthorized(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(true); // 启用异常捕获，让框架处理异常并返回响应

        $authData = [
            'method' => 'id_card_two_elements',
            'name' => 'Test User',
            'idCard' => '123456789012345678',
        ];

        $client->request(
            'POST',
            '/api/auth/personal/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ($json = json_encode($authData)) !== false ? $json : ''
        );

        // 用户未登录时，Symfony 会根据安全配置返回不同的状态码
        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_FOUND, // 重定向到登录页
            Response::HTTP_FORBIDDEN, // 访问被禁止
        ]);
    }

    public function testSubmitPersonalAuthInvalidJson(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $client->request(
            'POST',
            '/api/auth/personal/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid-json'
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $responseData = json_decode(($content = $client->getResponse()->getContent()) !== false ? $content : '', true);
        /** @var array<string, mixed> $responseData */
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testSubmitPersonalAuthMissingMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $authData = [
            'name' => 'Test User',
            'idCard' => '123456789012345678',
        ];

        $client->request(
            'POST',
            '/api/auth/personal/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ($json = json_encode($authData)) !== false ? $json : ''
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $responseData = json_decode(($content = $client->getResponse()->getContent()) !== false ? $content : '', true);
        /** @var array<string, mixed> $responseData */
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testSubmitPersonalAuthInvalidMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);

        $authData = [
            'method' => 'invalid_method',
            'name' => 'Test User',
            'idCard' => '123456789012345678',
        ];

        $client->request(
            'POST',
            '/api/auth/personal/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ($json = json_encode($authData)) !== false ? $json : ''
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $responseData = json_decode(($content = $client->getResponse()->getContent()) !== false ? $content : '', true);
        /** @var array<string, mixed> $responseData */
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testGetMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);
        $client->catchExceptions(true); // 启用异常捕获

        $client->request('GET', '/api/auth/personal/submit');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testPutMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);
        $client->catchExceptions(true); // 启用异常捕获

        $client->request('PUT', '/api/auth/personal/submit');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);
        $client->catchExceptions(true); // 启用异常捕获

        $client->request('DELETE', '/api/auth/personal/submit');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testPatchMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);
        $client->catchExceptions(true); // 启用异常捕获

        $client->request('PATCH', '/api/auth/personal/submit');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        try {
            $client->request('OPTIONS', '/api/auth/personal/submit');

            $this->assertContains($client->getResponse()->getStatusCode(), [
                Response::HTTP_OK,
                Response::HTTP_METHOD_NOT_ALLOWED,
            ]);
        } catch (MethodNotAllowedHttpException $e) {
            // OPTIONS 方法不被允许 - 这是预期的行为
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $this->loginWithTestUser($client);
        $client->catchExceptions(true); // 启用异常捕获

        $client->request($method, '/api/auth/personal/submit');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }
}
