<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tourze\RealNameAuthenticationBundle\Exception\ProviderNotAvailableException;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Exception\ProviderNotAvailableException
 */
class ProviderNotAvailableExceptionTest extends TestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new ProviderNotAvailableException('test message');
        
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(ProviderNotAvailableException::class, $exception);
        $this->assertSame('test message', $exception->getMessage());
    }
}