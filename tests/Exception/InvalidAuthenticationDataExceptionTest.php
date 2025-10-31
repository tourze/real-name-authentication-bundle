<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;

/**
 * @internal
 */
#[CoversClass(InvalidAuthenticationDataException::class)]
final class InvalidAuthenticationDataExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new InvalidAuthenticationDataException('test message');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(InvalidAuthenticationDataException::class, $exception);
        $this->assertSame('test message', $exception->getMessage());
    }
}
