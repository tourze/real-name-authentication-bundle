<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Unit\Exception;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException
 */
class InvalidAuthenticationDataExceptionTest extends TestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new InvalidAuthenticationDataException('test message');
        
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(InvalidAuthenticationDataException::class, $exception);
        $this->assertSame('test message', $exception->getMessage());
    }
}