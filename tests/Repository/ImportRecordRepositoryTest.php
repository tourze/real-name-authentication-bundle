<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;
use Tourze\RealNameAuthenticationBundle\Repository\ImportRecordRepository;

/**
 * @internal
 */
#[CoversClass(ImportRecordRepository::class)]
#[RunTestsInSeparateProcesses]
final class ImportRecordRepositoryTest extends AbstractRepositoryTestCase
{
    private ImportRecordRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ImportRecordRepository::class);
    }

    public function testRepositoryConfiguration(): void
    {
        $this->assertInstanceOf(ImportRecordRepository::class, $this->repository);
    }

    public function testPaginationWithValidParams(): void
    {
        $entity1 = $this->createNewEntity();
        $entity2 = $this->createNewEntity();

        self::getEntityManager()->persist($entity1);
        self::getEntityManager()->persist($entity2);
        self::getEntityManager()->flush();

        $result = $this->repository->findBy([], null, 1, 1);

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(ImportRecord::class, $result);
    }

    public function testFindByBatch(): void
    {
        $batch1 = $this->createBatch();
        $batch2 = $this->createBatch();

        $record1 = $this->createRecordWithBatch($batch1);
        $record2 = $this->createRecordWithBatch($batch1);
        $record3 = $this->createRecordWithBatch($batch2);

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->persist($record3);
        self::getEntityManager()->flush();

        $batch1Records = $this->repository->findByBatch($batch1);
        $this->assertCount(2, $batch1Records);
        foreach ($batch1Records as $record) {
            $this->assertEquals($batch1->getId(), $record->getBatch()->getId());
        }
    }

    public function testFindByBatchAndStatus(): void
    {
        $batch = $this->createBatch();

        $successRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
        $failedRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);
        $pendingRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::PENDING);

        self::getEntityManager()->persist($successRecord);
        self::getEntityManager()->persist($failedRecord);
        self::getEntityManager()->persist($pendingRecord);
        self::getEntityManager()->flush();

        $successRecords = $this->repository->findByBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
        $this->assertCount(1, $successRecords);
        $this->assertEquals(ImportRecordStatus::SUCCESS, $successRecords[0]->getStatus());
    }

    public function testFindFailedRecords(): void
    {
        $batch = $this->createBatch();

        $successRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
        $failedRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);

        self::getEntityManager()->persist($successRecord);
        self::getEntityManager()->persist($failedRecord);
        self::getEntityManager()->flush();

        $failedRecords = $this->repository->findFailedRecords($batch);
        $this->assertCount(1, $failedRecords);
        $this->assertEquals(ImportRecordStatus::FAILED, $failedRecords[0]->getStatus());
    }

    public function testFindSuccessRecords(): void
    {
        $batch = $this->createBatch();

        $successRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
        $failedRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);

        self::getEntityManager()->persist($successRecord);
        self::getEntityManager()->persist($failedRecord);
        self::getEntityManager()->flush();

        $successRecords = $this->repository->findSuccessRecords($batch);
        $this->assertCount(1, $successRecords);
        $this->assertEquals(ImportRecordStatus::SUCCESS, $successRecords[0]->getStatus());
    }

    public function testFindPendingRecords(): void
    {
        $batch = $this->createBatch();

        $pendingRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::PENDING);
        $successRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SUCCESS);

        self::getEntityManager()->persist($pendingRecord);
        self::getEntityManager()->persist($successRecord);
        self::getEntityManager()->flush();

        $pendingRecords = $this->repository->findPendingRecords($batch);
        $this->assertCount(1, $pendingRecords);
        $this->assertEquals(ImportRecordStatus::PENDING, $pendingRecords[0]->getStatus());
    }

    public function testCountByBatchAndStatus(): void
    {
        $batch = $this->createBatch();

        $successRecord1 = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
        $successRecord2 = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
        $failedRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);

        self::getEntityManager()->persist($successRecord1);
        self::getEntityManager()->persist($successRecord2);
        self::getEntityManager()->persist($failedRecord);
        self::getEntityManager()->flush();

        $counts = $this->repository->countByBatchAndStatus($batch);
        $this->assertIsArray($counts);
        $this->assertEquals(2, $counts['success']);
        $this->assertEquals(1, $counts['failed']);
    }

    public function testFindByBatchWithPagination(): void
    {
        $batch = $this->createBatch();

        $record1 = $this->createRecordWithBatch($batch, 1);
        $record2 = $this->createRecordWithBatch($batch, 2);
        $record3 = $this->createRecordWithBatch($batch, 3);

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->persist($record3);
        self::getEntityManager()->flush();

        $page1Records = $this->repository->findByBatchWithPagination($batch, 1, 2);
        $this->assertCount(2, $page1Records);

        $page2Records = $this->repository->findByBatchWithPagination($batch, 2, 2);
        $this->assertCount(1, $page2Records);
    }

    public function testFindByBatchAndRowNumber(): void
    {
        $batch = $this->createBatch();

        $record1 = $this->createRecordWithBatch($batch, 1);
        $record2 = $this->createRecordWithBatch($batch, 2);

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $foundRecord = $this->repository->findByBatchAndRowNumber($batch, 1);
        $this->assertNotNull($foundRecord);
        $this->assertEquals(1, $foundRecord->getRowNumber());

        $notFoundRecord = $this->repository->findByBatchAndRowNumber($batch, 999);
        $this->assertNull($notFoundRecord);
    }

    public function testFindByErrorPattern(): void
    {
        $batch = $this->createBatch();

        $record1 = $this->createRecordWithBatch($batch);
        $record1->setErrorMessage('Validation failed: invalid ID number');

        $record2 = $this->createRecordWithBatch($batch);
        $record2->setErrorMessage('Database error: duplicate key');

        $record3 = $this->createRecordWithBatch($batch);
        $record3->setErrorMessage('Validation failed: missing name');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->persist($record3);
        self::getEntityManager()->flush();

        $validationErrorRecords = $this->repository->findByErrorPattern($batch, 'Validation failed');
        $this->assertCount(2, $validationErrorRecords);

        foreach ($validationErrorRecords as $record) {
            $this->assertStringContainsString('Validation failed', $record->getErrorMessage() ?? '');
        }
    }

    public function testGetBatchProgress(): void
    {
        $batch = $this->createBatch();

        $successRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
        $failedRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);
        $pendingRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::PENDING);
        $skippedRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SKIPPED);

        self::getEntityManager()->persist($successRecord);
        self::getEntityManager()->persist($failedRecord);
        self::getEntityManager()->persist($pendingRecord);
        self::getEntityManager()->persist($skippedRecord);
        self::getEntityManager()->flush();

        $progress = $this->repository->getBatchProgress($batch);
        $this->assertIsArray($progress);
        $this->assertEquals(4, $progress['total']);
        $this->assertEquals(1, $progress['success']);
        $this->assertEquals(1, $progress['failed']);
        $this->assertEquals(1, $progress['pending']);
        $this->assertEquals(1, $progress['skipped']);
        $this->assertEquals(3, $progress['processed']); // success + failed + skipped
    }

    public function testGetErrorStatistics(): void
    {
        $batch = $this->createBatch();

        $record1 = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);
        $record1->setErrorMessage('Validation failed');

        $record2 = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);
        $record2->setErrorMessage('Validation failed');

        $record3 = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);
        $record3->setErrorMessage('Database error');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->persist($record3);
        self::getEntityManager()->flush();

        $errorStats = $this->repository->getErrorStatistics($batch);
        $this->assertIsArray($errorStats);
        $this->assertEquals(2, $errorStats['Validation failed']);
        $this->assertEquals(1, $errorStats['Database error']);
    }

    public function testDeleteByBatch(): void
    {
        $batch = $this->createBatch();

        $record1 = $this->createRecordWithBatch($batch);
        $record2 = $this->createRecordWithBatch($batch);

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $deletedCount = $this->repository->deleteByBatch($batch);
        $this->assertEquals(2, $deletedCount);

        $remainingRecords = $this->repository->findByBatch($batch);
        $this->assertEmpty($remainingRecords);
    }

    public function testFindRecentErrors(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportRecord::class)->execute();
        self::getEntityManager()->flush();

        $batch = $this->createBatch();

        $failedRecord1 = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);
        $failedRecord1->setErrorMessage('Error 1');

        $failedRecord2 = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);
        $failedRecord2->setErrorMessage('Error 2');

        $successRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SUCCESS);

        self::getEntityManager()->persist($failedRecord1);
        self::getEntityManager()->persist($failedRecord2);
        self::getEntityManager()->persist($successRecord);
        self::getEntityManager()->flush();

        $recentErrors = $this->repository->findRecentErrors(10);
        $this->assertCount(2, $recentErrors);

        foreach ($recentErrors as $error) {
            $this->assertEquals(ImportRecordStatus::FAILED, $error->getStatus());
            $this->assertNotNull($error->getErrorMessage());
        }
    }

    public function testFindSlowestRecords(): void
    {
        $batch = $this->createBatch();

        $record1 = $this->createRecordWithBatch($batch);
        $record1->setProcessingTime(100);

        $record2 = $this->createRecordWithBatch($batch);
        $record2->setProcessingTime(200);

        $record3 = $this->createRecordWithBatch($batch);
        $record3->setProcessingTime(50);

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->persist($record3);
        self::getEntityManager()->flush();

        $slowestRecords = $this->repository->findSlowestRecords($batch, 2);
        $this->assertCount(2, $slowestRecords);
        $this->assertEquals(200, $slowestRecords[0]->getProcessingTime());
        $this->assertEquals(100, $slowestRecords[1]->getProcessingTime());
    }

    public function testFindByStatus(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportRecord::class)->execute();
        self::getEntityManager()->flush();

        $batch = $this->createBatch();

        $successRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
        $failedRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);

        self::getEntityManager()->persist($successRecord);
        self::getEntityManager()->persist($failedRecord);
        self::getEntityManager()->flush();

        $successRecords = $this->repository->findByStatus(ImportRecordStatus::SUCCESS);
        $this->assertCount(1, $successRecords);
        $this->assertEquals(ImportRecordStatus::SUCCESS, $successRecords[0]->getStatus());
    }

    public function testGetStatisticsByBatch(): void
    {
        $batch = $this->createBatch();

        $successRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::SUCCESS);
        $failedRecord = $this->createRecordWithBatchAndStatus($batch, ImportRecordStatus::FAILED);

        self::getEntityManager()->persist($successRecord);
        self::getEntityManager()->persist($failedRecord);
        self::getEntityManager()->flush();

        $statistics = $this->repository->getStatisticsByBatch($batch);
        $this->assertIsArray($statistics);
        $this->assertEquals(2, $statistics['total']);
        $this->assertEquals(1, $statistics['success']);
        $this->assertEquals(1, $statistics['failed']);
    }

    protected function createNewEntity(): object
    {
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test.xlsx');
        $batch->setFileType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $batch->setFileSize(1024);
        $batch->setFileMd5('d41d8cd98f00b204e9800998ecf8427e');
        self::getEntityManager()->persist($batch);
        self::getEntityManager()->flush();

        $entity = new ImportRecord();
        $entity->setBatch($batch);
        $entity->setRowNumber(1);
        $entity->setRawData(['name' => 'Test User', 'id_number' => '123456789012345678']);

        return $entity;
    }

    private function createBatch(): ImportBatch
    {
        $batch = new ImportBatch();
        $batch->setOriginalFileName('test.xlsx');
        $batch->setFileType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $batch->setFileSize(1024);
        $batch->setFileMd5('d41d8cd98f00b204e9800998ecf8427e' . uniqid());
        self::getEntityManager()->persist($batch);
        self::getEntityManager()->flush();

        return $batch;
    }

    private function createRecordWithBatch(ImportBatch $batch, int $rowNumber = 1): ImportRecord
    {
        $record = new ImportRecord();
        $record->setBatch($batch);
        $record->setRowNumber($rowNumber);
        $record->setRawData(['name' => 'Test User ' . $rowNumber, 'id_number' => '123456789012345678']);

        return $record;
    }

    private function createRecordWithBatchAndStatus(ImportBatch $batch, ImportRecordStatus $status, ?int $rowNumber = null): ImportRecord
    {
        $record = $this->createRecordWithBatch($batch, $rowNumber ?? random_int(1, 1000));
        $record->setStatus($status);

        return $record;
    }

    /**
     * @return ServiceEntityRepository<ImportRecord>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
