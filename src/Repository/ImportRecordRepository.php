<?php

namespace Tourze\RealNameAuthenticationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;

/**
 * 导入记录Repository
 */
class ImportRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportRecord::class);
    }

    /**
     * 根据批次查找导入记录
     */
    public function findByBatch(ImportBatch $batch): array
    {
        return $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->setParameter('batch', $batch)
            ->orderBy('ir.rowNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据批次和状态查找导入记录
     */
    public function findByBatchAndStatus(ImportBatch $batch, ImportRecordStatus $status): array
    {
        return $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->andWhere('ir.status = :status')
            ->setParameter('batch', $batch)
            ->setParameter('status', $status)
            ->orderBy('ir.rowNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找批次中失败的记录
     */
    public function findFailedRecords(ImportBatch $batch): array
    {
        return $this->findByBatchAndStatus($batch, ImportRecordStatus::FAILED);
    }

    /**
     * 查找批次中成功的记录
     */
    public function findSuccessRecords(ImportBatch $batch): array
    {
        return $this->findByBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
    }

    /**
     * 查找批次中待处理的记录
     */
    public function findPendingRecords(ImportBatch $batch): array
    {
        return $this->findByBatchAndStatus($batch, ImportRecordStatus::PENDING);
    }

    /**
     * 统计批次中各状态的记录数量
     */
    public function countByBatchAndStatus(ImportBatch $batch): array
    {
        $result = $this->createQueryBuilder('ir')
            ->select('ir.status, COUNT(ir.id) as count')
            ->where('ir.batch = :batch')
            ->setParameter('batch', $batch)
            ->groupBy('ir.status')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']->value] = (int)$row['count'];
        }

        return $statistics;
    }

    /**
     * 分页查找批次的导入记录
     */
    public function findByBatchWithPagination(ImportBatch $batch, int $page = 1, int $size = 50): array
    {
        $offset = ($page - 1) * $size;
        
        return $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->setParameter('batch', $batch)
            ->orderBy('ir.rowNumber', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取批次中特定行号的记录
     */
    public function findByBatchAndRowNumber(ImportBatch $batch, int $rowNumber): ?ImportRecord
    {
        return $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->andWhere('ir.rowNumber = :rowNumber')
            ->setParameter('batch', $batch)
            ->setParameter('rowNumber', $rowNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 查找包含特定错误信息的记录
     */
    public function findByErrorPattern(ImportBatch $batch, string $errorPattern): array
    {
        return $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->andWhere('ir.errorMessage LIKE :pattern')
            ->setParameter('batch', $batch)
            ->setParameter('pattern', '%' . $errorPattern . '%')
            ->orderBy('ir.rowNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取批次的处理进度信息
     */
    public function getBatchProgress(ImportBatch $batch): array
    {
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
            ->getSingleResult();

        return [
            'total' => (int)$result['total'],
            'success' => (int)$result['success'],
            'failed' => (int)$result['failed'],
            'skipped' => (int)$result['skipped'],
            'pending' => (int)$result['pending'],
            'processed' => (int)$result['success'] + (int)$result['failed'] + (int)$result['skipped'],
        ];
    }

    /**
     * 获取批次的错误统计
     */
    public function getErrorStatistics(ImportBatch $batch): array
    {
        $records = $this->createQueryBuilder('ir')
            ->select('ir.errorMessage')
            ->where('ir.batch = :batch')
            ->andWhere('ir.status = :failed')
            ->andWhere('ir.errorMessage IS NOT NULL')
            ->setParameter('batch', $batch)
            ->setParameter('failed', ImportRecordStatus::FAILED)
            ->getQuery()
            ->getResult();

        $errorCounts = [];
        foreach ($records as $record) {
            $error = $record['errorMessage'];
            if (!isset($errorCounts[$error])) {
                $errorCounts[$error] = 0;
            }
            $errorCounts[$error]++;
        }

        // 按错误次数排序
        arsort($errorCounts);

        return $errorCounts;
    }

    /**
     * 清理批次的所有记录
     */
    public function deleteByBatch(ImportBatch $batch): int
    {
        return $this->createQueryBuilder('ir')
            ->delete()
            ->where('ir.batch = :batch')
            ->setParameter('batch', $batch)
            ->getQuery()
            ->execute();
    }

    /**
     * 获取最近的导入记录错误（用于调试）
     */
    public function findRecentErrors(int $limit = 20): array
    {
        return $this->createQueryBuilder('ir')
            ->where('ir.status = :failed')
            ->andWhere('ir.errorMessage IS NOT NULL')
            ->setParameter('failed', ImportRecordStatus::FAILED)
            ->orderBy('ir.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找处理时间最长的记录
     */
    public function findSlowestRecords(ImportBatch $batch, int $limit = 10): array
    {
        return $this->createQueryBuilder('ir')
            ->where('ir.batch = :batch')
            ->andWhere('ir.processingTime IS NOT NULL')
            ->setParameter('batch', $batch)
            ->orderBy('ir.processingTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
} 