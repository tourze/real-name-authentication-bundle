<?php

namespace Tourze\RealNameAuthenticationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;

/**
 * 导入批次数据填充
 *
 * 创建各种状态的导入批次，用于测试和演示
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class ImportBatchFixtures extends Fixture
{
    public const PENDING_BATCH_REFERENCE = 'pending-batch';
    public const PROCESSING_BATCH_REFERENCE = 'processing-batch';
    public const COMPLETED_BATCH_REFERENCE = 'completed-batch';
    public const FAILED_BATCH_REFERENCE = 'failed-batch';

    public function load(ObjectManager $manager): void
    {
        // 1. 待处理的批次
        $pendingBatch = new ImportBatch();
        $pendingBatch->setOriginalFileName('pending_users.xlsx');
        $pendingBatch->setFileType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $pendingBatch->setFileSize(2048000);
        $pendingBatch->setFileMd5('abcdef1234567890abcdef1234567890');
        $pendingBatch->setTotalRecords(1000);
        $pendingBatch->setImportConfig([
            'sheet_name' => 'Sheet1',
            'has_header' => true,
            'mapping' => [
                'name' => 'A',
                'id_card' => 'B',
                'phone' => 'C',
            ],
        ]);
        $pendingBatch->setRemark('等待开始处理的用户数据批次');
        $manager->persist($pendingBatch);
        $this->addReference(self::PENDING_BATCH_REFERENCE, $pendingBatch);

        // 2. 正在处理的批次
        $processingBatch = new ImportBatch();
        $processingBatch->setOriginalFileName('processing_users.csv');
        $processingBatch->setFileType('text/csv');
        $processingBatch->setFileSize(1536000);
        $processingBatch->setFileMd5('123456abcdef123456abcdef12345678');
        $processingBatch->setTotalRecords(750);
        $processingBatch->setProcessedRecords(450);
        $processingBatch->setSuccessRecords(380);
        $processingBatch->setFailedRecords(70);
        $processingBatch->setImportConfig([
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'has_header' => true,
        ]);
        $processingBatch->startProcessing();
        $processingBatch->setRemark('正在处理中的CSV格式用户数据');
        $manager->persist($processingBatch);
        $this->addReference(self::PROCESSING_BATCH_REFERENCE, $processingBatch);

        // 3. 已完成的批次
        $completedBatch = new ImportBatch();
        $completedBatch->setOriginalFileName('completed_users.xlsx');
        $completedBatch->setFileType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $completedBatch->setFileSize(3072000);
        $completedBatch->setFileMd5('fedcba0987654321fedcba0987654321');
        $completedBatch->setTotalRecords(1500);
        $completedBatch->setProcessedRecords(1500);
        $completedBatch->setSuccessRecords(1485);
        $completedBatch->setFailedRecords(15);
        $completedBatch->setImportConfig([
            'sheet_name' => 'UserData',
            'has_header' => true,
            'validation' => [
                'strict_mode' => true,
                'allow_duplicates' => false,
            ],
        ]);
        $completedBatch->startProcessing();
        $completedBatch->finishProcessing();
        $completedBatch->setRemark('成功完成的大批量用户数据导入');
        $manager->persist($completedBatch);
        $this->addReference(self::COMPLETED_BATCH_REFERENCE, $completedBatch);

        // 4. 失败的批次
        $failedBatch = new ImportBatch();
        $failedBatch->setOriginalFileName('failed_users.txt');
        $failedBatch->setFileType('text/plain');
        $failedBatch->setFileSize(512000);
        $failedBatch->setFileMd5('error123error123error123error123');
        $failedBatch->setTotalRecords(300);
        $failedBatch->setProcessedRecords(50);
        $failedBatch->setSuccessRecords(0);
        $failedBatch->setFailedRecords(50);
        $failedBatch->setImportConfig([
            'format' => 'txt',
            'separator' => '\t',
        ]);
        $failedBatch->startProcessing();
        $failedBatch->markAsFailed('文件格式错误：不支持的分隔符格式');
        $failedBatch->setRemark('因格式问题失败的导入批次');
        $manager->persist($failedBatch);
        $this->addReference(self::FAILED_BATCH_REFERENCE, $failedBatch);

        // 5. 小批量测试数据
        $smallBatch = new ImportBatch();
        $smallBatch->setOriginalFileName('test_sample.json');
        $smallBatch->setFileType('application/json');
        $smallBatch->setFileSize(51200);
        $smallBatch->setFileMd5('small123small123small123small123');
        $smallBatch->setTotalRecords(50);
        $smallBatch->setProcessedRecords(50);
        $smallBatch->setSuccessRecords(48);
        $smallBatch->setFailedRecords(2);
        $smallBatch->setImportConfig([
            'format' => 'json',
            'root_element' => 'users',
            'validate_schema' => true,
        ]);
        $smallBatch->startProcessing();
        $smallBatch->finishProcessing();
        $smallBatch->setRemark('JSON格式的小批量测试数据');
        $manager->persist($smallBatch);

        // 6. 无效的批次（已标记为无效）
        $invalidBatch = new ImportBatch();
        $invalidBatch->setOriginalFileName('invalid_data.csv');
        $invalidBatch->setFileType('text/csv');
        $invalidBatch->setFileSize(0);
        $invalidBatch->setFileMd5('invalid0invalid0invalid0invalid0');
        $invalidBatch->setTotalRecords(0);
        $invalidBatch->setRemark('空文件，已标记为无效');
        $invalidBatch->setValid(false);
        $manager->persist($invalidBatch);

        $manager->flush();
    }
}
