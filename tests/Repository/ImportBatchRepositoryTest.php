<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;
use Tourze\RealNameAuthenticationBundle\Repository\ImportBatchRepository;

/**
 * @internal
 */
#[CoversClass(ImportBatchRepository::class)]
#[RunTestsInSeparateProcesses]
final class ImportBatchRepositoryTest extends AbstractRepositoryTestCase
{
    private ImportBatchRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ImportBatchRepository::class);
    }

    public function testRepositoryConfiguration(): void
    {
        $this->assertInstanceOf(ImportBatchRepository::class, $this->repository);
    }

    public function testPaginationWithValidParams(): void
    {
        $entity1 = $this->createNewEntity();
        $entity2 = $this->createNewEntity();
        $entity3 = $this->createNewEntity();

        self::getEntityManager()->persist($entity1);
        self::getEntityManager()->persist($entity2);
        self::getEntityManager()->persist($entity3);
        self::getEntityManager()->flush();

        $result = $this->repository->findBy([], null, 2, 1);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(ImportBatch::class, $result);
    }

    public function testPaginationWithLargeOffset(): void
    {
        $entity = $this->createNewEntity();
        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();

        $result = $this->repository->findBy([], null, 10, 1000);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindByStatus(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportBatch::class)->execute();
        self::getEntityManager()->flush();

        $pendingBatch = $this->createBatchWithStatus(ImportStatus::PENDING);
        $processingBatch = $this->createBatchWithStatus(ImportStatus::PROCESSING);
        $completedBatch = $this->createBatchWithStatus(ImportStatus::COMPLETED);

        self::getEntityManager()->persist($pendingBatch);
        self::getEntityManager()->persist($processingBatch);
        self::getEntityManager()->persist($completedBatch);
        self::getEntityManager()->flush();

        $pendingResults = $this->repository->findByStatus(ImportStatus::PENDING);
        $this->assertCount(1, $pendingResults);
        $this->assertEquals(ImportStatus::PENDING, $pendingResults[0]->getStatus());
    }

    public function testFindByUser(): void
    {
        $user1Batch = $this->createBatchWithUser('user1');
        $user2Batch = $this->createBatchWithUser('user2');

        self::getEntityManager()->persist($user1Batch);
        self::getEntityManager()->persist($user2Batch);
        self::getEntityManager()->flush();

        $user1Results = $this->repository->findByUser('user1');
        $this->assertCount(1, $user1Results);
        $this->assertEquals('user1', $user1Results[0]->getCreatedBy());
    }

    public function testGetStatisticsForUser(): void
    {
        $batch = $this->createBatchWithUser('testuser');
        $batch->setStatus(ImportStatus::COMPLETED);
        $batch->setTotalRecords(100);
        $batch->setSuccessRecords(80);
        $batch->setFailedRecords(20);

        self::getEntityManager()->persist($batch);
        self::getEntityManager()->flush();

        $stats = $this->repository->getStatisticsForUser('testuser');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertEquals(1, $stats['completed']['batch_count']);
        $this->assertEquals(100, $stats['completed']['total_records']);
    }

    public function testCountByDateRange(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportBatch::class)->execute();
        self::getEntityManager()->flush();

        $now = new \DateTimeImmutable();
        $yesterday = $now->modify('-1 day');
        $tomorrow = $now->modify('+1 day');

        $batch1 = $this->createBatchWithStatus(ImportStatus::COMPLETED);
        $batch1->setCreateTime($now);
        $batch2 = $this->createBatchWithStatus(ImportStatus::PENDING);
        $batch2->setCreateTime($now);

        self::getEntityManager()->persist($batch1);
        self::getEntityManager()->persist($batch2);
        self::getEntityManager()->flush();

        $result = $this->repository->countByDateRange($yesterday, $tomorrow);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('completed', $result);
        $this->assertArrayHasKey('pending', $result);
        $this->assertEquals(1, $result['completed']);
        $this->assertEquals(1, $result['pending']);
    }

    public function testCountTotal(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportBatch::class)->execute();
        self::getEntityManager()->flush();

        $batch1 = $this->createNewEntity();
        $batch2 = $this->createNewEntity();

        self::getEntityManager()->persist($batch1);
        self::getEntityManager()->persist($batch2);
        self::getEntityManager()->flush();

        $count = $this->repository->countTotal();

        $this->assertIsInt($count);
        $this->assertEquals(2, $count);
    }

    public function testFindDuplicateFiles(): void
    {
        $md5Hash = 'duplicate_hash_123';

        $batch1 = $this->createNewEntity();
        $batch1->setFileMd5($md5Hash);
        $batch2 = $this->createNewEntity();
        $batch2->setFileMd5($md5Hash);
        $batch3 = $this->createNewEntity();
        $batch3->setFileMd5('different_hash');

        self::getEntityManager()->persist($batch1);
        self::getEntityManager()->persist($batch2);
        self::getEntityManager()->persist($batch3);
        self::getEntityManager()->flush();

        $duplicates = $this->repository->findDuplicateFiles($md5Hash);

        $this->assertCount(2, $duplicates);
        foreach ($duplicates as $duplicate) {
            $this->assertEquals($md5Hash, $duplicate->getFileMd5());
        }
    }

    public function testFindOldBatches(): void
    {
        $oldDate = new \DateTimeImmutable('-40 days');
        $recentDate = new \DateTimeImmutable('-10 days');

        $oldBatch = $this->createBatchWithStatus(ImportStatus::COMPLETED);
        $oldBatch->setCreateTime($oldDate);

        $recentBatch = $this->createBatchWithStatus(ImportStatus::COMPLETED);
        $recentBatch->setCreateTime($recentDate);

        $processingBatch = $this->createBatchWithStatus(ImportStatus::PROCESSING);
        $processingBatch->setCreateTime($oldDate);

        self::getEntityManager()->persist($oldBatch);
        self::getEntityManager()->persist($recentBatch);
        self::getEntityManager()->persist($processingBatch);
        self::getEntityManager()->flush();

        $oldBatches = $this->repository->findOldBatches(30);

        $this->assertCount(1, $oldBatches);
        $this->assertEquals(ImportStatus::COMPLETED, $oldBatches[0]->getStatus());
        $this->assertEquals($oldDate->format('Y-m-d'), $oldBatches[0]->getCreateTime()?->format('Y-m-d'));
    }

    public function testFindPendingBatches(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportBatch::class)->execute();
        self::getEntityManager()->flush();

        $pendingBatch = $this->createBatchWithStatus(ImportStatus::PENDING);
        $processingBatch = $this->createBatchWithStatus(ImportStatus::PROCESSING);
        $completedBatch = $this->createBatchWithStatus(ImportStatus::COMPLETED);

        self::getEntityManager()->persist($pendingBatch);
        self::getEntityManager()->persist($processingBatch);
        self::getEntityManager()->persist($completedBatch);
        self::getEntityManager()->flush();

        $pendingBatches = $this->repository->findPendingBatches();

        $this->assertCount(1, $pendingBatches);
        $this->assertEquals(ImportStatus::PENDING, $pendingBatches[0]->getStatus());
    }

    public function testFindProcessingBatches(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportBatch::class)->execute();
        self::getEntityManager()->flush();

        $pendingBatch = $this->createBatchWithStatus(ImportStatus::PENDING);
        $processingBatch = $this->createBatchWithStatus(ImportStatus::PROCESSING);
        $completedBatch = $this->createBatchWithStatus(ImportStatus::COMPLETED);

        self::getEntityManager()->persist($pendingBatch);
        self::getEntityManager()->persist($processingBatch);
        self::getEntityManager()->persist($completedBatch);
        self::getEntityManager()->flush();

        $processingBatches = $this->repository->findProcessingBatches();

        $this->assertCount(1, $processingBatches);
        $this->assertEquals(ImportStatus::PROCESSING, $processingBatches[0]->getStatus());
    }

    public function testFindRecentBatches(): void
    {
        $batch1 = $this->createNewEntity();
        $batch2 = $this->createNewEntity();
        $batch3 = $this->createNewEntity();

        self::getEntityManager()->persist($batch1);
        self::getEntityManager()->persist($batch2);
        self::getEntityManager()->persist($batch3);
        self::getEntityManager()->flush();

        $recentBatches = $this->repository->findRecentBatches(1, 2);

        $this->assertCount(2, $recentBatches);
        $this->assertContainsOnlyInstancesOf(ImportBatch::class, $recentBatches);
    }

    public function testFindStuckBatches(): void
    {
        // 清理旧数据以避免数据隔离问题
        $em = self::getEntityManager();
        $connection = $em->getConnection();
        $connection->executeStatement('DELETE FROM import_batch WHERE status = :status', [
            'status' => ImportStatus::PROCESSING->value,
        ]);
        $em->clear();

        $oldDate = new \DateTimeImmutable('-5 hours');
        $recentDate = new \DateTimeImmutable('-1 hour');

        $stuckBatch = $this->createBatchWithStatus(ImportStatus::PROCESSING);
        $stuckBatch->setStartTime($oldDate);

        $recentBatch = $this->createBatchWithStatus(ImportStatus::PROCESSING);
        $recentBatch->setStartTime($recentDate);

        $completedBatch = $this->createBatchWithStatus(ImportStatus::COMPLETED);
        $completedBatch->setStartTime($oldDate);

        $em->persist($stuckBatch);
        $em->persist($recentBatch);
        $em->persist($completedBatch);
        $em->flush();

        $stuckBatches = $this->repository->findStuckBatches(3);

        $this->assertCount(1, $stuckBatches);
        $this->assertEquals(ImportStatus::PROCESSING, $stuckBatches[0]->getStatus());
        $this->assertEquals($oldDate->format('Y-m-d H:i'), $stuckBatches[0]->getStartTime()?->format('Y-m-d H:i'));
    }

    private function createBatchWithStatus(ImportStatus $status): ImportBatch
    {
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test.xlsx');
        $batch->setFileType('application/xlsx');
        $batch->setFileSize(1024);
        $batch->setFileMd5('md5hash');
        $batch->setStatus($status);

        return $batch;
    }

    private function createBatchWithUser(string $userId): ImportBatch
    {
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test.xlsx');
        $batch->setFileType('application/xlsx');
        $batch->setFileSize(1024);
        $batch->setFileMd5('md5hash');
        $batch->setCreatedBy($userId);

        return $batch;
    }

    protected function createNewEntity(): ImportBatch
    {
        $entity = new ImportBatch();
        $entity->setOriginalFileName('test.xlsx');
        $entity->setFileType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $entity->setFileSize(1024);
        $entity->setFileMd5('d41d8cd98f00b204e9800998ecf8427e');

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<ImportBatch>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
