<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Entity\ImportRecord
 */
class ImportRecordTest extends TestCase
{
    public function testEntityExists(): void
    {
        $this->assertTrue(class_exists(ImportRecord::class));
    }

    public function testEntityConfiguration(): void
    {
        $entity = new ImportRecord();
        
        $this->assertInstanceOf(ImportRecord::class, $entity);
    }
}