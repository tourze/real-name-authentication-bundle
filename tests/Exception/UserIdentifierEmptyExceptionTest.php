<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RealNameAuthenticationBundle\Exception\UserIdentifierEmptyException;

/**
 * @internal
 */
#[CoversClass(UserIdentifierEmptyException::class)]
final class UserIdentifierEmptyExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new UserIdentifierEmptyException('Test message');

        $this->assertInstanceOf(UserIdentifierEmptyException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithCode(): void
    {
        $exception = new UserIdentifierEmptyException('Test message', 2001);

        $this->assertEquals(2001, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new UserIdentifierEmptyException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
