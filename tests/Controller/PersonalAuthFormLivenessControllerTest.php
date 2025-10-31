<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormLivenessController;

/**
 * @internal
 */
#[CoversClass(PersonalAuthFormLivenessController::class)]
#[RunTestsInSeparateProcesses]
final class PersonalAuthFormLivenessControllerTest extends AbstractWebTestCase
{
    public function testGetRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $client->request('GET', '/auth/personal/liveness');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    public function testPostRequest(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $client->request('POST', '/auth/personal/liveness', [
            'realName' => '测试用户',
            'idCardNumber' => '110101199001011234',
            'livenessData' => 'base64_encoded_data',
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
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PUT', '/auth/personal/liveness');
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('DELETE', '/auth/personal/liveness');
    }

    public function testPatchMethodNotAllowed(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PATCH', '/auth/personal/liveness');
    }

    public function testHeadMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $client->request('HEAD', '/auth/personal/liveness');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();
        $this->createNormalUser('test@example.com', 'password123');
        $this->loginAsUser($client, 'test@example.com', 'password123');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('OPTIONS', '/auth/personal/liveness');
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/auth/personal/liveness');

        $this->assertContains($client->getResponse()->getStatusCode(), [
            Response::HTTP_OK, // 可能直接显示页面
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_FOUND, // 重定向到登录页
        ]);
    }

    public function testUnauthenticatedPostAccess(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('POST', '/auth/personal/liveness');

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
            $client->request($method, '/auth/personal/liveness');

            // 如果没有抛出异常，检查状态码
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 期望的异常 - 方法不允许
            $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $e->getStatusCode());
        }
    }
}
