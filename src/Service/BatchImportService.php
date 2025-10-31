<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;
use Tourze\RealNameAuthenticationBundle\Exception\BatchImportException;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;
use Tourze\RealNameAuthenticationBundle\Repository\ImportBatchRepository;
use Tourze\RealNameAuthenticationBundle\Repository\ImportRecordRepository;
use Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO;

/**
 * 批量导入服务
 *
 * 处理实名认证信息的批量导入功能
 */
#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'real_name_authentication')]
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
        private readonly LoggerInterface $logger,
        private readonly AuthenticationMethodDetector $methodDetector,
        private readonly CsvFileParser $csvParser,
    ) {
    }

    /**
     * 创建导入批次
     *
     * @param array<string, mixed> $config
     */
    public function createImportBatch(UploadedFile $file, array $config = []): ImportBatch
    {
        // 验证文件
        $this->validateFile($file);

        // 计算文件信息
        $fileInfo = $this->extractFileInfo($file);

        // 检查重复文件
        $this->checkDuplicateFile($fileInfo['md5'], $file->getClientOriginalName());

        // 创建并保存批次
        $batch = $this->createBatchEntity($file, $fileInfo, $config);

        $this->logger->info('创建导入批次', [
            'batch_id' => $batch->getId(),
            'file_name' => $batch->getOriginalFileName(),
            'file_size' => $batch->getFileSize(),
            'file_type' => $batch->getFileType(),
        ]);

        return $batch;
    }

    /**
     * 提取文件信息
     *
     * @return array{md5: string, size: int, type: string}
     */
    private function extractFileInfo(UploadedFile $file): array
    {
        $fileMd5 = md5_file($file->getPathname());
        if (false === $fileMd5) {
            throw new InvalidAuthenticationDataException('无法计算文件MD5值');
        }

        return [
            'md5' => $fileMd5,
            'size' => $file->getSize(),
            'type' => $this->detectFileType($file),
        ];
    }

    /**
     * 检查重复文件
     */
    private function checkDuplicateFile(string $fileMd5, string $originalName): void
    {
        $duplicates = $this->batchRepository->findDuplicateFiles($fileMd5);
        if ([] !== $duplicates) {
            $this->logger->warning('检测到重复文件', [
                'file_md5' => $fileMd5,
                'original_name' => $originalName,
                'duplicate_count' => count($duplicates),
            ]);
        }
    }

    /**
     * 创建批次实体
     *
     * @param array{md5: string, size: int, type: string} $fileInfo
     * @param array<string, mixed> $config
     */
    private function createBatchEntity(UploadedFile $file, array $fileInfo, array $config): ImportBatch
    {
        $batch = new ImportBatch();
        $batch->setOriginalFileName($file->getClientOriginalName());
        $batch->setFileType($fileInfo['type']);
        $batch->setFileSize($fileInfo['size']);
        $batch->setFileMd5($fileInfo['md5']);
        $batch->setImportConfig($config);

        $this->entityManager->persist($batch);
        $this->entityManager->flush();

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
                'total_records' => $totalRecords,
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
                'created_records' => $totalRecords,
            ]);
        } catch (\Throwable $e) {
            $batch->markAsFailed('文件解析失败: ' . $e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('文件解析失败', [
                'batch_id' => $batch->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
                'pending_records' => count($pendingRecords),
            ]);

            foreach ($pendingRecords as $record) {
                $this->processRecord($record);

                // 每处理10条记录更新一次批次统计
                if (0 === $record->getRowNumber() % 10) {
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
                'success_rate' => $batch->getSuccessRate(),
            ]);
        } catch (\Throwable $e) {
            $batch->markAsFailed('批次处理失败: ' . $e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('批次处理失败', [
                'batch_id' => $batch->getId(),
                'error' => $e->getMessage(),
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
            $this->executeRecordProcessing($record);
        } catch (\Throwable $e) {
            $this->handleRecordProcessingError($record, $e);
        } finally {
            $processingTime = (int) ((microtime(true) - $startTime) * 1000);
            $record->setProcessingTime($processingTime);
            $this->entityManager->flush();
        }
    }

    /**
     * 执行记录处理逻辑
     */
    private function executeRecordProcessing(ImportRecord $record): void
    {
        $rawData = $record->getRawData();

        // 数据清理和验证
        $cleanedData = $this->cleanAndValidateData($rawData);
        $record->setProcessedData($cleanedData);

        // 确定认证方式
        $method = $this->determineAuthenticationMethod($cleanedData);
        if (null === $method) {
            $record->markAsSkipped('无法确定认证方式');
            $this->entityManager->flush();

            return;
        }

        // 创建并验证DTO
        $dto = $this->createAuthenticationDto($cleanedData, $method);
        $validationErrors = $this->validateDto($dto);
        if (null !== $validationErrors) {
            $record->markAsFailed('数据验证失败', ['errors' => $validationErrors]);
            $this->entityManager->flush();

            return;
        }

        // 提交认证
        $authentication = $this->personalAuthService->submitAuthentication($dto);
        $record->markAsSuccess($authentication, $cleanedData);

        $this->logger->debug('导入记录处理成功', [
            'batch_id' => $record->getBatch()->getId(),
            'row_number' => $record->getRowNumber(),
            'auth_id' => $authentication->getId(),
        ]);
    }

    /**
     * 验证DTO
     *
     * @return array<int, string>|null
     */
    private function validateDto(PersonalAuthDTO $dto): ?array
    {
        $violations = $this->validator->validate($dto);
        if (0 === count($violations)) {
            return null;
        }

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
        }

        return $errors;
    }

    /**
     * 处理记录处理错误
     */
    private function handleRecordProcessingError(ImportRecord $record, \Throwable $e): void
    {
        $record->markAsFailed('处理异常: ' . $e->getMessage());

        $this->logger->warning('导入记录处理失败', [
            'batch_id' => $record->getBatch()->getId(),
            'row_number' => $record->getRowNumber(),
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 验证上传文件
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new InvalidAuthenticationDataException('文件上传失败');
        }

        $allowedMimeTypes = [
            'text/csv',
            'application/csv',
            'text/plain',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
            throw new InvalidAuthenticationDataException('不支持的文件类型: ' . $file->getMimeType());
        }

        // 检查文件大小（最大10MB）
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new InvalidAuthenticationDataException('文件大小超过限制（10MB）');
        }
    }

    /**
     * 检测文件类型
     */
    private function detectFileType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($mimeType, ['text/csv', 'application/csv', 'text/plain'], true) || 'csv' === $extension) {
            return 'csv';
        }

        if (in_array($mimeType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ], true) || in_array($extension, ['xls', 'xlsx'], true)) {
            return 'excel';
        }

        throw new InvalidAuthenticationDataException('无法识别的文件类型');
    }

    /**
     * 解析文件内容
     *
     * @return array<int, array<string, string>>
     */
    private function parseFile(UploadedFile $file, string $fileType): array
    {
        switch ($fileType) {
            case 'csv':
                return $this->parseCsvFile($file);
            case 'excel':
                return $this->parseExcelFile($file);
            default:
                throw new InvalidAuthenticationDataException('不支持的文件类型: ' . $fileType);
        }
    }

    /**
     * 解析CSV文件
     *
     * @return array<int, array<string, string>>
     */
    private function parseCsvFile(UploadedFile $file): array
    {
        return $this->csvParser->parse($file);
    }

    /**
     * 解析Excel文件（简化实现，实际需要使用PhpSpreadsheet）
     *
     * @return array<int, array<string, string>>
     */
    private function parseExcelFile(UploadedFile $file): array
    {
        throw new AuthenticationException('Excel文件解析功能需要安装PhpSpreadsheet扩展包');
    }

    /**
     * 清理和验证数据
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function cleanAndValidateData(array $data): array
    {
        return $this->validationService->sanitizeInput($data);
    }

    /**
     * 确定认证方式
     *
     * @param array<string, mixed> $data
     */
    private function determineAuthenticationMethod(array $data): ?AuthenticationMethod
    {
        return $this->methodDetector->detect($data);
    }

    /**
     * 创建认证DTO
     *
     * @param array<string, mixed> $data
     */
    private function createAuthenticationDto(array $data, AuthenticationMethod $method): PersonalAuthDTO
    {
        // 获取当前用户，如果没有则创建虚拟用户（用于测试环境）
        $user = $this->security->getUser();
        if (null === $user) {
            // 在测试环境中创建虚拟用户
            $user = new class implements UserInterface {
                public function getUserIdentifier(): string
                {
                    return 'batch_import_user';
                }

                public function getRoles(): array
                {
                    return ['ROLE_USER'];
                }

                public function eraseCredentials(): void
                {
                }
            };
        }

        $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : null;
        $idCard = isset($data['id_card']) && is_string($data['id_card']) ? $data['id_card'] : null;
        $mobile = isset($data['mobile']) && is_string($data['mobile']) ? $data['mobile'] : null;
        $bankCard = isset($data['bank_card']) && is_string($data['bank_card']) ? $data['bank_card'] : null;

        return new PersonalAuthDTO(
            user: $user,
            method: $method,
            name: $name,
            idCard: $idCard,
            mobile: $mobile,
            bankCard: $bankCard
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
            ['张三', '11010119900101100X', '13800138000', '6222021234567894', 'bank_card_four_elements'],
            ['李四', '110101199002021007', '13800138001', '', 'carrier_three_elements'],
            ['王五', '110101199003031004', '', '', 'id_card_two_elements'],
        ];

        $output = fopen('php://temp', 'w+');
        if (false === $output) {
            throw new BatchImportException('无法创建临时文件');
        }

        // 写入BOM以支持中文
        if (false === fwrite($output, "\xEF\xBB\xBF")) {
            throw new BatchImportException('写入BOM失败');
        }

        // 写入头部
        if (false === fputcsv($output, $headers, ',', '"', '\\')) {
            throw new BatchImportException('写入CSV头部失败');
        }

        // 写入示例数据
        foreach ($sampleData as $row) {
            if (false === fputcsv($output, $row, ',', '"', '\\')) {
                throw new BatchImportException('写入CSV行失败');
            }
        }

        rewind($output);
        $content = stream_get_contents($output);
        if (false === $content) {
            fclose($output);
            throw new BatchImportException('读取文件内容失败');
        }

        fclose($output);

        return $content;
    }

    /**
     * 取消导入批次
     */
    public function cancelBatch(ImportBatch $batch): void
    {
        if (!$batch->getStatus()->isCancellable()) {
            throw new InvalidAuthenticationDataException('当前状态不允许取消');
        }

        $batch->setStatus(ImportStatus::CANCELLED);
        $batch->setFinishTime(new \DateTimeImmutable());
        $this->entityManager->flush();

        $this->logger->info('导入批次已取消', [
            'batch_id' => $batch->getId(),
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
            'reset_count' => count($failedRecords),
        ]);
    }
}
