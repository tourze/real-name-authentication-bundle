<?php

namespace Tourze\RealNameAuthenticationBundle\Enum;

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
enum ImportRecordStatus: string implements Labelable, Itemable, Selectable
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
            self::SUCCESS => '处理成功',
            self::FAILED => '处理失败',
            self::SKIPPED => '已跳过',
        };
    }

    /**
     * 判断是否为最终状态
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::SUCCESS, self::FAILED, self::SKIPPED]);
    }

    /**
     * 获取状态对应的CSS颜色类
     */
    public function getCssClass(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::SUCCESS => 'success',
            self::FAILED => 'danger',
            self::SKIPPED => 'secondary',
        };
    }

    /**
     * 获取状态对应的图标
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'fas fa-clock',
            self::SUCCESS => 'fas fa-check',
            self::FAILED => 'fas fa-times',
            self::SKIPPED => 'fas fa-forward',
        };
    }
} 