<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RealNameAuthenticationBundle\Exception\BatchImportException;

/**
 * @internal
 */
#[CoversClass(BatchImportException::class)]
final class BatchImportExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new BatchImportException('Test message');

        $this->assertInstanceOf(BatchImportException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithCode(): void
    {
        $exception = new BatchImportException('Test message', 1001);

        $this->assertEquals(1001, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new BatchImportException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
