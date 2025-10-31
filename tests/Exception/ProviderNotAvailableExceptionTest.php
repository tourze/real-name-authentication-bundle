<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RealNameAuthenticationBundle\Exception\ProviderNotAvailableException;

/**
 * @internal
 */
#[CoversClass(ProviderNotAvailableException::class)]
final class ProviderNotAvailableExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new ProviderNotAvailableException('test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(ProviderNotAvailableException::class, $exception);
        $this->assertSame('test message', $exception->getMessage());
    }
}
