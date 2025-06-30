<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;
use Tourze\RealNameAuthenticationBundle\Repository\ImportRecordRepository;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;
use Tourze\RealNameAuthenticationBundle\Service\BatchImportService;
use Tourze\RealNameAuthenticationBundle\Tests\Fixtures\TestUser;
use Tourze\RealNameAuthenticationBundle\Tests\Integration\IntegrationTestCase;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Service\BatchImportService
 */
class BatchImportServiceTest extends IntegrationTestCase
{
    private BatchImportService $service;
    private EntityManagerInterface $entityManager;
    private AuthenticationProvider $provider;
    private ImportRecordRepository $importRecordRepository;
    private RealNameAuthenticationRepository $realNameAuthenticationRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = $this->getService(BatchImportService::class);
        $this->entityManager = $this->getService('doctrine.orm.entity_manager');
        $this->importRecordRepository = $this->getService(ImportRecordRepository::class);
        $this->realNameAuthenticationRepository = $this->getService(RealNameAuthenticationRepository::class);
        
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // 创建认证提供商
        $this->provider = new AuthenticationProvider();
        $this->provider->setName('批量导入提供商');
        $this->provider->setCode('batch_import_provider');
        $this->provider->setType(ProviderType::GOVERNMENT);
        $this->provider->setApiEndpoint('https://api.batch.com');
        $this->provider->setActive(true);
        $this->provider->setConfig(['api_key' => 'batch_key']);
        $this->entityManager->persist($this->provider);
        
        // 创建一些测试用户
        for ($i = 1; $i <= 10; $i++) {
            $user = new TestUser("import_user_{$i}");
            $this->entityManager->persist($user);
        }
        
