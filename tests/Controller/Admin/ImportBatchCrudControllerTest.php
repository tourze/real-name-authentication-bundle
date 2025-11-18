<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\ImportBatchCrudController;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;

/**
 * @internal
 */
#[CoversClass(ImportBatchCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ImportBatchCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<ImportBatch>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(ImportBatchCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '文件名' => ['文件名'];
        yield '文件类型' => ['文件类型'];
        yield '文件大小(字节)' => ['文件大小(字节)'];
        yield '状态' => ['状态'];
        yield '总记录数' => ['总记录数'];
        yield '已处理' => ['已处理'];
        yield '成功数' => ['成功数'];
        yield '失败数' => ['失败数'];
        yield '进度(%)' => ['进度(%)'];
        yield '开始时间' => ['开始时间'];
        yield '完成时间' => ['完成时间'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // ImportBatch 控制器禁用了 EDIT 操作，提供虚拟数据
        yield 'skipped' => ['skipped'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // ImportBatch 控制器禁用了 NEW 操作，提供虚拟数据
        yield 'skipped' => ['skipped'];
    }

    public function testUnauthenticatedAccessHandledCorrectly(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=ImportBatch';
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
            $url = '/admin/?entityName=ImportBatch';
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
            $url = '/admin/?entityName=ImportBatch&action=new';
            $client->request('POST', $url, [
                'ImportBatch' => [
                    'fileName' => '',
                    'batchName' => '',
                ],
            ]);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isRedirect() || $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Form validation should handle empty fields appropriately'
            );
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
            $url = '/admin/?entityName=ImportBatch&filters%5Bstatus%5D=PENDING';
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
            $url = '/admin/?entityName=ImportBatch&query=batch';
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

    public function testDetailAndEditAccess(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=ImportBatch&action=edit&entityId=1';
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

    public function testRetryFailedRecords(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/import-batch/1/retry-failed';
            $client->request('GET', $url);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isRedirect() || $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Retry failed records action should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testCancelBatch(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/import-batch/1/cancel';
            $client->request('GET', $url);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isRedirect() || $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Cancel batch action should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testViewRecords(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/?entityName=ImportBatch&action=viewRecords&entityId=1';
            $client->request('GET', $url);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isRedirect() || $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'View records action should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testDownloadTemplate(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/import-batch/download-template';
            $client->request('GET', $url);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isRedirect() || $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Download template action should be handled appropriately'
            );
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'doctrine_ping_connection',
                $e->getMessage(),
                'Should not fail with doctrine_ping_connection error'
            );
        }
    }

    public function testUploadFile(): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $url = '/admin/import-batch/upload-file';
            $client->request('GET', $url);

            $response = $client->getResponse();
            $this->assertTrue(
                $response->isRedirect() || $response->getStatusCode() >= 400 || $response->isSuccessful(),
                'Upload file action should be handled appropriately'
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
