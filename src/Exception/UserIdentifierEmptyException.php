<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Exception;

/**
 * 用户标识符为空异常
 */
class UserIdentifierEmptyException extends \RuntimeException
{
    public function __construct(string $message = 'User identifier cannot be empty', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
