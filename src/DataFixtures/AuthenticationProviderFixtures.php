<?php

namespace Tourze\RealNameAuthenticationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;

/**
 * 认证提供商数据填充
 *
 * 创建各种类型的认证服务提供商，用于测试和演示
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class AuthenticationProviderFixtures extends Fixture
{
    // 提供商引用常量
    public const GOVERNMENT_PROVIDER_REFERENCE = 'government-provider';
    public const BANK_UNION_PROVIDER_REFERENCE = 'bank-union-provider';
    public const CARRIER_PROVIDER_REFERENCE = 'carrier-provider';
    public const THIRD_PARTY_PROVIDER_REFERENCE = 'third-party-provider';
    public const BACKUP_PROVIDER_REFERENCE = 'backup-provider';

    public function load(ObjectManager $manager): void
    {
        // 1. 政府部门认证提供商
        $governmentProvider = new AuthenticationProvider();
        $governmentProvider->setName('公安部身份认证中心');
        $governmentProvider->setCode('gov_police_auth');
        $governmentProvider->setType(ProviderType::GOVERNMENT);
        $governmentProvider->setSupportedMethods([
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value,
        ]);
        $governmentProvider->setApiEndpoint('https://api.police.gov.cn/auth');
        $governmentProvider->setConfig([
            'app_id' => 'GOV_APP_12345',
            'app_secret' => 'GOV_SECRET_ABCDEF',
            'timeout' => 10,
            'retry_count' => 3,
            'sandbox_mode' => true,
        ]);
        $governmentProvider->setPriority(100);
        $manager->persist($governmentProvider);
        $this->addReference(self::GOVERNMENT_PROVIDER_REFERENCE, $governmentProvider);

        // 2. 银联认证提供商
        $bankUnionProvider = new AuthenticationProvider();
        $bankUnionProvider->setName('中国银联实名认证');
        $bankUnionProvider->setCode('unionpay_auth');
        $bankUnionProvider->setType(ProviderType::BANK_UNION);
        $bankUnionProvider->setSupportedMethods([
            AuthenticationMethod::BANK_CARD_THREE_ELEMENTS->value,
            AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS->value,
        ]);
        $bankUnionProvider->setApiEndpoint('https://api.unionpay.com/auth');
        $bankUnionProvider->setConfig([
            'merchant_id' => 'UNIONPAY_MERCHANT_001',
            'certificate_path' => '/path/to/unionpay.cer',
            'private_key_path' => '/path/to/private.key',
            'version' => '1.0.0',
            'charset' => 'UTF-8',
            'sign_method' => 'RSA',
            'sandbox_mode' => true,
        ]);
        $bankUnionProvider->setPriority(95);
        $manager->persist($bankUnionProvider);
        $this->addReference(self::BANK_UNION_PROVIDER_REFERENCE, $bankUnionProvider);

        // 3. 运营商认证提供商
        $carrierProvider = new AuthenticationProvider();
        $carrierProvider->setName('中国移动实名认证');
        $carrierProvider->setCode('cmcc_auth');
        $carrierProvider->setType(ProviderType::CARRIER);
        $carrierProvider->setSupportedMethods([
            AuthenticationMethod::CARRIER_THREE_ELEMENTS->value,
        ]);
        $carrierProvider->setApiEndpoint('https://api.10086.cn/auth');
        $carrierProvider->setConfig([
            'app_key' => 'CMCC_APP_KEY_12345',
            'app_secret' => 'CMCC_SECRET_ABCDEF',
            'version' => '2.0',
            'format' => 'json',
            'sign_method' => 'HMAC-SHA256',
            'sandbox_mode' => true,
        ]);
        $carrierProvider->setPriority(90);
        $manager->persist($carrierProvider);
        $this->addReference(self::CARRIER_PROVIDER_REFERENCE, $carrierProvider);

        // 4. 第三方AI认证提供商
        $thirdPartyProvider = new AuthenticationProvider();
        $thirdPartyProvider->setName('阿里云人脸识别');
        $thirdPartyProvider->setCode('aliyun_face_auth');
        $thirdPartyProvider->setType(ProviderType::THIRD_PARTY);
        $thirdPartyProvider->setSupportedMethods([
            AuthenticationMethod::LIVENESS_DETECTION->value,
        ]);
        $thirdPartyProvider->setApiEndpoint('https://facebody.cn-shanghai.aliyuncs.com');
        $thirdPartyProvider->setConfig([
            'access_key_id' => 'ALI_ACCESS_KEY_ID',
            'access_key_secret' => 'ALI_ACCESS_KEY_SECRET',
            'region_id' => 'cn-shanghai',
            'version' => '2019-12-30',
            'confidence_threshold' => 0.8,
            'sandbox_mode' => true,
        ]);
        $thirdPartyProvider->setPriority(85);
        $manager->persist($thirdPartyProvider);
        $this->addReference(self::THIRD_PARTY_PROVIDER_REFERENCE, $thirdPartyProvider);

        // 5. 备用综合认证提供商
        $backupProvider = new AuthenticationProvider();
        $backupProvider->setName('腾讯云综合认证');
        $backupProvider->setCode('tencent_cloud_auth');
        $backupProvider->setType(ProviderType::THIRD_PARTY);
        $backupProvider->setSupportedMethods([
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value,
            AuthenticationMethod::CARRIER_THREE_ELEMENTS->value,
            AuthenticationMethod::BANK_CARD_THREE_ELEMENTS->value,
            AuthenticationMethod::LIVENESS_DETECTION->value,
        ]);
        $backupProvider->setApiEndpoint('https://faceid.tencentcloudapi.com');
        $backupProvider->setConfig([
            'secret_id' => 'TENCENT_SECRET_ID',
            'secret_key' => 'TENCENT_SECRET_KEY',
            'region' => 'ap-beijing',
            'version' => '2018-03-01',
            'endpoint' => 'faceid.tencentcloudapi.com',
            'sandbox_mode' => true,
        ]);
        $backupProvider->setPriority(80);
        $manager->persist($backupProvider);
        $this->addReference(self::BACKUP_PROVIDER_REFERENCE, $backupProvider);

        // 6. 创建一些禁用的提供商（用于测试）
        $disabledProvider = new AuthenticationProvider();
        $disabledProvider->setName('测试禁用提供商');
        $disabledProvider->setCode('disabled_test_provider');
        $disabledProvider->setType(ProviderType::THIRD_PARTY);
        $disabledProvider->setSupportedMethods([
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value,
        ]);
        $disabledProvider->setApiEndpoint('https://images.unsplash.com');
        $disabledProvider->setConfig([
            'test_mode' => true,
        ]);
        $disabledProvider->setPriority(0);
        $disabledProvider->setActive(false);
        $manager->persist($disabledProvider);

        // 7. 银行直连认证提供商
        $bankDirectProvider = new AuthenticationProvider();
        $bankDirectProvider->setName('工商银行直连认证');
        $bankDirectProvider->setCode('icbc_direct_auth');
        $bankDirectProvider->setType(ProviderType::BANK_UNION);
        $bankDirectProvider->setSupportedMethods([
            AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS->value,
        ]);
        $bankDirectProvider->setApiEndpoint('https://api.icbc.com.cn/auth');
        $bankDirectProvider->setConfig([
            'bank_code' => 'ICBC',
            'merchant_no' => 'ICBC_MERCHANT_001',
            'cert_file' => '/path/to/icbc.cer',
            'key_file' => '/path/to/icbc.key',
            'encryption' => 'RSA2048',
            'sandbox_mode' => true,
        ]);
        $bankDirectProvider->setPriority(88);
        $manager->persist($bankDirectProvider);

        // 8. 联通认证提供商
        $unicomProvider = new AuthenticationProvider();
        $unicomProvider->setName('中国联通实名认证');
        $unicomProvider->setCode('unicom_auth');
        $unicomProvider->setType(ProviderType::CARRIER);
        $unicomProvider->setSupportedMethods([
            AuthenticationMethod::CARRIER_THREE_ELEMENTS->value,
        ]);
        $unicomProvider->setApiEndpoint('https://api.10010.com/auth');
        $unicomProvider->setConfig([
            'partner_id' => 'UNICOM_PARTNER_001',
            'partner_key' => 'UNICOM_KEY_ABCDEF',
            'version' => '1.0',
            'charset' => 'UTF-8',
            'sign_type' => 'MD5',
            'sandbox_mode' => true,
        ]);
        $unicomProvider->setPriority(88);
        $manager->persist($unicomProvider);

        $manager->flush();
    }
}
