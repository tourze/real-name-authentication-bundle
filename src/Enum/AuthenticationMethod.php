<?php

namespace Tourze\RealNameAuthenticationBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 认证方式枚举
 * 定义各种个人实名认证方式
 */
enum AuthenticationMethod: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    // 个人认证方式
    case ID_CARD_TWO_ELEMENTS = 'id_card_two_elements';
    case CARRIER_THREE_ELEMENTS = 'carrier_three_elements';
    case BANK_CARD_THREE_ELEMENTS = 'bank_card_three_elements';
    case BANK_CARD_FOUR_ELEMENTS = 'bank_card_four_elements';
    case LIVENESS_DETECTION = 'liveness_detection';

    public function getLabel(): string
    {
        return match ($this) {
            self::ID_CARD_TWO_ELEMENTS => '身份证二要素',
            self::CARRIER_THREE_ELEMENTS => '运营商三要素',
            self::BANK_CARD_THREE_ELEMENTS => '银行卡三要素',
            self::BANK_CARD_FOUR_ELEMENTS => '银行卡四要素',
            self::LIVENESS_DETECTION => '活体检测',
        };
    }

    /**
     * 获取认证所需字段
     */
    public function getRequiredFields(): array
    {
        return match ($this) {
            self::ID_CARD_TWO_ELEMENTS => ['name', 'id_card'],
            self::CARRIER_THREE_ELEMENTS => ['name', 'id_card', 'mobile'],
            self::BANK_CARD_THREE_ELEMENTS => ['name', 'id_card', 'bank_card'],
            self::BANK_CARD_FOUR_ELEMENTS => ['name', 'id_card', 'bank_card', 'mobile'],
            self::LIVENESS_DETECTION => ['image'],
        };
    }

    /**
     * 判断是否为个人认证方式
     */
    public function isPersonal(): bool
    {
        return in_array($this, [
            self::ID_CARD_TWO_ELEMENTS,
            self::CARRIER_THREE_ELEMENTS,
            self::BANK_CARD_THREE_ELEMENTS,
            self::BANK_CARD_FOUR_ELEMENTS,
            self::LIVENESS_DETECTION,
        ]);
    }
}
