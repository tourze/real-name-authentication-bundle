<?php

namespace Tourze\RealNameAuthenticationBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Uid\Uuid;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;
use Tourze\RealNameAuthenticationBundle\Repository\ImportRecordRepository;

/**
 * 导入记录实体
 * 
 * 记录每条导入记录的处理结果和详细信息
 */
#[ORM\Entity(repositoryClass: ImportRecordRepository::class)]
#[ORM\Table(
    name: 'import_record',
    options: ['comment' => '导入记录表']
)]
#[ORM\Index(name: 'import_record_idx_batch_id', columns: ['batch_id'])]
#[ORM\Index(name: 'import_record_idx_status', columns: ['status'])]
#[ORM\Index(name: 'import_record_idx_row_number', columns: ['row_number'])]
class ImportRecord implements Stringable
{
    use TimestampableAware;
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, options: ['comment' => '主键ID'])]
    private string $id;

    #[ORM\ManyToOne(targetEntity: ImportBatch::class, inversedBy: 'records')]
    #[ORM\JoinColumn(name: 'batch_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ImportBatch $batch;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['comment' => '行号'])]
    private int $rowNumber;

    #[ORM\Column(type: Types::STRING, enumType: ImportRecordStatus::class, nullable: false, options: ['comment' => '处理状态'])]
    private ImportRecordStatus $status;

    #[ORM\Column(type: Types::JSON, nullable: false, options: ['comment' => '原始数据'])]
    private array $rawData;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '处理后数据'])]
    private ?array $processedData = null;

    #[ORM\ManyToOne(targetEntity: RealNameAuthentication::class)]
    #[ORM\JoinColumn(name: 'authentication_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?RealNameAuthentication $authentication = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '错误信息'])]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '验证错误详情'])]
    private ?array $validationErrors = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '处理备注'])]
    private ?string $remark = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '处理时长(毫秒)'])]
    private ?int $processingTime = null;


    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否有效', 'default' => true])]
    private bool $valid = true;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->status = ImportRecordStatus::PENDING;
        // Note: createTime and updateTime are handled by TimestampableAware trait
    }

    public function __toString(): string
    {
        return sprintf('行 %d (%s)', 
            $this->rowNumber, 
            $this->status->getLabel()
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getBatch(): ImportBatch
    {
        return $this->batch;
    }

    public function setBatch(ImportBatch $batch): void
    {
        $this->batch = $batch;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getRowNumber(): int
    {
        return $this->rowNumber;
    }

    public function setRowNumber(int $rowNumber): void
    {
        $this->rowNumber = $rowNumber;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getStatus(): ImportRecordStatus
    {
        return $this->status;
    }

    public function setStatus(ImportRecordStatus $status): void
    {
        $this->status = $status;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function setRawData(array $rawData): void
    {
        $this->rawData = $rawData;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getProcessedData(): ?array
    {
        return $this->processedData;
    }

    public function setProcessedData(?array $processedData): void
    {
        $this->processedData = $processedData;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getAuthentication(): ?RealNameAuthentication
    {
        return $this->authentication;
    }

    public function setAuthentication(?RealNameAuthentication $authentication): void
    {
        $this->authentication = $authentication;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getValidationErrors(): ?array
    {
        return $this->validationErrors;
    }

    public function setValidationErrors(?array $validationErrors): void
    {
        $this->validationErrors = $validationErrors;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getProcessingTime(): ?int
    {
        return $this->processingTime;
    }

    public function setProcessingTime(?int $processingTime): void
    {
        $this->processingTime = $processingTime;
        $this->updateTime = new DateTimeImmutable();
    }public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
        $this->updateTime = new DateTimeImmutable();
    }

    /**
     * 标记为处理成功
     */
    public function markAsSuccess(RealNameAuthentication $authentication, ?array $processedData = null): void
    {
        $this->status = ImportRecordStatus::SUCCESS;
        $this->authentication = $authentication;
        $this->processedData = $processedData;
        $this->errorMessage = null;
        $this->validationErrors = null;
        $this->updateTime = new DateTimeImmutable();
    }

    /**
     * 标记为处理失败
     */
    public function markAsFailed(string $errorMessage, ?array $validationErrors = null): void
    {
        $this->status = ImportRecordStatus::FAILED;
        $this->errorMessage = $errorMessage;
        $this->validationErrors = $validationErrors;
        $this->authentication = null;
        $this->updateTime = new DateTimeImmutable();
    }

    /**
     * 标记为跳过
     */
    public function markAsSkipped(string $reason): void
    {
        $this->status = ImportRecordStatus::SKIPPED;
        $this->remark = $reason;
        $this->updateTime = new DateTimeImmutable();
    }

    /**
     * 获取处理结果摘要
     */
    public function getResultSummary(): string
    {
        return match ($this->status) {
            ImportRecordStatus::SUCCESS => $this->authentication 
                ? sprintf('成功创建认证记录: %s', $this->authentication->getId())
                : '处理成功',
            ImportRecordStatus::FAILED => $this->errorMessage ?? '处理失败',
            ImportRecordStatus::SKIPPED => $this->remark ?? '已跳过',
            ImportRecordStatus::PENDING => '等待处理',
        };
    }

    /**
     * 判断是否成功
     */
    public function isSuccess(): bool
    {
        return $this->status === ImportRecordStatus::SUCCESS;
    }

    /**
     * 判断是否失败
     */
    public function isFailed(): bool
    {
        return $this->status === ImportRecordStatus::FAILED;
    }

    /**
     * 判断是否跳过
     */
    public function isSkipped(): bool
    {
        return $this->status === ImportRecordStatus::SKIPPED;
    }

    /**
     * 获取原始数据中的指定字段值
     */
    public function getRawValue(string $field): mixed
    {
        return $this->rawData[$field] ?? null;
    }

    /**
     * 获取处理后数据中的指定字段值
     */
    public function getProcessedValue(string $field): mixed
    {
        return $this->processedData[$field] ?? null;
    }} 