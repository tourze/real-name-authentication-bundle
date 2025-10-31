<?php

namespace Tourze\RealNameAuthenticationBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 导入记录状态枚举
 *
 * 表示单条导入记录的处理状态
 */
enum ImportRecordStatus: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '等待处理',
            self::SUCCESS => '成功',
            self::FAILED => '失败',
            self::SKIPPED => '已跳过',
        };
    }

    /**
     * 判断是否为最终状态
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::PENDING => false,
            self::SUCCESS, self::FAILED, self::SKIPPED => true,
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::PENDING => BadgeInterface::WARNING,
            self::SUCCESS => BadgeInterface::SUCCESS,
            self::FAILED => BadgeInterface::DANGER,
            self::SKIPPED => BadgeInterface::SECONDARY,
        };
    }
}
