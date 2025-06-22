<?php

namespace Tourze\RealNameAuthenticationBundle\Repository;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;

/**
 * 导入批次Repository
 */
class ImportBatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportBatch::class);
    }

    /**
     * 根据状态查找导入批次
     */
    public function findByStatus(ImportStatus $status): array
    {
        return $this->createQueryBuilder('ib')
            ->where('ib.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ib.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找正在处理的批次
     */
    public function findProcessingBatches(): array
    {
        return $this->findByStatus(ImportStatus::PROCESSING);
    }

    /**
     * 查找待处理的批次
     */
    public function findPendingBatches(): array
    {
        return $this->findByStatus(ImportStatus::PENDING);
    }

    /**
     * 查找用户的导入批次
     */
    public function findByUser(string $userId, int $limit = 20): array
    {
        return $this->createQueryBuilder('ib')
            ->where('ib.createdBy = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ib.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计指定时间范围内的导入批次
     */
    public function countByDateRange(DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $result = $this->createQueryBuilder('ib')
            ->select('ib.status, COUNT(ib.id) as count')
            ->where('ib.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('ib.status')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']->value] = (int)$row['count'];
        }

        return $statistics;
    }

    /**
     * 查找重复的文件（相同MD5）
     */
    public function findDuplicateFiles(string $fileMd5): array
    {
        return $this->createQueryBuilder('ib')
            ->where('ib.fileMd5 = :fileMd5')
            ->setParameter('fileMd5', $fileMd5)
            ->orderBy('ib.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取最近的导入批次（分页）
     */
    public function findRecentBatches(int $page = 1, int $size = 20): array
    {
        $offset = ($page - 1) * $size;
        
        return $this->createQueryBuilder('ib')
            ->orderBy('ib.createTime', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计导入批次总数
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('ib')
            ->select('COUNT(ib.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 获取导入统计信息
     */
    public function getImportStatistics(): array
    {
        $result = $this->createQueryBuilder('ib')
            ->select('
                ib.status,
                COUNT(ib.id) as batchCount,
                SUM(ib.totalRecords) as totalRecords,
                SUM(ib.successRecords) as successRecords,
                SUM(ib.failedRecords) as failedRecords,
                AVG(ib.processingDuration) as avgProcessingTime
            ')
            ->groupBy('ib.status')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']->value] = [
                'batch_count' => (int)$row['batchCount'],
                'total_records' => (int)$row['totalRecords'],
                'success_records' => (int)$row['successRecords'],
                'failed_records' => (int)$row['failedRecords'],
                'avg_processing_time' => $row['avgProcessingTime'] !== null ? round((float)$row['avgProcessingTime'], 2) : 0,
            ];
        }

        return $statistics;
    }

    /**
     * 查找长时间未完成的批次
     */
    public function findStuckBatches(int $hours = 2): array
    {
        $cutoffTime = new \DateTimeImmutable("-{$hours} hours");
        
        return $this->createQueryBuilder('ib')
            ->where('ib.status = :status')
            ->andWhere('ib.startTime < :cutoffTime')
            ->setParameter('status', ImportStatus::PROCESSING)
            ->setParameter('cutoffTime', $cutoffTime)
            ->orderBy('ib.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 清理旧的导入批次
     */
    public function findOldBatches(int $days = 30): array
    {
        $cutoffTime = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('ib')
            ->where('ib.createTime < :cutoffTime')
            ->andWhere('ib.status IN (:finalStatuses)')
            ->setParameter('cutoffTime', $cutoffTime)
            ->setParameter('finalStatuses', [ImportStatus::COMPLETED, ImportStatus::FAILED, ImportStatus::CANCELLED])
            ->orderBy('ib.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 