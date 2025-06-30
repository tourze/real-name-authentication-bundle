<?php

namespace Tourze\RealNameAuthenticationBundle\Enum;

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
enum ImportStatus: string implements Labelable, Itemable, Selectable
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
            self::PROCESSING => '正在处理',
            self::COMPLETED => '处理完成',
            self::FAILED => '处理失败',
            self::CANCELLED => '已取消',
        };
    }

    /**
     * 判断是否为最终状态
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED]);
    }

    /**
     * 判断是否可以取消
     */
    public function isCancellable(): bool
    {
        return in_array($this, [self::PENDING]);
    }

    /**
     * 获取状态对应的CSS颜色类
     */
    public function getCssClass(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'secondary',
        };
    }
} 