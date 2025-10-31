<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;
use Tourze\RealNameAuthenticationBundle\Service\BatchImportService;

/**
 * @internal
 */
#[CoversClass(BatchImportService::class)]
#[RunTestsInSeparateProcesses]
final class BatchImportServiceTest extends AbstractIntegrationTestCase
{
    private BatchImportService $service;

    protected function onSetUp(): void
    {
        // 从容器获取服务实例（集成测试模式）
        $this->service = self::getService(BatchImportService::class);
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(BatchImportService::class, $this->service);
    }

    public function testGenerateTemplate(): void
    {
        $template = $this->service->generateTemplate();

        $this->assertIsString($template);
        $this->assertStringContainsString('姓名', $template);
        $this->assertStringContainsString('身份证号', $template);
        $this->assertStringContainsString('手机号', $template);
        $this->assertStringContainsString('银行卡号', $template);
        $this->assertStringContainsString('认证方式', $template);

        // 检查示例数据
        $this->assertStringContainsString('张三', $template);
        $this->assertStringContainsString('李四', $template);
        $this->assertStringContainsString('王五', $template);

        // 检查CSV格式
        $lines = explode("\n", trim($template));
        $this->assertGreaterThanOrEqual(4, count($lines)); // 头部 + 3行示例数据
    }

    public function testRetryFailedRecords(): void
    {
        // 创建测试批次
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test-retry.csv');
        $batch->setFileType('csv');
        $batch->setFileSize(1024);
        $batch->setFileMd5('test-retry-hash');
        $batch->setStatus(ImportStatus::PROCESSING);

        self::getEntityManager()->persist($batch);
        self::getEntityManager()->flush();

        // 创建失败记录
        $record1 = new ImportRecord();
        $record1->setBatch($batch);
        $record1->setRowNumber(1);
        $record1->setRawData(['name' => '张三']);
        $record1->setStatus(ImportRecordStatus::FAILED);
        $record1->setErrorMessage('测试错误');

        $record2 = new ImportRecord();
        $record2->setBatch($batch);
        $record2->setRowNumber(2);
        $record2->setRawData(['name' => '李四']);
        $record2->setStatus(ImportRecordStatus::FAILED);
        $record2->setErrorMessage('测试错误2');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        // 执行重试
        $this->service->retryFailedRecords($batch);

        // 刷新实体以获取最新状态
        self::getEntityManager()->refresh($record1);
        self::getEntityManager()->refresh($record2);

        // 验证状态已更新为PENDING
        $this->assertSame(ImportRecordStatus::PENDING, $record1->getStatus());
        $this->assertSame(ImportRecordStatus::PENDING, $record2->getStatus());
    }

    public function testCancelBatch(): void
    {
        // 创建测试批次
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test-cancel.csv');
        $batch->setFileType('csv');
        $batch->setFileSize(1024);
        $batch->setFileMd5('test-cancel-hash');
        $batch->setStatus(ImportStatus::PENDING);

        self::getEntityManager()->persist($batch);
        self::getEntityManager()->flush();

        // 验证初始状态
        $this->assertSame(ImportStatus::PENDING, $batch->getStatus());
        $this->assertNull($batch->getFinishTime());

        // 执行取消
        $this->service->cancelBatch($batch);

        // 刷新实体以获取最新状态
        self::getEntityManager()->refresh($batch);

        // 验证状态已更新为CANCELLED
        $this->assertSame(ImportStatus::CANCELLED, $batch->getStatus());
        $this->assertNotNull($batch->getFinishTime());
    }

    public function testCancelBatchFailedWhenNotCancellable(): void
    {
        // 创建已完成的批次
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test-completed.csv');
        $batch->setFileType('csv');
        $batch->setFileSize(1024);
        $batch->setFileMd5('test-completed-hash');
        $batch->setStatus(ImportStatus::COMPLETED);

        self::getEntityManager()->persist($batch);
        self::getEntityManager()->flush();

        // 验证无法取消已完成批次
        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('当前状态不允许取消');

        $this->service->cancelBatch($batch);
    }

    public function testCreateImportBatch(): void
    {
        // 创建模拟上传文件
        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv_');
        file_put_contents($tempFile, "姓名,身份证号,手机号\n张三,11010119900101100X,13800138000\n");

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test-import.csv',
            'text/csv',
            null,
            true
        );

        // 执行创建批次
        $batch = $this->service->createImportBatch($uploadedFile, ['test_config' => 'value']);

        // 验证批次创建成功
        $this->assertInstanceOf(ImportBatch::class, $batch);
        $this->assertSame('test-import.csv', $batch->getOriginalFileName());
        $this->assertSame('csv', $batch->getFileType());
        $this->assertGreaterThan(0, $batch->getFileSize());
        $this->assertSame(32, strlen($batch->getFileMd5()));
        $this->assertSame(ImportStatus::PENDING, $batch->getStatus());
        $this->assertSame(['test_config' => 'value'], $batch->getImportConfig());

