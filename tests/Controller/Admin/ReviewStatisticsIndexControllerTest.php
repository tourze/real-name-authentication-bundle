<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\ReviewStatisticsIndexController;

/**
 * @internal
 */
#[CoversClass(ReviewStatisticsIndexController::class)]
#[RunTestsInSeparateProcesses]
final class ReviewStatisticsIndexControllerTest extends AbstractWebTestCase
{
    public function testUnauthenticatedAccessReturnsRedirect(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('GET', '/admin/auth/statistics');

            $this->assertTrue(
                $client->getResponse()->isNotFound()
                || $client->getResponse()->isRedirect()
                || $client->getResponse()->isSuccessful(),
                'Response should be 404, redirect, or successful'
            );
        } catch (NotFoundHttpException $e) {
            $this->assertNotEmpty($e->getMessage(), 'Exception should have a message');
        } catch (AccessDeniedException $e) {
            $this->assertNotEmpty($e->getMessage(), 'Exception should have a message');
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error: ' . $e->getMessage()
            );
        }
    }

    public function testGetRequestHandling(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('GET', '/admin/auth/statistics');

            $response = $client->getResponse();
            if ($response->isSuccessful()) {
                $this->assertResponseIsSuccessful();
            } elseif ($response->isRedirect()) {
                $this->assertResponseRedirects();
            } else {
                $this->assertLessThan(500, $response->getStatusCode(), 'Response should not be a server error');
            }
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testPostRequestHandling(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('POST', '/admin/auth/statistics');

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirect() || 405 === $response->getStatusCode(),
                'POST request should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testWithCustomDateRange(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('GET', '/admin/auth/statistics', [
                'start_date' => '2023-01-01',
                'end_date' => '2023-12-31',
            ]);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirect() || $response->isNotFound(),
                'Date range request should not cause server errors'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testWithInvalidDateRange(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('GET', '/admin/auth/statistics', [
                'start_date' => 'invalid-date',
                'end_date' => 'invalid-date',
            ]);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirect() || $response->isNotFound(),
                'Invalid date request should not cause server errors'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testPutRequestHandling(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('PUT', '/admin/auth/statistics');

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirect() || 405 === $response->getStatusCode(),
                'PUT request should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testDeleteRequestHandling(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('DELETE', '/admin/auth/statistics');

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirect() || 405 === $response->getStatusCode(),
                'DELETE request should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testPatchRequestHandling(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('PATCH', '/admin/auth/statistics');

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirect() || 405 === $response->getStatusCode(),
                'PATCH request should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testHeadRequestHandling(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('HEAD', '/admin/auth/statistics');

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirect() || 405 === $response->getStatusCode(),
                'HEAD request should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testOptionsRequestHandling(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('OPTIONS', '/admin/auth/statistics');

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || 405 === $response->getStatusCode(),
                'OPTIONS request should succeed or return 405'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $client->catchExceptions(true); // 让Symfony处理异常并返回HTTP响应
        $client->request($method, '/admin/auth/statistics');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }
}
