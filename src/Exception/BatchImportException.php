<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Exception;

/**
 * 批量导入相关异常
 */
final class BatchImportException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
