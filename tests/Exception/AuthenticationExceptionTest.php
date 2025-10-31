<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;

/**
 * @internal
 */
#[CoversClass(AuthenticationException::class)]
final class AuthenticationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new AuthenticationException('test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertSame('test message', $exception->getMessage());
    }
}
