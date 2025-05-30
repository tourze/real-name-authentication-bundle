<?php

namespace Tourze\RealNameAuthenticationBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 认证状态枚举
 * 定义实名认证的各种状态
 */
enum AuthenticationStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待审核',
            self::PROCESSING => '审核中',
            self::APPROVED => '已通过',
            self::REJECTED => '已拒绝',
            self::EXPIRED => '已过期',
        };
    }

    /**
     * 判断是否为最终状态
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED, self::EXPIRED]);
    }
}
 