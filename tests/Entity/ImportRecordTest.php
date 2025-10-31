<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;

/**
 * @internal
 */
#[CoversClass(ImportRecord::class)]
final class ImportRecordTest extends AbstractEntityTestCase
{
    private ImportBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batch = new ImportBatch();
        $this->batch->setOriginalFileName('test_batch.csv');
        $this->batch->setFileType('csv');
        $this->batch->setFileSize(1024);
        $this->batch->setFileMd5('d41d8cd98f00b204e9800998ecf8427e');
    }

    public function testEntityConfiguration(): void
    {
        $entity = new ImportRecord();

        $this->assertInstanceOf(ImportRecord::class, $entity);
    }

    /**
     * 创建被测实体的实例
     */
    protected function createEntity(): object
    {
        $entity = new ImportRecord();
        $entity->setBatch($this->batch);
        $entity->setRowNumber(1);
        $entity->setRawData(['name' => '张三', 'id_card' => '11010119900101100X']);

        return $entity;
    }

    /**
     * 提供属性及其样本值的 Data Provider
     *
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        // 注意：batch 属性需要 setUp 中的依赖，这里只测试简单属性
        yield 'rowNumber' => ['rowNumber', 2];
        yield 'status' => ['status', ImportRecordStatus::SUCCESS];
        yield 'rawData' => ['rawData', ['name' => '李四', 'id_card' => '11010119900101100Y']];
        yield 'processedData' => ['processedData', ['normalized_name' => '李四', 'validated_id' => true]];
        yield 'errorMessage' => ['errorMessage', '身份证格式错误'];
        yield 'validationErrors' => ['validationErrors', ['id_card' => '身份证号码格式不正确']];
        yield 'remark' => ['remark', '处理备注'];
        yield 'processingTime' => ['processingTime', 150];
        yield 'valid' => ['valid', false];
    }
}
