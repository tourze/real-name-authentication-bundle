<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException
 */
class AuthenticationExceptionTest extends TestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new AuthenticationException('test message');
        
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertSame('test message', $exception->getMessage());
    }
}