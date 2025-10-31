<?php

namespace Tourze\RealNameAuthenticationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;
use Tourze\RealNameAuthenticationBundle\Repository\ImportBatchRepository;

/**
 * 导入批次实体
 *
 * 记录每次批量导入的基本信息和处理状态
 */
#[ORM\Entity(repositoryClass: ImportBatchRepository::class)]
#[ORM\Table(
    name: 'import_batch',
    options: ['comment' => '导入批次表']
)]
class ImportBatch implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use IpTraceableAware;

    #[ORM\Id]
    #[ORM\CustomIdGenerator]
    #[ORM\Column(name: 'id', type: Types::STRING, length: 36, options: ['comment' => '主键ID'])]
    #[Assert\Length(max: 36)]
    private string $id;

    #[ORM\Column(name: 'original_file_name', type: Types::STRING, length: 255, nullable: false, options: ['comment' => '原始文件名'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $originalFileName;

    #[ORM\Column(name: 'file_type', type: Types::STRING, length: 100, nullable: false, options: ['comment' => '文件类型'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $fileType;

    #[ORM\Column(name: 'file_size', type: Types::INTEGER, nullable: false, options: ['comment' => '文件大小(字节)'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'int')]
    #[Assert\GreaterThan(value: 0)]
    private int $fileSize;

    #[ORM\Column(name: 'file_md5', type: Types::STRING, length: 32, nullable: false, options: ['comment' => '文件MD5值'])]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 32)]
    #[Assert\Regex(pattern: '/^[a-f0-9]{32}$/', message: '必须是有效的MD5值')]
    private string $fileMd5;

    #[ORM\Column(name: 'status', type: Types::STRING, enumType: ImportStatus::class, nullable: false, options: ['comment' => '导入状态'])]
    #[IndexColumn]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [ImportStatus::class, 'cases'])]
    private ImportStatus $status;

    #[ORM\Column(name: 'total_records', type: Types::INTEGER, nullable: false, options: ['default' => 0, 'comment' => '总记录数'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $totalRecords = 0;

    #[ORM\Column(name: 'processed_records', type: Types::INTEGER, nullable: false, options: ['default' => 0, 'comment' => '已处理记录数'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'int')]
    #[Assert\GreaterThanOrEqual(value: 0)]
    private int $processedRecords = 0;

    #[ORM\Column(name: 'success_records', type: Types::INTEGER, nullable: false, options: ['default' => 0, 'comment' => '成功记录数'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'int')]
    #[Assert\GreaterThanOrEqual(value: 0)]
    private int $successRecords = 0;

    #[ORM\Column(name: 'failed_records', type: Types::INTEGER, nullable: false, options: ['default' => 0, 'comment' => '失败记录数'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'int')]
    #[Assert\GreaterThanOrEqual(value: 0)]
    private int $failedRecords = 0;

    #[ORM\Column(name: 'start_time', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '开始处理时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(name: 'finish_time', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '完成时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $finishTime = null;

    #[ORM\Column(name: 'processing_duration', type: Types::INTEGER, nullable: true, options: ['comment' => '处理时长(秒)'])]
    #[Assert\Type(type: 'int')]
    #[Assert\GreaterThanOrEqual(value: 0)]
    private ?int $processingDuration = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'import_config', type: Types::JSON, nullable: true, options: ['comment' => '导入配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $importConfig = null;

    #[ORM\Column(name: 'error_message', type: Types::TEXT, nullable: true, options: ['comment' => '错误信息'])]
    #[Assert\Length(max: 65535)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'remark', type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $remark = null;

    #[ORM\Column(name: 'valid', type: Types::BOOLEAN, options: ['comment' => '是否有效', 'default' => true])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    private bool $valid = true;

    /**
     * @var Collection<int, ImportRecord>
     */
    #[ORM\OneToMany(mappedBy: 'batch', targetEntity: ImportRecord::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    private Collection $records;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->status = ImportStatus::PENDING;
        $this->records = new ArrayCollection();
        // Note: createTime and updateTime are handled by TimestampableAware trait
    }

    public function __toString(): string
    {
        return sprintf('批次 %s (%s)',
            $this->originalFileName,
            $this->status->getLabel()
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOriginalFileName(): string
    {
        return $this->originalFileName;
    }

    public function setOriginalFileName(string $originalFileName): void
    {
        $this->originalFileName = $originalFileName;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getFileMd5(): string
    {
        return $this->fileMd5;
    }

    public function setFileMd5(string $fileMd5): void
    {
        $this->fileMd5 = $fileMd5;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getStatus(): ImportStatus
    {
        return $this->status;
    }

    public function setStatus(ImportStatus $status): void
    {
        $this->status = $status;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getTotalRecords(): int
    {
        return $this->totalRecords;
    }

    public function setTotalRecords(int $totalRecords): void
    {
        $this->totalRecords = $totalRecords;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getProcessedRecords(): int
    {
        return $this->processedRecords;
    }

    public function setProcessedRecords(int $processedRecords): void
    {
        $this->processedRecords = $processedRecords;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getSuccessRecords(): int
    {
        return $this->successRecords;
    }

    public function setSuccessRecords(int $successRecords): void
    {
        $this->successRecords = $successRecords;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getFailedRecords(): int
    {
        return $this->failedRecords;
    }

    public function setFailedRecords(int $failedRecords): void
    {
        $this->failedRecords = $failedRecords;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeImmutable $startTime): void
    {
        $this->startTime = $startTime;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getFinishTime(): ?\DateTimeImmutable
    {
        return $this->finishTime;
    }

    public function setFinishTime(?\DateTimeImmutable $finishTime): void
    {
        $this->finishTime = $finishTime;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getProcessingDuration(): ?int
    {
        return $this->processingDuration;
    }

    public function setProcessingDuration(?int $processingDuration): void
    {
        $this->processingDuration = $processingDuration;
        $this->updateTime = new \DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getImportConfig(): ?array
    {
        return $this->importConfig;
    }

    /**
     * @param array<string, mixed>|null $importConfig
     */
    public function setImportConfig(?array $importConfig): void
    {
        $this->importConfig = $importConfig;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
        // Note: updateTime is handled by TimestampableAware trait automatically
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
        $this->updateTime = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, ImportRecord>
     */
    public function getRecords(): Collection
    {
        return $this->records;
    }

    /**
     * 计算处理进度百分比
     */
    public function getProgressPercentage(): float
    {
        if (0 === $this->totalRecords) {
            return 0.0;
        }

        return round(($this->processedRecords / $this->totalRecords) * 100, 2);
    }

    /**
     * 更新统计数据
     */
    public function updateStatistics(): void
    {
        $this->processedRecords = $this->successRecords + $this->failedRecords;

        if (null !== $this->startTime && null !== $this->finishTime) {
            $this->processingDuration = $this->finishTime->getTimestamp() - $this->startTime->getTimestamp();
        }

        $this->updateTime = new \DateTimeImmutable();
    }

    /**
     * 开始处理
     */
    public function startProcessing(): void
    {
        $this->status = ImportStatus::PROCESSING;
        $this->startTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
    }

    /**
     * 完成处理
     */
    public function finishProcessing(): void
    {
        $this->status = ImportStatus::COMPLETED;
        $this->finishTime = new \DateTimeImmutable();
        $this->updateStatistics();
    }

    /**
     * 标记为失败
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->status = ImportStatus::FAILED;
        $this->errorMessage = $errorMessage;
        $this->finishTime = new \DateTimeImmutable();
        $this->updateStatistics();
    }

    /**
     * 判断是否已完成
     */
    public function isCompleted(): bool
    {
        return ImportStatus::COMPLETED === $this->status;
    }

    /**
     * 判断是否失败
     */
    public function isFailed(): bool
    {
        return ImportStatus::FAILED === $this->status;
    }

    /**
     * 判断是否正在处理
     */
    public function isProcessing(): bool
    {
        return ImportStatus::PROCESSING === $this->status;
    }

    /**
     * 获取成功率
     */
    public function getSuccessRate(): float
    {
        if (0 === $this->processedRecords) {
            return 0.0;
        }

        return round(($this->successRecords / $this->processedRecords) * 100, 2);
    }
}
