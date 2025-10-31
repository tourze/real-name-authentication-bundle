<?php

namespace Tourze\RealNameAuthenticationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;

/**
 * 导入记录数据填充
 *
 * 创建各种状态的导入记录，用于测试和演示
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class ImportRecordFixtures extends Fixture implements DependentFixtureInterface
{
    public const SUCCESS_RECORD_REFERENCE = 'success-record';
    public const FAILED_RECORD_REFERENCE = 'failed-record';
    public const SKIPPED_RECORD_REFERENCE = 'skipped-record';
    public const PENDING_RECORD_REFERENCE = 'pending-record';

    public function load(ObjectManager $manager): void
    {
        $processingBatch = $this->getReference(ImportBatchFixtures::PROCESSING_BATCH_REFERENCE, ImportBatch::class);
        $completedBatch = $this->getReference(ImportBatchFixtures::COMPLETED_BATCH_REFERENCE, ImportBatch::class);
        $failedBatch = $this->getReference(ImportBatchFixtures::FAILED_BATCH_REFERENCE, ImportBatch::class);
        $pendingBatch = $this->getReference(ImportBatchFixtures::PENDING_BATCH_REFERENCE, ImportBatch::class);

        // 1. 成功的记录（关联到已完成的批次）
        $successRecord = new ImportRecord();
        $successRecord->setBatch($completedBatch);
        $successRecord->setRowNumber(1);
        $successRecord->setRawData([
            'name' => '张三',
            'id_card' => '110101199001011234',
            'phone' => '13800138000',
            'email' => 'zhangsan@test.local',
        ]);
        $successRecord->setProcessedData([
            'name' => '张三',
            'id_card' => '110101199001011234',
            'phone' => '13800138000',
            'email' => 'zhangsan@test.local',
            'normalized_name' => '张三',
            'region_code' => '110101',
        ]);
        $successRecord->setProcessingTime(150);
        $successRecord->setStatus(ImportRecordStatus::SUCCESS);
        $manager->persist($successRecord);
        $this->addReference(self::SUCCESS_RECORD_REFERENCE, $successRecord);

        // 2. 失败的记录（关联到正在处理的批次）
        $failedRecord = new ImportRecord();
        $failedRecord->setBatch($processingBatch);
        $failedRecord->setRowNumber(25);
        $failedRecord->setRawData([
            'name' => '李四',
            'id_card' => '12345678901234567X',  // 无效身份证号
            'phone' => '1380013800x',  // 无效手机号
            'email' => 'invalid-email',  // 无效邮箱
        ]);
        $failedRecord->setErrorMessage('数据验证失败');
        $failedRecord->setValidationErrors([
            'id_card' => ['身份证号码格式错误'],
            'phone' => ['手机号码格式错误'],
            'email' => ['邮箱格式错误'],
        ]);
        $failedRecord->setProcessingTime(80);
        $failedRecord->setStatus(ImportRecordStatus::FAILED);
        $manager->persist($failedRecord);
        $this->addReference(self::FAILED_RECORD_REFERENCE, $failedRecord);

        // 3. 跳过的记录（关联到已完成的批次）
        $skippedRecord = new ImportRecord();
        $skippedRecord->setBatch($completedBatch);
        $skippedRecord->setRowNumber(100);
        $skippedRecord->setRawData([
            'name' => '王五',
            'id_card' => '110101199001011234',  // 重复的身份证号
            'phone' => '13800138001',
            'email' => 'wangwu@test.local',
        ]);
        $skippedRecord->setRemark('身份证号已存在，跳过处理');
        $skippedRecord->setProcessingTime(25);
        $skippedRecord->setStatus(ImportRecordStatus::SKIPPED);
        $manager->persist($skippedRecord);
        $this->addReference(self::SKIPPED_RECORD_REFERENCE, $skippedRecord);

        // 4. 待处理的记录（关联到待处理的批次）
        $pendingRecord = new ImportRecord();
        $pendingRecord->setBatch($pendingBatch);
        $pendingRecord->setRowNumber(1);
        $pendingRecord->setRawData([
            'name' => '赵六',
            'id_card' => '110101199002021234',
            'phone' => '13800138002',
            'email' => 'zhaoliu@test.local',
        ]);
        $pendingRecord->setStatus(ImportRecordStatus::PENDING);
        $manager->persist($pendingRecord);
        $this->addReference(self::PENDING_RECORD_REFERENCE, $pendingRecord);

        // 5. 更多的成功记录（模拟批量数据）
        for ($i = 2; $i <= 10; ++$i) {
            $record = new ImportRecord();
            $record->setBatch($completedBatch);
            $record->setRowNumber($i);
            $record->setRawData([
                'name' => "测试用户{$i}",
                'id_card' => sprintf('11010119900101%04d', $i),
                'phone' => sprintf('1380013%04d', $i),
                'email' => "user{$i}@test.local",
            ]);
            $record->setProcessedData([
                'name' => "测试用户{$i}",
                'id_card' => sprintf('11010119900101%04d', $i),
                'phone' => sprintf('1380013%04d', $i),
                'email' => "user{$i}@test.local",
                'normalized_name' => "测试用户{$i}",
                'region_code' => '110101',
            ]);
            $record->setProcessingTime(rand(100, 300));
            $record->setStatus(ImportRecordStatus::SUCCESS);
            $manager->persist($record);
        }

        // 6. 更多的失败记录（模拟各种错误情况）
        $errorCases = [
            ['错误类型' => '姓名为空', 'name' => '', 'id_card' => '110101199001011235', 'phone' => '13800138003'],
            ['错误类型' => '身份证号为空', 'name' => '测试用户', 'id_card' => '', 'phone' => '13800138004'],
            ['错误类型' => '手机号为空', 'name' => '测试用户', 'id_card' => '110101199001011236', 'phone' => ''],
            ['错误类型' => '身份证号长度错误', 'name' => '测试用户', 'id_card' => '1101011990', 'phone' => '13800138005'],
            ['错误类型' => '手机号长度错误', 'name' => '测试用户', 'id_card' => '110101199001011237', 'phone' => '138001'],
        ];

        foreach ($errorCases as $index => $errorCase) {
            $record = new ImportRecord();
            $record->setBatch($failedBatch);
            $record->setRowNumber($index + 1);
            $record->setRawData($errorCase);
            $record->setErrorMessage($errorCase['错误类型']);
            $record->setValidationErrors(['general' => [$errorCase['错误类型']]]);
            $record->setProcessingTime(rand(50, 150));
            $record->setStatus(ImportRecordStatus::FAILED);
            $manager->persist($record);
        }

        // 7. 无效的记录（已标记为无效）
        $invalidRecord = new ImportRecord();
        $invalidRecord->setBatch($failedBatch);
        $invalidRecord->setRowNumber(999);
        $invalidRecord->setRawData([
            'corrupted_data' => true,
        ]);
        $invalidRecord->setErrorMessage('数据损坏');
        $invalidRecord->setStatus(ImportRecordStatus::FAILED);
        $invalidRecord->setValid(false);
        $manager->persist($invalidRecord);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ImportBatchFixtures::class,
        ];
    }
}