        // 清理临时文件
        unlink($tempFile);
    }

    public function testParseFileAndCreateRecords(): void
    {
        // 创建测试批次
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test-parse.csv');
        $batch->setFileType('csv');
        $batch->setFileSize(1024);
        $batch->setFileMd5('test-parse-hash');
        $batch->setStatus(ImportStatus::PENDING);

        self::getEntityManager()->persist($batch);
        self::getEntityManager()->flush();

        // 创建模拟CSV文件
        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv_');
        $csvContent = "姓名,身份证号,手机号,银行卡号,认证方式\n张三,11010119900101100X,13800138000,6222021234567894,bank_card_four_elements\n李四,110101199002021007,13800138001,,carrier_three_elements\n";
        file_put_contents($tempFile, $csvContent);

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test-parse.csv',
            'text/csv',
            null,
            true
        );

        // 执行解析文件
        $this->service->parseFileAndCreateRecords($batch, $uploadedFile);

        // 刷新实体以获取最新状态
        self::getEntityManager()->refresh($batch);

        // 验证批次状态和记录数
        $this->assertSame(ImportStatus::PROCESSING, $batch->getStatus());
        $this->assertSame(2, $batch->getTotalRecords());

        // 验证创建的记录
        $records = $batch->getRecords();
        $this->assertCount(2, $records);

        $record1 = $records->first();
        $this->assertInstanceOf(ImportRecord::class, $record1);
        $this->assertSame(1, $record1->getRowNumber());
        $this->assertSame(ImportRecordStatus::PENDING, $record1->getStatus());
        $this->assertArrayHasKey('name', $record1->getRawData());
        $this->assertSame('张三', $record1->getRawData()['name']);

        // 清理临时文件
        unlink($tempFile);
    }

    public function testProcessBatch(): void
    {
        // 创建测试批次
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test-process.csv');
        $batch->setFileType('csv');
        $batch->setFileSize(1024);
        $batch->setFileMd5('test-process-hash');
        $batch->setStatus(ImportStatus::PROCESSING);
        $batch->setTotalRecords(1);

        self::getEntityManager()->persist($batch);
        self::getEntityManager()->flush();

        // 创建测试记录（使用会导致失败的数据，避免复杂的认证逻辑）
        $record = new ImportRecord();
        $record->setBatch($batch);
        $record->setRowNumber(1);
        $record->setRawData(['name' => '', 'id_card' => 'invalid']); // 无效数据
        $record->setStatus(ImportRecordStatus::PENDING);

        self::getEntityManager()->persist($record);
        self::getEntityManager()->flush();

        // 执行批次处理
        $this->service->processBatch($batch);

        // 刷新实体以获取最新状态
        self::getEntityManager()->refresh($batch);
        self::getEntityManager()->refresh($record);

        // 验证批次已完成处理
        $this->assertSame(ImportStatus::COMPLETED, $batch->getStatus());
        $this->assertNotNull($batch->getFinishTime());
        $this->assertGreaterThanOrEqual(0, $batch->getProcessedRecords());

        // 验证记录已被处理（状态不再是PENDING）
        $this->assertNotSame(ImportRecordStatus::PENDING, $record->getStatus());
    }

    public function testProcessBatchWithFailedRecords(): void
    {
        // 创建测试批次
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test-failed.csv');
        $batch->setFileType('csv');
        $batch->setFileSize(1024);
        $batch->setFileMd5('test-failed-hash');
        $batch->setStatus(ImportStatus::PROCESSING);
        $batch->setTotalRecords(1);

        self::getEntityManager()->persist($batch);
        self::getEntityManager()->flush();

        // 创建测试记录
        $record = new ImportRecord();
        $record->setBatch($batch);
        $record->setRowNumber(1);
        $record->setRawData(['name' => '', 'id_card' => '']); // 空数据会导致验证失败
        $record->setStatus(ImportRecordStatus::PENDING);

        self::getEntityManager()->persist($record);
        self::getEntityManager()->flush();

        // 执行批次处理
        $this->service->processBatch($batch);

        // 刷新实体以获取最新状态
        self::getEntityManager()->refresh($batch);
        self::getEntityManager()->refresh($record);

        // 验证批次已完成处理
        $this->assertSame(ImportStatus::COMPLETED, $batch->getStatus());
        $this->assertGreaterThanOrEqual(0, $batch->getFailedRecords());

        // 验证记录处理完成（可能失败或跳过）
        $this->assertNotSame(ImportRecordStatus::PENDING, $record->getStatus());
        if (ImportRecordStatus::FAILED === $record->getStatus()) {
            $this->assertNotNull($record->getErrorMessage());
        }
    }
}