        $this->entityManager->flush();
    }

    public function testCreateImportBatch(): void
    {
        // 创建临时CSV文件
        $csvContent = "username,real_name,id_card\n";
        $csvContent .= "import_user_1,张三,110101199001011001\n";
        $csvContent .= "import_user_2,李四,110101199001011002\n";
        $csvContent .= "import_user_3,王五,110101199001011003\n";
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'import_test');
        file_put_contents($tmpFile, $csvContent);
        
        $uploadedFile = new UploadedFile(
            $tmpFile,
            'test_import.csv',
            'text/csv',
            null,
            true
        );
        
        try {
            // 创建批次
            $batch = $this->service->createImportBatch($uploadedFile, [
                'provider_id' => $this->provider->getId(),
                'auto_approve' => true,
            ]);
            
            // 验证批次信息
            $this->assertInstanceOf(ImportBatch::class, $batch);
            $this->assertEquals('test_import.csv', $batch->getOriginalFileName());
            $this->assertEquals('csv', $batch->getFileType());
            $this->assertGreaterThan(0, $batch->getFileSize());
            $this->assertNotEmpty($batch->getFileMd5());
            $this->assertEquals(ImportStatus::PENDING, $batch->getStatus());
            $this->assertArrayHasKey('provider_id', $batch->getImportConfig());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testParseFileAndCreateRecords(): void
    {
        // 创建临时CSV文件
        $csvContent = "username,real_name,id_card\n";
        $csvContent .= "import_user_4,赵六,110101199001011004\n";
        $csvContent .= "import_user_5,钱七,110101199001011005\n";
        $csvContent .= "import_user_6,孙八,110101199001011006\n";
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'parse_test');
        file_put_contents($tmpFile, $csvContent);
        
        $uploadedFile = new UploadedFile(
            $tmpFile,
            'test_parse.csv',
            'text/csv',
            null,
            true
        );
        
        try {
            // 创建批次
            $batch = $this->service->createImportBatch($uploadedFile);
            
            // 解析文件并创建记录
            $this->service->parseFileAndCreateRecords($batch, $uploadedFile);
            
            // 验证批次状态
            $this->entityManager->refresh($batch);
            $this->assertEquals(ImportStatus::PROCESSING, $batch->getStatus());
            $this->assertEquals(3, $batch->getTotalRecords());
            
            // 验证导入记录
            $records = $this->importRecordRepository
                ->findBy(['batch' => $batch]);
            $this->assertCount(3, $records);
            
            foreach ($records as $record) {
                $this->assertEquals(ImportRecordStatus::PENDING, $record->getStatus());
                $this->assertNotEmpty($record->getRawValue('username'));
                $this->assertNotEmpty($record->getRawValue('realname'));
                $this->assertNotEmpty($record->getRawValue('idcard'));
            }
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testProcessBatch(): void
    {
        // 创建临时CSV文件
        $csvContent = "username,real_name,id_card\n";
        $csvContent .= "import_user_7,周九,110101199001011007\n";
        $csvContent .= "import_user_8,吴十,110101199001011008\n";
        $csvContent .= "invalid_user,无效用户,110101199001011009\n";
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'process_test');
        file_put_contents($tmpFile, $csvContent);
        
        $uploadedFile = new UploadedFile(
            $tmpFile,
            'test_process.csv',
            'text/csv',
            null,
            true
        );
        
        try {
            // 创建批次
            $batch = $this->service->createImportBatch($uploadedFile, [
                'provider_id' => $this->provider->getId(),
            ]);
            
            // 解析文件
            $this->service->parseFileAndCreateRecords($batch, $uploadedFile);
            
            // 处理批次
            $this->service->processBatch($batch);
            
            // 验证批次状态
            $this->entityManager->refresh($batch);
            $this->assertEquals(ImportStatus::COMPLETED, $batch->getStatus());
            $this->assertEquals(3, $batch->getTotalRecords());
            $this->assertEquals(2, $batch->getSuccessRecords());
            $this->assertEquals(1, $batch->getFailedRecords());
            $this->assertNotNull($batch->getFinishTime());
            
            // 验证成功的记录
            $successRecords = $this->importRecordRepository
                ->findBy(['batch' => $batch, 'status' => ImportRecordStatus::SUCCESS]);
            $this->assertCount(2, $successRecords);
            
            // 验证失败的记录
            $failedRecords = $this->importRecordRepository
                ->findBy(['batch' => $batch, 'status' => ImportRecordStatus::FAILED]);
            $this->assertCount(1, $failedRecords);
            $this->assertEquals('invalid_user', $failedRecords[0]->getUsername());
            $this->assertNotEmpty($failedRecords[0]->getErrorMessage());
            
            // 验证认证记录创建
            $auth1 = $this->realNameAuthenticationRepository
                ->findOneBy(['submittedData' => ['real_name' => '周九']]);
            $this->assertNotNull($auth1);
            
            $auth2 = $this->realNameAuthenticationRepository
                ->findOneBy(['submittedData' => ['real_name' => '吴十']]);
            $this->assertNotNull($auth2);
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testGenerateTemplate(): void
    {
        // 生成模板
        $template = $this->service->generateTemplate();
        
        // 验证模板内容
        $this->assertStringContainsString('username', $template);
        $this->assertStringContainsString('real_name', $template);
        $this->assertStringContainsString('id_card', $template);
        
        // 验证是否是有效的CSV格式
        $lines = explode("\n", trim($template));
        $this->assertGreaterThanOrEqual(2, count($lines)); // 至少有标题行和一个示例行
        
        // 验证标题行
        $headers = str_getcsv($lines[0]);
        $this->assertContains('username', $headers);
        $this->assertContains('real_name', $headers);
        $this->assertContains('id_card', $headers);
    }

    public function testCancelBatch(): void
    {
        // 创建批次
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test_cancel.csv');
        $batch->setFileType('csv');
        $batch->setFileSize(1000);
        $batch->setFileMd5(md5('test'));
        $batch->setStatus(ImportStatus::PROCESSING);
        $batch->setTotalRecords(10);
        $this->entityManager->persist($batch);
        $this->entityManager->flush();
        
        // 取消批次
        $this->service->cancelBatch($batch);
        
        // 验证状态
        $this->entityManager->refresh($batch);
        $this->assertEquals(ImportStatus::CANCELLED, $batch->getStatus());
        $this->assertNotNull($batch->getFinishTime());
    }

    public function testRetryFailedRecords(): void
    {
        // 创建批次和失败记录
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test_retry.csv');
        $batch->setFileType('csv');
        $batch->setFileSize(1000);
        $batch->setFileMd5(md5('retry'));
        $batch->setStatus(ImportStatus::COMPLETED);
        $batch->setTotalRecords(3);
        $batch->setSuccessRecords(1);
        $batch->setFailedRecords(2);
        $this->entityManager->persist($batch);
        
        // 创建失败的记录
        for ($i = 1; $i <= 2; $i++) {
            $record = new ImportRecord();
            $record->setBatch($batch);
            $record->setRowNumber($i);
            $record->setRawData([
                'username' => "retry_user_{$i}",
                'real_name' => "重试用户{$i}",
                'id_card_number' => "11010119900101200{$i}",
            ]);
            $record->setStatus(ImportRecordStatus::FAILED);
            $record->setErrorMessage('用户不存在');
            $this->entityManager->persist($record);
        }
        
        $this->entityManager->flush();
        
        // 创建之前失败的用户
        $user1 = new TestUser('retry_user_1');
        $user2 = new TestUser('retry_user_2');
        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->flush();
        
        // 重试失败的记录
        $this->service->retryFailedRecords($batch);
        
        // 验证批次状态更新
        $this->entityManager->refresh($batch);
        $this->assertEquals(ImportStatus::COMPLETED, $batch->getStatus());
        
        // 验证记录状态更新
        $records = $this->importRecordRepository
            ->findBy(['batch' => $batch]);
        
        foreach ($records as $record) {
            $this->assertEquals(ImportRecordStatus::SUCCESS, $record->getStatus());
            $this->assertNull($record->getErrorMessage());
        }
    }

    public function testDuplicateFileDetection(): void
    {
        // 创建临时文件
        $content = "username,real_name,id_card\ntest1,测试,110101199001011111\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'duplicate_test');
        file_put_contents($tmpFile, $content);
        
        $uploadedFile1 = new UploadedFile(
            $tmpFile,
            'test1.csv',
            'text/csv',
            null,
            true
        );
        
        try {
            // 第一次上传
            $batch1 = $this->service->createImportBatch($uploadedFile1);
            $this->assertNotNull($batch1);
            
            // 创建相同内容的第二个文件
            $tmpFile2 = tempnam(sys_get_temp_dir(), 'duplicate_test2');
            file_put_contents($tmpFile2, $content);
            
            $uploadedFile2 = new UploadedFile(
                $tmpFile2,
                'test2.csv',
                'text/csv',
                null,
                true
            );
            
            // 第二次上传相同内容的文件
            $batch2 = $this->service->createImportBatch($uploadedFile2);
            $this->assertNotNull($batch2);
            
            // 两个批次应该有相同的MD5
            $this->assertEquals($batch1->getFileMd5(), $batch2->getFileMd5());
            
            @unlink($tmpFile2);
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testInvalidFileType(): void
    {
        // 创建非CSV文件
        $tmpFile = tempnam(sys_get_temp_dir(), 'invalid_test');
        file_put_contents($tmpFile, '<?php echo "test"; ?>');
        
        $uploadedFile = new UploadedFile(
            $tmpFile,
            'test.php',
            'application/x-php',
            null,
            true
        );
        
        try {
            // 预期抛出异常
            $this->expectException(\Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException::class);
            
            // 尝试创建批次
            $this->service->createImportBatch($uploadedFile);
        } finally {
            @unlink($tmpFile);
        }
    }
}