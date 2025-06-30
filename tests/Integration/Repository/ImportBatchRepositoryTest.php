<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Repository\ImportBatchRepository;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Repository\ImportBatchRepository
 */
class ImportBatchRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(ImportBatchRepository::class));
    }

    public function testRepositoryConfiguration(): void
    {
        $this->assertTrue(class_exists(ImportBatch::class));
        $this->assertTrue(class_exists(ImportBatchRepository::class));
    }
}