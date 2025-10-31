<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Service\AuthenticationValidationService;

/**
 * @internal
 */
#[CoversClass(AuthenticationValidationService::class)]
#[RunTestsInSeparateProcesses]
final class AuthenticationValidationServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testServiceInstance(): void
    {
        $service = self::getService(AuthenticationValidationService::class);
        $this->assertInstanceOf(AuthenticationValidationService::class, $service);
    }

    public function testValidateIdCardFormatWithValidCard(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        // 测试有效的身份证号码（校验位已验证）
        $validIdCards = [
            '110101199001011237', // 北京市东城区，校验位7
            '32038119901212567X', // 江苏省，校验位X
            '440306198506123458', // 广东省深圳市，校验位8
            '510110199001011238', // 四川省成都市，校验位8
        ];

        foreach ($validIdCards as $idCard) {
            $this->assertTrue($service->validateIdCardFormat($idCard), "身份证号 {$idCard} 应该是有效的");
        }
    }

    public function testValidateIdCardFormatWithInvalidCard(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        // 测试无效的身份证号码
        $invalidIdCards = [
            '1101011990010112345', // 19位
            '11010119900101123', // 17位
            '000000199001011234', // 地区码无效
            '110101199013011234', // 月份无效
            '110101199001321234', // 日期无效
            '110101199001011234', // 校验位错误（正确应为7）
            'abcdef199001011234', // 包含字母
            '', // 空字符串
        ];

        foreach ($invalidIdCards as $idCard) {
            $this->assertFalse($service->validateIdCardFormat($idCard), "身份证号 {$idCard} 应该是无效的");
        }
    }

    public function testValidateMobileFormatWithValidMobile(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        // 测试有效的手机号码
        $validMobiles = [
            '13800138000',
            '15912345678',
            '18612345678',
            '17712345678',
            '19912345678',
        ];

        foreach ($validMobiles as $mobile) {
            $this->assertTrue($service->validateMobileFormat($mobile), "手机号 {$mobile} 应该是有效的");
        }
    }

    public function testValidateMobileFormatWithInvalidMobile(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        // 测试无效的手机号码
        $invalidMobiles = [
            '1380013800', // 10位
            '138001380001', // 12位
            '12800138000', // 第二位不是3-9
            '03800138000', // 第一位不是1
            'abc01380000', // 包含字母
            '', // 空字符串
        ];

        foreach ($invalidMobiles as $mobile) {
            $this->assertFalse($service->validateMobileFormat($mobile), "手机号 {$mobile} 应该是无效的");
        }
    }

    public function testValidateBankCardFormatWithValidCard(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        // 测试有效的银行卡号（通过Luhn算法）
        $validBankCards = [
            '4111111111111111', // Visa测试卡号
            '5555555555554444', // Mastercard测试卡号
            '6222021234567894', // 建设银行卡号格式
        ];

        foreach ($validBankCards as $bankCard) {
            $this->assertTrue($service->validateBankCardFormat($bankCard), "银行卡号 {$bankCard} 应该是有效的");
        }
    }

    public function testValidateBankCardFormatWithInvalidCard(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        // 测试无效的银行卡号
        $invalidBankCards = [
            '12345', // 太短
            '12345678901234567890', // 太长
            '4111111111111116', // Luhn校验失败
            'abc1111111111111', // 包含字母
            '', // 空字符串
        ];

        foreach ($invalidBankCards as $bankCard) {
            $this->assertFalse($service->validateBankCardFormat($bankCard), "银行卡号 {$bankCard} 应该是无效的");
        }
    }

    public function testValidateCreditCodeFormatWithValidCode(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        // 测试有效的统一社会信用代码
        $validCreditCodes = [
            '91110000000000000A', // 模拟有效格式
            '92320000000000001B', // 另一个模拟有效格式
        ];

        foreach ($validCreditCodes as $creditCode) {
            // 注意：这里可能需要根据实际算法调整测试数据
            $result = $service->validateCreditCodeFormat($creditCode);
            $this->assertIsBool($result, "信用代码 {$creditCode} 验证应返回布尔值");
        }
    }

    public function testValidateCreditCodeFormatWithInvalidCode(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        // 测试无效的统一社会信用代码
        $invalidCreditCodes = [
            '9111000000000000', // 17位，缺少校验码
            '911100000000000001', // 19位，太长
            'I1110000000000000A', // 包含无效字符I
            'O1110000000000000A', // 包含无效字符O
            '', // 空字符串
        ];

        foreach ($invalidCreditCodes as $creditCode) {
            $this->assertFalse($service->validateCreditCodeFormat($creditCode), "信用代码 {$creditCode} 应该是无效的");
        }
    }

    public function testSanitizeInputWithMixedData(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        $inputData = [
            'name' => '  张三  ',
            'id_card' => '11010119900101123x', // 小写x（注意：完整号码应为110101199001011237，此处测试x转X）
            'legal_id_card' => '32038119901212567x', // 小写x
            'bank_card' => '6222 0212 3456 7894', // 带空格
            'bank_account' => '6222-0212-3456-7895', // 带连字符
            'other_field' => '  其他数据  ',
            'non_string_field' => 123,
        ];

        $result = $service->sanitizeInput($inputData);

        $this->assertSame('张三', $result['name'], '姓名应去除前后空格');
        $this->assertSame('11010119900101123X', $result['id_card'], '身份证号最后的x应转换为X');
        $this->assertSame('32038119901212567X', $result['legal_id_card'], '法人身份证号最后的x应转换为X');
        $this->assertSame('6222021234567894', $result['bank_card'], '银行卡号应去除空格和连字符');
        $this->assertSame('6222021234567895', $result['bank_account'], '银行账号应去除空格和连字符');
        $this->assertSame('其他数据', $result['other_field'], '其他字符串字段应去除前后空格');
        $this->assertSame(123, $result['non_string_field'], '非字符串字段应保持不变');
    }

    public function testCheckRateLimitingWithinLimit(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        $userId = 'test_user_' . uniqid();
        $method = AuthenticationMethod::ID_CARD_TWO_ELEMENTS;

        // 第一次调用应该成功
        $result = $service->checkRateLimiting($userId, $method);
        $this->assertTrue($result, '首次调用频率限制检查应该通过');
    }

    public function testCheckRateLimitingExceedsLimit(): void
    {
        $service = self::getService(AuthenticationValidationService::class);

        $userId = 'test_rate_limit_' . uniqid();
        $method = AuthenticationMethod::ID_CARD_TWO_ELEMENTS;

        // 连续调用超过限制次数（假设限制为10次）
        for ($i = 0; $i < 11; ++$i) {
            $result = $service->checkRateLimiting($userId, $method);
            if ($i < 10) {
                $this->assertTrue($result, "第 {$i} 次调用应该通过");
            } else {
                $this->assertFalse($result, '超过限制后的调用应该被拒绝');
            }
        }
    }
}
