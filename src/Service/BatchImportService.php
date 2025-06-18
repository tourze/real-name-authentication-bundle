<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;
use Tourze\RealNameAuthenticationBundle\Repository\ImportBatchRepository;
use Tourze\RealNameAuthenticationBundle\Repository\ImportRecordRepository;
use Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO;

/**
 * 批量导入服务
 * 
 * 处理实名认证信息的批量导入功能
 */
class BatchImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ImportBatchRepository $batchRepository,
        private readonly ImportRecordRepository $recordRepository,
        private readonly PersonalAuthenticationService $personalAuthService,
        private readonly AuthenticationValidationService $validationService,
        private readonly ValidatorInterface $validator,
        private readonly Security $security,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * 创建导入批次
     */
    public function createImportBatch(UploadedFile $file, array $config = []): ImportBatch
    {
        // 验证文件
        $this->validateFile($file);

        // 计算文件信息
        $fileMd5 = md5_file($file->getPathname());
        $fileSize = $file->getSize();
        $fileType = $this->detectFileType($file);

        // 检查重复文件
        $duplicates = $this->batchRepository->findDuplicateFiles($fileMd5);
        if (!empty($duplicates)) {
            $this->logger->warning('检测到重复文件', [
                'file_md5' => $fileMd5,
                'original_name' => $file->getClientOriginalName(),
                'duplicate_count' => count($duplicates)
            ]);
        }

        // 创建导入批次
        $batch = new ImportBatch();
        $batch->setOriginalFileName($file->getClientOriginalName());
        $batch->setFileType($fileType);
        $batch->setFileSize($fileSize);
        $batch->setFileMd5($fileMd5);
        $batch->setImportConfig($config);

        $this->entityManager->persist($batch);
        $this->entityManager->flush();

        $this->logger->info('创建导入批次', [
            'batch_id' => $batch->getId(),
            'file_name' => $batch->getOriginalFileName(),
            'file_size' => $batch->getFileSize(),
            'file_type' => $batch->getFileType()
        ]);

        return $batch;
    }

    /**
     * 解析文件并创建导入记录
     */
    public function parseFileAndCreateRecords(ImportBatch $batch, UploadedFile $file): void
    {
        try {
            $batch->setStatus(ImportStatus::PROCESSING);
            $this->entityManager->flush();

            $data = $this->parseFile($file, $batch->getFileType());
            $totalRecords = count($data);
            
            $batch->setTotalRecords($totalRecords);
            $batch->startProcessing();

            $this->logger->info('开始解析文件', [
                'batch_id' => $batch->getId(),
                'total_records' => $totalRecords
            ]);

            // 创建导入记录
            foreach ($data as $rowNumber => $rowData) {
                $record = new ImportRecord();
                $record->setBatch($batch);
                $record->setRowNumber($rowNumber + 1); // 从1开始
                $record->setRawData($rowData);

                $this->entityManager->persist($record);
            }

            $this->entityManager->flush();

            $this->logger->info('文件解析完成', [
                'batch_id' => $batch->getId(),
                'created_records' => $totalRecords
            ]);

        } catch (\Throwable $e) {
            $batch->markAsFailed('文件解析失败: ' . $e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('文件解析失败', [
                'batch_id' => $batch->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * 处理导入批次
     */
    public function processBatch(ImportBatch $batch): void
    {
        try {
            $pendingRecords = $this->recordRepository->findPendingRecords($batch);
            
            $this->logger->info('开始处理导入批次', [
                'batch_id' => $batch->getId(),
                'pending_records' => count($pendingRecords)
            ]);

            foreach ($pendingRecords as $record) {
                $this->processRecord($record);
                
                // 每处理10条记录更新一次批次统计
                if ($record->getRowNumber() % 10 === 0) {
                    $this->updateBatchStatistics($batch);
                }
            }

            // 最终更新统计
            $this->updateBatchStatistics($batch);
            $batch->finishProcessing();
            $this->entityManager->flush();

            $this->logger->info('导入批次处理完成', [
                'batch_id' => $batch->getId(),
                'success_records' => $batch->getSuccessRecords(),
                'failed_records' => $batch->getFailedRecords(),
                'success_rate' => $batch->getSuccessRate()
            ]);

        } catch (\Throwable $e) {
            $batch->markAsFailed('批次处理失败: ' . $e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('批次处理失败', [
                'batch_id' => $batch->getId(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 处理单条导入记录
     */
    private function processRecord(ImportRecord $record): void
    {
        $startTime = microtime(true);

        try {
            $rawData = $record->getRawData();
            
            // 数据清理和验证
            $cleanedData = $this->cleanAndValidateData($rawData);
            $record->setProcessedData($cleanedData);

            // 确定认证方式
            $method = $this->determineAuthenticationMethod($cleanedData);
            if (!$method) {
                $record->markAsSkipped('无法确定认证方式');
                $this->entityManager->flush();
                return;
            }

            // 创建认证DTO
            $dto = $this->createAuthenticationDto($cleanedData, $method);
            
            // 验证DTO
            $violations = $this->validator->validate($dto);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                }
                $record->markAsFailed('数据验证失败', $errors);
                $this->entityManager->flush();
                return;
            }

            // 提交认证
            $authentication = $this->personalAuthService->submitAuthentication($dto);
            $record->markAsSuccess($authentication, $cleanedData);

            $this->logger->debug('导入记录处理成功', [
                'batch_id' => $record->getBatch()->getId(),
                'row_number' => $record->getRowNumber(),
                'auth_id' => $authentication->getId()
            ]);

        } catch (\Throwable $e) {
            $record->markAsFailed('处理异常: ' . $e->getMessage());
            
            $this->logger->warning('导入记录处理失败', [
                'batch_id' => $record->getBatch()->getId(),
                'row_number' => $record->getRowNumber(),
                'error' => $e->getMessage()
            ]);
        } finally {
            $processingTime = (int)((microtime(true) - $startTime) * 1000);
            $record->setProcessingTime($processingTime);
            $this->entityManager->flush();
        }
    }

    /**
     * 验证上传文件
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('文件上传失败');
        }

        $allowedMimeTypes = [
            'text/csv',
            'application/csv',
            'text/plain',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \InvalidArgumentException('不支持的文件类型: ' . $file->getMimeType());
        }

        // 检查文件大小（最大10MB）
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException('文件大小超过限制（10MB）');
        }
    }

    /**
     * 检测文件类型
     */
    private function detectFileType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($mimeType, ['text/csv', 'application/csv', 'text/plain']) || $extension === 'csv') {
            return 'csv';
        }

        if (in_array($mimeType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]) || in_array($extension, ['xls', 'xlsx'])) {
            return 'excel';
        }

        throw new \InvalidArgumentException('无法识别的文件类型');
    }

    /**
     * 解析文件内容
     */
    private function parseFile(UploadedFile $file, string $fileType): array
    {
        switch ($fileType) {
            case 'csv':
                return $this->parseCsvFile($file);
            case 'excel':
                return $this->parseExcelFile($file);
            default:
                throw new \InvalidArgumentException('不支持的文件类型: ' . $fileType);
        }
    }

    /**
     * 解析CSV文件
     */
    private function parseCsvFile(UploadedFile $file): array
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        if (!$handle) {
            throw new \RuntimeException('无法打开CSV文件');
        }

        // 读取头部行
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new \InvalidArgumentException('CSV文件格式错误：缺少头部行');
        }

        // 标准化头部字段名
        $headers = array_map([$this, 'normalizeFieldName'], $headers);

        // 读取数据行
        $rowNumber = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue; // 跳过格式错误的行
            }
            
            $data[$rowNumber] = array_combine($headers, $row);
            $rowNumber++;
        }

        fclose($handle);
        return $data;
    }

    /**
     * 解析Excel文件（简化实现，实际需要使用PhpSpreadsheet）
     */
    private function parseExcelFile(UploadedFile $file): array
    {
        throw new \RuntimeException('Excel文件解析功能需要安装PhpSpreadsheet扩展包');
    }

    /**
     * 标准化字段名
     */
    private function normalizeFieldName(string $fieldName): string
    {
        $fieldMap = [
            '姓名' => 'name',
            '真实姓名' => 'name',
            '身份证号' => 'id_card',
            '身份证' => 'id_card',
            '手机号' => 'mobile',
            '手机号码' => 'mobile',
            '电话号码' => 'mobile',
            '银行卡号' => 'bank_card',
            '银行卡' => 'bank_card',
            '认证方式' => 'method',
            '认证类型' => 'method',
        ];

        $normalizedName = trim($fieldName);
        return $fieldMap[$normalizedName] ?? strtolower(str_replace([' ', '-', '_'], '', $normalizedName));
    }

    /**
     * 清理和验证数据
     */
    private function cleanAndValidateData(array $data): array
    {
        return $this->validationService->sanitizeInput($data);
    }

    /**
     * 确定认证方式
     */
    private function determineAuthenticationMethod(array $data): ?AuthenticationMethod
    {
        // 如果有明确指定认证方式
        if (isset($data['method'])) {
            try {
                return AuthenticationMethod::from($data['method']);
            } catch (\ValueError) {
                // 忽略无效的认证方式
            }
        }

        // 根据数据字段自动判断
        $hasName = !empty($data['name']);
        $hasIdCard = !empty($data['id_card']);
        $hasMobile = !empty($data['mobile']);
        $hasBankCard = !empty($data['bank_card']);

        if ($hasName && $hasIdCard && $hasMobile && $hasBankCard) {
            return AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS;
        }

        if ($hasName && $hasIdCard && $hasBankCard) {
            return AuthenticationMethod::BANK_CARD_THREE_ELEMENTS;
        }

        if ($hasName && $hasIdCard && $hasMobile) {
            return AuthenticationMethod::CARRIER_THREE_ELEMENTS;
        }

        if ($hasName && $hasIdCard) {
            return AuthenticationMethod::ID_CARD_TWO_ELEMENTS;
        }

        return null;
    }

    /**
     * 创建认证DTO
     */
    private function createAuthenticationDto(array $data, AuthenticationMethod $method): PersonalAuthDTO
    {
        // 创建模拟用户（实际应该根据业务逻辑关联真实用户）
        $user = $this->security->getUser();
        if (!$user) {
            throw new \RuntimeException('无法获取当前用户信息');
        }

        return new PersonalAuthDTO(
            user: $user,
            method: $method,
            name: $data['name'] ?? null,
            idCard: $data['id_card'] ?? null,
            mobile: $data['mobile'] ?? null,
            bankCard: $data['bank_card'] ?? null
        );
    }

    /**
     * 更新批次统计信息
     */
    private function updateBatchStatistics(ImportBatch $batch): void
    {
        $progress = $this->recordRepository->getBatchProgress($batch);
        
        $batch->setSuccessRecords($progress['success']);
        $batch->setFailedRecords($progress['failed']);
        $batch->updateStatistics();
        
        $this->entityManager->flush();
    }

    /**
     * 获取导入模板（CSV格式）
     */
    public function generateTemplate(): string
    {
        $headers = ['姓名', '身份证号', '手机号', '银行卡号', '认证方式'];
        $sampleData = [
            ['张三', '110101199001011234', '13800138000', '6225123456789012', 'bank_card_four_elements'],
            ['李四', '110101199002021234', '13800138001', '', 'carrier_three_elements'],
            ['王五', '110101199003031234', '', '', 'id_card_two_elements'],
        ];

        $output = fopen('php://temp', 'w+');
        
        // 写入BOM以支持中文
        fwrite($output, "\xEF\xBB\xBF");
        
        // 写入头部
        fputcsv($output, $headers);
        
        // 写入示例数据
        foreach ($sampleData as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * 取消导入批次
     */
    public function cancelBatch(ImportBatch $batch): void
    {
        if (!$batch->getStatus()->isCancellable()) {
            throw new \InvalidArgumentException('当前状态不允许取消');
        }

        $batch->setStatus(ImportStatus::CANCELLED);
        $batch->setFinishTime(new DateTimeImmutable());
        $this->entityManager->flush();

        $this->logger->info('导入批次已取消', [
            'batch_id' => $batch->getId()
        ]);
    }

    /**
     * 重新处理失败的记录
     */
    public function retryFailedRecords(ImportBatch $batch): void
    {
        $failedRecords = $this->recordRepository->findFailedRecords($batch);
        
        foreach ($failedRecords as $record) {
            $record->setStatus(ImportRecordStatus::PENDING);
            $record->setErrorMessage(null);
            $record->setValidationErrors(null);
        }

        $this->entityManager->flush();

        $this->logger->info('重置失败记录状态', [
            'batch_id' => $batch->getId(),
            'reset_count' => count($failedRecords)
        ]);
    }
} 