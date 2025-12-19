<?php

namespace Tourze\RealNameAuthenticationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;

/**
 * 导入记录Repository
 *
 * @template-extends ServiceEntityRepository<ImportRecord>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: ImportRecord::class)]
final class ImportRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportRecord::class);
    }

    /**
     * 根据批次查找导入记录
     *
     * @return array<ImportRecord>
     */
    public function findByBatch(ImportBatch $batch): array
    {
        $result = $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->setParameter('batch', $batch)
            ->orderBy('ir.rowNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportRecord> $result */
        return $result;
    }

    /**
     * 根据批次和状态查找导入记录
     *
     * @return array<ImportRecord>
     */
    public function findByBatchAndStatus(ImportBatch $batch, ImportRecordStatus $status): array
    {
        $result = $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->andWhere('ir.status = :status')
            ->setParameter('batch', $batch)
            ->setParameter('status', $status)
            ->orderBy('ir.rowNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportRecord> $result */
        return $result;
    }

    /**
     * 查找批次中失败的记录
     *
     * @return array<ImportRecord>
     */
    public function findFailedRecords(ImportBatch $batch): array
    {
        return $this->findByBatchAndStatus($batch, ImportRecordStatus::FAILED);
    }

    /**
     * 查找批次中成功的记录
     *
     * @return array<ImportRecord>
     */
    public function findSuccessRecords(ImportBatch $batch): array
    {
        return $this->findByBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
    }

    /**
     * 查找批次中待处理的记录
     *
     * @return array<ImportRecord>
     */
    public function findPendingRecords(ImportBatch $batch): array
    {
        return $this->findByBatchAndStatus($batch, ImportRecordStatus::PENDING);
    }

    /**
     * 统计批次中各状态的记录数量
     *
     * @return array<string, int>
     */
    public function countByBatchAndStatus(ImportBatch $batch): array
    {
        /** @var array<int, array{status: ImportRecordStatus, count: int|string}> $result */
        $result = $this->createQueryBuilder('ir')
            ->select('ir.status, COUNT(ir.id) as count')
            ->where('ir.batch = :batch')
            ->setParameter('batch', $batch)
            ->groupBy('ir.status')
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
     * 分页查找批次的导入记录
     *
     * @return array<ImportRecord>
     */
    public function findByBatchWithPagination(ImportBatch $batch, int $page = 1, int $size = 50): array
    {
        $offset = ($page - 1) * $size;

        $result = $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->setParameter('batch', $batch)
            ->orderBy('ir.rowNumber', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportRecord> $result */
        return $result;
    }

    /**
     * 获取批次中特定行号的记录
     *
     * @return ImportRecord|null
     */
    public function findByBatchAndRowNumber(ImportBatch $batch, int $rowNumber): ?ImportRecord
    {
        $result = $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->andWhere('ir.rowNumber = :rowNumber')
            ->setParameter('batch', $batch)
            ->setParameter('rowNumber', $rowNumber)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        assert($result instanceof ImportRecord || null === $result);

        return $result;
    }

    /**
     * 查找包含特定错误信息的记录
     *
     * @return array<ImportRecord>
     */
    public function findByErrorPattern(ImportBatch $batch, string $errorPattern): array
    {
        $result = $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->andWhere('ir.errorMessage LIKE :pattern')
            ->setParameter('batch', $batch)
            ->setParameter('pattern', '%' . $errorPattern . '%')
            ->orderBy('ir.rowNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportRecord> $result */
        return $result;
    }

    /**
     * 获取批次的处理进度信息
     *
     * @return array<string, int>
     */
    public function getBatchProgress(ImportBatch $batch): array
    {
        /** @var array{total: int|string, success: int|string, failed: int|string, skipped: int|string, pending: int|string} $result */
        $result = $this->createQueryBuilder('ir')
            ->select('
                COUNT(ir.id) as total,
                SUM(CASE WHEN ir.status = :success THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN ir.status = :failed THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN ir.status = :skipped THEN 1 ELSE 0 END) as skipped,
                SUM(CASE WHEN ir.status = :pending THEN 1 ELSE 0 END) as pending
            ')
            ->where('ir.batch = :batch')
            ->setParameter('batch', $batch)
            ->setParameter('success', ImportRecordStatus::SUCCESS)
            ->setParameter('failed', ImportRecordStatus::FAILED)
            ->setParameter('skipped', ImportRecordStatus::SKIPPED)
            ->setParameter('pending', ImportRecordStatus::PENDING)
            ->getQuery()
            ->getSingleResult()
        ;

        return [
            'total' => (int) $result['total'],
            'success' => (int) $result['success'],
            'failed' => (int) $result['failed'],
            'skipped' => (int) $result['skipped'],
            'pending' => (int) $result['pending'],
            'processed' => (int) $result['success'] + (int) $result['failed'] + (int) $result['skipped'],
        ];
    }

    /**
     * 获取批次的错误统计
     *
     * @return array<string, int>
     */
    public function getErrorStatistics(ImportBatch $batch): array
    {
        /** @var array<int, array{errorMessage: string}> $records */
        $records = $this->createQueryBuilder('ir')
            ->select('ir.errorMessage')
            ->where('ir.batch = :batch')
            ->andWhere('ir.status = :failed')
            ->andWhere('ir.errorMessage IS NOT NULL')
            ->setParameter('batch', $batch)
            ->setParameter('failed', ImportRecordStatus::FAILED)
            ->getQuery()
            ->getResult()
        ;

        $errorCounts = [];
        foreach ($records as $record) {
            $error = $record['errorMessage'];
            if (!isset($errorCounts[$error])) {
                $errorCounts[$error] = 0;
            }
            ++$errorCounts[$error];
        }

        // 按错误次数排序
        arsort($errorCounts);

        return $errorCounts;
    }

    /**
     * 清理批次的所有记录
     *
     * @return int
     */
    public function deleteByBatch(ImportBatch $batch): int
    {
        $result = $this->createQueryBuilder('ir')
            ->delete()
            ->where('ir.batch = :batch')
            ->setParameter('batch', $batch)
            ->getQuery()
            ->execute()
        ;
        assert(is_int($result));

        return $result;
    }

    /**
     * 获取最近的导入记录错误（用于调试）
     *
     * @return array<ImportRecord>
     */
    public function findRecentErrors(int $limit = 20): array
    {
        $result = $this->createQueryBuilder('ir')
            ->where('ir.status = :failed')
            ->andWhere('ir.errorMessage IS NOT NULL')
            ->setParameter('failed', ImportRecordStatus::FAILED)
            ->orderBy('ir.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportRecord> $result */
        return $result;
    }

    /**
     * 查找处理时间最长的记录
     *
     * @return array<ImportRecord>
     */
    public function findSlowestRecords(ImportBatch $batch, int $limit = 10): array
    {
        $result = $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->andWhere('ir.processingTime IS NOT NULL')
            ->setParameter('batch', $batch)
            ->orderBy('ir.processingTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportRecord> $result */
        return $result;
    }

    /**
     * 根据状态查找导入记录
     *
     * @return array<ImportRecord>
     */
    public function findByStatus(ImportRecordStatus $status): array
    {
        $result = $this->createQueryBuilder('ir')
            ->where('ir.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ir.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));

        /** @var list<ImportRecord> $result */
        return $result;
    }

    /**
     * 获取批次的统计信息
     *
     * @return array<string, int>
     */
    public function getStatisticsByBatch(ImportBatch $batch): array
    {
        return $this->getBatchProgress($batch);
    }

    public function save(ImportRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ImportRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
