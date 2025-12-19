<?php

namespace Tourze\RealNameAuthenticationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;

/**
 * 导入批次Repository
 *
 * @template-extends ServiceEntityRepository<ImportBatch>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: ImportBatch::class)]
final class ImportBatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportBatch::class);
    }

    /**
     * 根据状态查找导入批次
     *
     * @return array<ImportBatch>
     */
    public function findByStatus(ImportStatus $status): array
    {
        $result = $this->createQueryBuilder('ib')
            ->where('ib.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ib.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportBatch> $result */
        return $result;
    }

    /**
     * 查找正在处理的批次
     *
     * @return array<ImportBatch>
     */
    public function findProcessingBatches(): array
    {
        return $this->findByStatus(ImportStatus::PROCESSING);
    }

    /**
     * 查找待处理的批次
     *
     * @return array<ImportBatch>
     */
    public function findPendingBatches(): array
    {
        return $this->findByStatus(ImportStatus::PENDING);
    }

    /**
     * 查找用户的导入批次
     *
     * @return array<ImportBatch>
     */
    public function findByUser(string $userId, int $limit = 20): array
    {
        $result = $this->createQueryBuilder('ib')
            ->where('ib.createdBy = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ib.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportBatch> $result */
        return $result;
    }

    /**
     * 统计指定时间范围内的导入批次
     *
     * @return array<string, int>
     */
    public function countByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<int, array{status: ImportStatus, count: int|string}> $result */
        $result = $this->createQueryBuilder('ib')
            ->select('ib.status, COUNT(ib.id) as count')
            ->where('ib.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('ib.status')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']->value] = (int) $row['count'];
        }

        return $statistics;
    }

    /**
     * 查找重复的文件（相同MD5）
     *
     * @return array<ImportBatch>
     */
    public function findDuplicateFiles(string $fileMd5): array
    {
        $result = $this->createQueryBuilder('ib')
            ->where('ib.fileMd5 = :fileMd5')
            ->setParameter('fileMd5', $fileMd5)
            ->orderBy('ib.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportBatch> $result */
        return $result;
    }

    /**
     * 获取最近的导入批次（分页）
     *
     * @return array<ImportBatch>
     */
    public function findRecentBatches(int $page = 1, int $size = 20): array
    {
        $offset = ($page - 1) * $size;

        $result = $this->createQueryBuilder('ib')
            ->orderBy('ib.createTime', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportBatch> $result */
        return $result;
    }

    /**
     * 统计导入批次总数
     *
     * @return int
     */
    public function countTotal(): int
    {
        /** @var int|string|null $result */
        $result = $this->createQueryBuilder('ib')
            ->select('COUNT(ib.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 获取导入统计信息
     *
     * @return array<string, array<string, int|float>>
     */
    public function getImportStatistics(): array
    {
        /** @var array<int, array{status: ImportStatus, batchCount: int|string, totalRecords: int|string|null, successRecords: int|string|null, failedRecords: int|string|null, avgProcessingTime: float|string|null}> $result */
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
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']->value] = [
                'batch_count' => (int) $row['batchCount'],
                'total_records' => (int) ($row['totalRecords'] ?? 0),
                'success_records' => (int) ($row['successRecords'] ?? 0),
                'failed_records' => (int) ($row['failedRecords'] ?? 0),
                'avg_processing_time' => null !== $row['avgProcessingTime'] ? round((float) $row['avgProcessingTime'], 2) : 0,
            ];
        }

        return $statistics;
    }

    /**
     * 查找长时间未完成的批次
     *
     * @return array<ImportBatch>
     */
    public function findStuckBatches(int $hours = 2): array
    {
        $cutoffTime = new \DateTimeImmutable("-{$hours} hours");

        $result = $this->createQueryBuilder('ib')
            ->where('ib.status = :status')
            ->andWhere('ib.startTime < :cutoffTime')
            ->setParameter('status', ImportStatus::PROCESSING)
            ->setParameter('cutoffTime', $cutoffTime)
            ->orderBy('ib.startTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportBatch> $result */
        return $result;
    }

    /**
     * 清理旧的导入批次
     *
     * @return array<ImportBatch>
     */
    public function findOldBatches(int $days = 30): array
    {
        $cutoffTime = new \DateTimeImmutable("-{$days} days");

        $result = $this->createQueryBuilder('ib')
            ->where('ib.createTime < :cutoffTime')
            ->andWhere('ib.status IN (:finalStatuses)')
            ->setParameter('cutoffTime', $cutoffTime)
            ->setParameter('finalStatuses', [ImportStatus::COMPLETED, ImportStatus::FAILED, ImportStatus::CANCELLED])
            ->orderBy('ib.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportBatch> $result */
        return $result;
    }

    /**
     * 获取用户的导入统计信息
     *
     * @return array<string, array<string, int|float>>
     */
    public function getStatisticsForUser(string $userId): array
    {
        /** @var array<int, array{status: ImportStatus, batchCount: int|string, totalRecords: int|string|null, successRecords: int|string|null, failedRecords: int|string|null, avgProcessingTime: float|string|null}> $result */
        $result = $this->createQueryBuilder('ib')
            ->select('
                ib.status,
                COUNT(ib.id) as batchCount,
                SUM(ib.totalRecords) as totalRecords,
                SUM(ib.successRecords) as successRecords,
                SUM(ib.failedRecords) as failedRecords,
                AVG(ib.processingDuration) as avgProcessingTime
            ')
            ->where('ib.createdBy = :userId')
            ->setParameter('userId', $userId)
            ->groupBy('ib.status')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']->value] = [
                'batch_count' => (int) $row['batchCount'],
                'total_records' => (int) ($row['totalRecords'] ?? 0),
                'success_records' => (int) ($row['successRecords'] ?? 0),
                'failed_records' => (int) ($row['failedRecords'] ?? 0),
                'avg_processing_time' => null !== $row['avgProcessingTime'] ? round((float) $row['avgProcessingTime'], 2) : 0,
            ];
        }

        return $statistics;
    }

    public function save(ImportBatch $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ImportBatch $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
