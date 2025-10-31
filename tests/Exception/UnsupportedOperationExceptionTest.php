<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RealNameAuthenticationBundle\Exception\UnsupportedOperationException;

/**
 * @internal
 */
#[CoversClass(UnsupportedOperationException::class)]
final class UnsupportedOperationExceptionTest extends AbstractExceptionTestCase
{
    public function testDefaultMessage(): void
    {
        $exception = new UnsupportedOperationException();

        $this->assertEquals('不支持的操作', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCustomMessage(): void
    {
        $exception = new UnsupportedOperationException('自定义错误消息');

        $this->assertEquals('自定义错误消息', $exception->getMessage());
    }

    public function testWithCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new UnsupportedOperationException('测试消息', 123, $previous);

        $this->assertEquals('测试消息', $exception->getMessage());
        $this->assertEquals(123, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testInheritance(): void
    {
        $exception = new UnsupportedOperationException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }
}
