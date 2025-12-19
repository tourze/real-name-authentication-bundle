<?php

namespace Tourze\RealNameAuthenticationBundle\Exception;

/**
 * 不支持的操作异常
 *
 * 当调用了不支持的操作或方法时抛出此异常
 */
final class UnsupportedOperationException extends \RuntimeException
{
    public function __construct(string $message = '不支持的操作', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
