<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Entity\ImportBatch
 */
class ImportBatchTest extends TestCase
{
    public function testEntityExists(): void
    {
        $this->assertTrue(class_exists(ImportBatch::class));
    }

    public function testEntityConfiguration(): void
    {
        $entity = new ImportBatch();
        
        $this->assertInstanceOf(ImportBatch::class, $entity);
    }
}