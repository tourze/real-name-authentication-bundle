<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Repository\ImportRecordRepository;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Repository\ImportRecordRepository
 */
class ImportRecordRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(ImportRecordRepository::class));
    }

    public function testRepositoryConfiguration(): void
    {
        $this->assertTrue(class_exists(ImportRecord::class));
        $this->assertTrue(class_exists(ImportRecordRepository::class));
    }
}