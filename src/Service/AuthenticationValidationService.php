<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;

/**
 * 认证验证服务
 * 
 * 负责验证各种认证数据的格式和合法性
 */
class AuthenticationValidationService
{
    private const RATE_LIMIT_PREFIX = 'auth_rate_limit_';
    private const RATE_LIMIT_WINDOW = 3600; // 1小时
    private const MAX_ATTEMPTS_PER_HOUR = 10; // 每小时最多尝试次数

    public function __construct(
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * 验证身份证号码格式
     */
    public function validateIdCardFormat(string $idCard): bool
    {
        // 18位身份证号码正则表达式
        $pattern = '/^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/';
        
        if (!preg_match($pattern, $idCard)) {
            return false;
        }

        // 验证身份证校验位
        return $this->validateIdCardChecksum($idCard);
    }

    /**
     * 验证手机号码格式
     */
    public function validateMobileFormat(string $mobile): bool
    {
        // 中国大陆手机号码正则表达式
        $pattern = '/^1[3-9]\d{9}$/';
        return preg_match($pattern, $mobile) === 1;
    }

    /**
     * 验证银行卡号格式
     */
    public function validateBankCardFormat(string $bankCard): bool
    {
        // 银行卡号长度一般为15-19位
        if (strlen($bankCard) < 15 || strlen($bankCard) > 19) {
            return false;
        }

        // 只允许数字
        if (!ctype_digit($bankCard)) {
            return false;
        }

        // 使用Luhn算法验证银行卡号
        return $this->validateLuhnChecksum($bankCard);
    }

    /**
     * 验证统一社会信用代码格式
     */
    public function validateCreditCodeFormat(string $creditCode): bool
    {
        // 统一社会信用代码为18位，格式为：登记管理部门代码(1位) + 机构类别代码(1位) + 登记管理机关行政区划码(6位) + 主体标识码(9位) + 校验码(1位)
        $pattern = '/^[0-9A-HJ-NPQRTUWXY]{2}\d{6}[0-9A-HJ-NPQRTUWXY]{10}$/';
        
        if (!preg_match($pattern, $creditCode)) {
            return false;
        }

        // 验证校验码
        return $this->validateCreditCodeChecksum($creditCode);
    }

    /**
     * 清理输入数据
     */
    public function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // 移除首尾空格
                $value = trim($value);
                
                // 对于身份证号，转换最后一位x为X
                if (in_array($key, ['id_card', 'legal_id_card']) && strtolower(substr($value, -1)) === 'x') {
                    $value = substr($value, 0, -1) . 'X';
                }
                
                // 移除银行卡号中的空格和分隔符
                if (in_array($key, ['bank_card', 'bank_account'])) {
                    $value = preg_replace('/[\s\-]/', '', $value);
                }
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }

    /**
     * 检查访问频率限制
     */
    public function checkRateLimiting(string $userId, AuthenticationMethod $method): bool
    {
        $key = self::RATE_LIMIT_PREFIX . $userId . '_' . $method->value;
        
        $attempts = $this->cache->get($key, function (ItemInterface $item) {
            $item->expiresAfter(self::RATE_LIMIT_WINDOW);
            return 0;
        });
        
        if ($attempts >= self::MAX_ATTEMPTS_PER_HOUR) {
            return false;
        }
        
        // 增加尝试次数
        $this->cache->delete($key);
        $this->cache->get($key, function (ItemInterface $item) use ($attempts) {
            $item->expiresAfter(self::RATE_LIMIT_WINDOW);
            return $attempts + 1;
        });
        
        return true;
    }

    /**
     * 验证身份证校验位
     */
    private function validateIdCardChecksum(string $idCard): bool
    {
        $weights = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $checksums = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += intval($idCard[$i]) * $weights[$i];
        }
        
        $expectedChecksum = $checksums[$sum % 11];
        $actualChecksum = strtoupper($idCard[17]);
        
        return $expectedChecksum === $actualChecksum;
    }

    /**
     * 使用Luhn算法验证银行卡号
     */
    private function validateLuhnChecksum(string $number): bool
    {
        $sum = 0;
        $numDigits = strlen($number);
        $oddEven = $numDigits & 1;

        for ($count = 0; $count < $numDigits; $count++) {
            $digit = intval($number[$count]);

            if ((($count & 1) ^ $oddEven) === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return ($sum % 10) === 0;
    }

    /**
     * 验证统一社会信用代码校验码
     */
    private function validateCreditCodeChecksum(string $creditCode): bool
    {
        $chars = '0123456789ABCDEFGHJKLMNPQRTUWXY';
        $weights = [1, 3, 9, 27, 19, 26, 16, 17, 20, 29, 25, 13, 8, 24, 10, 30, 28];
        
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $pos = strpos($chars, $creditCode[$i]);
            if ($pos === false) {
                return false;
            }
            $sum += $pos * $weights[$i];
        }
        
        $remainder = $sum % 31;
        $expectedChar = $chars[31 - $remainder];
        
        return $expectedChar === $creditCode[17];
    }
} 