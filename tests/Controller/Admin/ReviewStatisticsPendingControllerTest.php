<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\ReviewStatisticsPendingController;

/**
 * @internal
 */
#[CoversClass(ReviewStatisticsPendingController::class)]
#[RunTestsInSeparateProcesses]
final class ReviewStatisticsPendingControllerTest extends AbstractWebTestCase
{
    public function testUnauthenticatedAccessReturnsRedirect(): void
    {
        $client = self::createClient();

        // 未认证用户访问管理员页面应该被拒绝或重定向
        $client->catchExceptions(false);

        try {
            $client->request('GET', '/admin/auth/statistics/pending');

            // 如果没有抛出异常，检查是否被重定向
            $this->assertTrue(
                $client->getResponse()->isRedirect()
                || in_array($client->getResponse()->getStatusCode(), [401, 403], true)
            );
        } catch (AccessDeniedException $e) {
            // 期望的异常 - 未授权访问
            $this->assertStringContainsString('ROLE_ADMIN', $e->getMessage());
        }
    }

    public function testGetRequestWithAuthentication(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('GET', '/admin/auth/statistics/pending');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    public function testPostRequestWithAuthentication(): void
    {
        $client = self::createAuthenticatedClient();
        $client->catchExceptions(true);

        $client->request('POST', '/admin/auth/statistics/pending');

        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    public function testPutRequestWithAuthentication(): void
    {
        $client = self::createAuthenticatedClient();
        $client->catchExceptions(true);

        $client->request('PUT', '/admin/auth/statistics/pending');

        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    public function testDeleteRequestWithAuthentication(): void
    {
        $client = self::createAuthenticatedClient();
        $client->catchExceptions(true);

        $client->request('DELETE', '/admin/auth/statistics/pending');

        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    public function testPatchRequestWithAuthentication(): void
    {
        $client = self::createAuthenticatedClient();
        $client->catchExceptions(true);

        $client->request('PATCH', '/admin/auth/statistics/pending');

        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    public function testHeadRequestWithAuthentication(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('HEAD', '/admin/auth/statistics/pending');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    public function testOptionsRequestWithAuthentication(): void
    {
        $client = self::createAuthenticatedClient();
        $client->catchExceptions(true); // 让Symfony处理异常并返回HTTP响应

        $client->request('OPTIONS', '/admin/auth/statistics/pending');

        $this->assertTrue(
            $client->getResponse()->isSuccessful() || 405 === $client->getResponse()->getStatusCode(),
            'Options request should succeed or return 405 Method Not Allowed'
        );
    }

    public function testWithCustomPagination(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('GET', '/admin/auth/statistics/pending', [
            'limit' => '50',
            'page' => '2',
        ]);

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    public function testWithInvalidPaginationParams(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('GET', '/admin/auth/statistics/pending', [
            'limit' => 'invalid',
            'page' => 'invalid',
        ]);

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    public function testWithLimitBoundaries(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('GET', '/admin/auth/statistics/pending', [
            'limit' => '0',
        ]);

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    public function testWithLargeLimit(): void
    {
        $client = self::createAuthenticatedClient();

        $client->request('GET', '/admin/auth/statistics/pending', [
            'limit' => '500',
        ]);

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createAuthenticatedClient();
        $client->catchExceptions(true); // 让Symfony处理异常并返回HTTP响应
        $client->request($method, '/admin/auth/statistics/pending');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    /**
     * 创建已认证的客户端（管理员身份）
     */
    protected static function createAuthenticatedClient(string $username = 'admin', string $password = 'password'): KernelBrowser
    {
        $client = self::createClient();
        // 使用内存用户登录，避免实例化测试类
        $user = new InMemoryUser($username, '', ['ROLE_ADMIN']);
        $client->loginUser($user, 'main');

        return $client;
    }
}
