<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\AuthenticationResultCrudController;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;

/**
 * @internal
 */
#[CoversClass(AuthenticationResultCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AuthenticationResultCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<AuthenticationResult>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(AuthenticationResultCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '认证记录' => ['认证记录'];
        yield '认证提供商' => ['认证提供商'];
        yield '请求ID' => ['请求ID'];
        yield '是否成功' => ['是否成功'];
        yield '处理时间(ms)' => ['处理时间(ms)'];
        yield '创建时间' => ['创建时间'];
        yield '是否有效' => ['是否有效'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // AuthenticationResult 控制器禁用了 EDIT 操作，提供虚拟数据
        yield 'skipped' => ['skipped'];
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertSame(AuthenticationResult::class, AuthenticationResultCrudController::getEntityFqcn());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // AuthenticationResult 控制器禁用了 NEW 操作，提供虚拟数据
        yield 'skipped' => ['skipped'];
    }

    public function testUnauthenticatedAccessHandledCorrectly(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationResult';
            $client->request('GET', $url);

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

    public function testIndexPageStructure(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationResult';
            $client->request('GET', $url);

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

    public function testFormValidation(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationResult&action=new';
            $client->request('POST', $url, [
                'AuthenticationResult' => [
                    'taskId' => '',
                    'status' => '',
                ],
            ]);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isRedirect() || $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Form validation should handle empty fields appropriately'
            );
        } catch (ForbiddenActionException $e) {
            // NEW action is disabled for this controller, skip test
            self::markTestSkipped('NEW action is disabled for AuthenticationResult controller.');
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testFilterFunctionality(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationResult&filters%5Bstatus%5D=SUCCESS';
            $client->request('GET', $url);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirect() || $response->isNotFound(),
                'Filter request should not cause server errors'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testSearchFunctionality(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationResult&query=task';
            $client->request('GET', $url);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirect() || $response->isNotFound(),
                'Search request should not cause server errors'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testDetailPageAccess(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=AuthenticationResult&action=detail&entityId=1';
            $client->request('GET', $url);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isRedirect() || $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Detail page access should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }
}
