<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;

/**
 * @internal
 */
#[CoversClass(ImportBatch::class)]
final class ImportBatchTest extends AbstractEntityTestCase
{
    public function testEntityConfiguration(): void
    {
        $entity = new ImportBatch();

        $this->assertInstanceOf(ImportBatch::class, $entity);
    }

    /**
     * 创建被测实体的实例
     */
    protected function createEntity(): object
    {
        $entity = new ImportBatch();
        $entity->setOriginalFileName('test_file.csv');
        $entity->setFileType('csv');
        $entity->setFileSize(1024);
        $entity->setFileMd5('d41d8cd98f00b204e9800998ecf8427e');

        return $entity;
    }

    /**
     * 提供属性及其样本值的 Data Provider
     *
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'originalFileName' => ['originalFileName', 'new_test_file.xlsx'];
        yield 'fileType' => ['fileType', 'xlsx'];
        yield 'fileSize' => ['fileSize', 2048];
        yield 'fileMd5' => ['fileMd5', 'c4ca4238a0b923820dcc509a6f75849b'];
        yield 'status' => ['status', ImportStatus::PROCESSING];
        yield 'totalRecords' => ['totalRecords', 100];
        yield 'processedRecords' => ['processedRecords', 50];
        yield 'successRecords' => ['successRecords', 45];
        yield 'failedRecords' => ['failedRecords', 5];
        yield 'startTime' => ['startTime', new \DateTimeImmutable()];
        yield 'finishTime' => ['finishTime', new \DateTimeImmutable('+1 hour')];
        yield 'processingDuration' => ['processingDuration', 3600];
        yield 'importConfig' => ['importConfig', ['skip_header' => true, 'encoding' => 'utf-8']];
        yield 'errorMessage' => ['errorMessage', '处理失败'];
        yield 'remark' => ['remark', '测试批次'];
        yield 'valid' => ['valid', false];
    }
}
