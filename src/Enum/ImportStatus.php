<?php

namespace Tourze\RealNameAuthenticationBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 导入状态枚举
 *
 * 表示批量导入的各种状态
 */
enum ImportStatus: string implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '等待处理',
            self::PROCESSING => '处理中',
            self::COMPLETED => '已完成',
            self::FAILED => '失败',
            self::CANCELLED => '已取消',
        };
    }

    /**
     * 判断是否为最终状态
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::PENDING, self::PROCESSING => false,
            self::COMPLETED, self::FAILED, self::CANCELLED => true,
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::PENDING => BadgeInterface::WARNING,
            self::PROCESSING => BadgeInterface::INFO,
            self::COMPLETED => BadgeInterface::SUCCESS,
            self::FAILED => BadgeInterface::DANGER,
            self::CANCELLED => BadgeInterface::SECONDARY,
        };
    }

    /**
     * 获取状态的CSS样式类
     * @deprecated 使用 getBadge() 方法代替
     */
    public function getCssClass(): string
    {
        return $this->getBadge();
    }

    /**
     * 判断是否可以取消
     */
    public function isCancellable(): bool
    {
        return match ($this) {
            self::PENDING, self::PROCESSING => true,
            self::COMPLETED, self::FAILED, self::CANCELLED => false,
        };
    }
}
