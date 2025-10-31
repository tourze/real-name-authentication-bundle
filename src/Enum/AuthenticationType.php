<?php

namespace Tourze\RealNameAuthenticationBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 认证类型枚举
 * 定义个人实名认证类型
 */
enum AuthenticationType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PERSONAL = 'personal';

    public function getLabel(): string
    {
        return match ($this) {
            self::PERSONAL => '个人认证',
        };
    }
}
