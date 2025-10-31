<?php

namespace Tourze\RealNameAuthenticationBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 认证提供商类型枚举
 * 定义不同类型的认证服务提供商
 */
enum ProviderType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case GOVERNMENT = 'government';
    case BANK_UNION = 'bank_union';
    case CARRIER = 'carrier';
    case THIRD_PARTY = 'third_party';

    public function getLabel(): string
    {
        return match ($this) {
            self::GOVERNMENT => '政府部门',
            self::BANK_UNION => '银联',
            self::CARRIER => '运营商',
            self::THIRD_PARTY => '第三方',
        };
    }

    /**
     * 获取支持的认证方式
     * @return array<int, AuthenticationMethod>
     */
    public function getSupportedMethods(): array
    {
        return match ($this) {
            self::GOVERNMENT => [
                AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            ],
            self::BANK_UNION => [
                AuthenticationMethod::BANK_CARD_THREE_ELEMENTS,
                AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS,
            ],
            self::CARRIER => [
                AuthenticationMethod::CARRIER_THREE_ELEMENTS,
            ],
            self::THIRD_PARTY => [
                AuthenticationMethod::LIVENESS_DETECTION,
            ],
        };
    }
}
