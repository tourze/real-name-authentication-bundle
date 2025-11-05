<?php

namespace Tourze\RealNameAuthenticationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;

/**
 * 实名认证记录数据填充
 *
 * 创建各种状态和类型的认证记录，用于测试和演示
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class RealNameAuthenticationFixtures extends Fixture implements DependentFixtureInterface
{
    // 认证记录引用常量
    public const PERSONAL_APPROVED_AUTH_REFERENCE = 'personal-approved-auth';
    public const PERSONAL_PENDING_AUTH_REFERENCE = 'personal-pending-auth';
    public const PERSONAL_REJECTED_AUTH_REFERENCE = 'personal-rejected-auth';

    public function load(ObjectManager $manager): void
    {
        $governmentProvider = $this->getReference(AuthenticationProviderFixtures::GOVERNMENT_PROVIDER_REFERENCE, AuthenticationProvider::class);
        assert($governmentProvider instanceof AuthenticationProvider);

        $bankUnionProvider = $this->getReference(AuthenticationProviderFixtures::BANK_UNION_PROVIDER_REFERENCE, AuthenticationProvider::class);
        assert($bankUnionProvider instanceof AuthenticationProvider);

        $carrierProvider = $this->getReference(AuthenticationProviderFixtures::CARRIER_PROVIDER_REFERENCE, AuthenticationProvider::class);
        assert($carrierProvider instanceof AuthenticationProvider);

        $thirdPartyProvider = $this->getReference(AuthenticationProviderFixtures::THIRD_PARTY_PROVIDER_REFERENCE, AuthenticationProvider::class);
        assert($thirdPartyProvider instanceof AuthenticationProvider);

        // 创建测试用户（不依赖外部 Bundle 的 fixtures）
        $adminUser = $this->createFixtureUser('admin-test');
        $manager->persist($adminUser);

        $moderatorUser = $this->createFixtureUser('moderator-test');
        $manager->persist($moderatorUser);

        // 1. 个人认证 - 已通过（身份证二要素）
        $personalApproved = new RealNameAuthentication();
        $personalApproved->setUser($adminUser);
        $personalApproved->setType(AuthenticationType::PERSONAL);
        $personalApproved->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $personalApproved->setSubmittedData([
            'name' => '张三',
            'id_card' => '11010119900101100X',
        ]);
        $personalApproved->updateStatus(
            AuthenticationStatus::APPROVED,
            [
                'confidence' => 0.98,
                'match_score' => 95.6,
                'verification_time' => '2024-01-27 10:30:15',
            ],
            [
                'provider_name' => $governmentProvider->getName(),
                'request_id' => 'REQ_' . uniqid(),
                'response_code' => '0000',
                'response_message' => '认证成功',
                'processing_time' => 1250,
            ]
        );
        $personalApproved->setExpireTime(new \DateTimeImmutable('+1 year'));
        $manager->persist($personalApproved);
        $this->addReference(self::PERSONAL_APPROVED_AUTH_REFERENCE, $personalApproved);

        // 2. 个人认证 - 待审核（运营商三要素）
        $personalPending = new RealNameAuthentication();
        $personalPending->setUser($moderatorUser);
        $personalPending->setType(AuthenticationType::PERSONAL);
        $personalPending->setMethod(AuthenticationMethod::CARRIER_THREE_ELEMENTS);
        $personalPending->setSubmittedData([
            'name' => '李四',
            'id_card' => '110101199002021007',
            'mobile' => '13800138000',
        ]);
        $personalPending->updateStatus(AuthenticationStatus::PENDING);
        $manager->persist($personalPending);
        $this->addReference(self::PERSONAL_PENDING_AUTH_REFERENCE, $personalPending);

        // 3. 个人认证 - 已拒绝（银行卡三要素）
        $personalRejected = new RealNameAuthentication();
        $personalRejected->setUser($adminUser);
        $personalRejected->setType(AuthenticationType::PERSONAL);
        $personalRejected->setMethod(AuthenticationMethod::BANK_CARD_THREE_ELEMENTS);
        $personalRejected->setSubmittedData([
            'name' => '王五',
            'id_card' => '110101199003031004',
            'bank_card' => '6222021234567894',
        ]);
        $personalRejected->updateStatus(
            AuthenticationStatus::REJECTED,
            [
                'confidence' => 0.12,
                'error_details' => '姓名与银行卡信息不匹配',
            ],
            [
                'provider_name' => $bankUnionProvider->getName(),
                'request_id' => 'REQ_' . uniqid(),
                'response_code' => '1001',
                'response_message' => '姓名与银行卡信息不匹配',
                'processing_time' => 890,
            ],
            '提交的姓名与银行卡预留信息不一致，请检查后重新提交'
        );
        $manager->persist($personalRejected);
        $this->addReference(self::PERSONAL_REJECTED_AUTH_REFERENCE, $personalRejected);

        // 4. 个人认证 - 处理中（银行卡四要素）
        $personalProcessing = new RealNameAuthentication();
        $personalProcessing->setUser($moderatorUser);
        $personalProcessing->setType(AuthenticationType::PERSONAL);
        $personalProcessing->setMethod(AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS);
        $personalProcessing->setSubmittedData([
            'name' => '赵六',
            'id_card' => '51010119900404100X',
            'bank_card' => '6228481234567894',
            'mobile' => '13900139000',
        ]);
        $personalProcessing->updateStatus(AuthenticationStatus::PROCESSING);
        $manager->persist($personalProcessing);

        // 5. 个人认证 - 已过期
        $personalExpired = new RealNameAuthentication();
        $personalExpired->setUser($adminUser);
        $personalExpired->setType(AuthenticationType::PERSONAL);
        $personalExpired->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $personalExpired->setSubmittedData([
            'name' => '孙七',
            'id_card' => '33010119900505100X',
        ]);
        $personalExpired->updateStatus(
            AuthenticationStatus::APPROVED,
            ['confidence' => 0.96],
            ['provider_name' => $governmentProvider->getName()]
        );
        $personalExpired->setExpireTime(new \DateTimeImmutable('-1 month'));
        $manager->persist($personalExpired);

        // 6. 活体检测认证 - 已通过
        $livenessApproved = new RealNameAuthentication();
        $livenessApproved->setUser($moderatorUser);
        $livenessApproved->setType(AuthenticationType::PERSONAL);
        $livenessApproved->setMethod(AuthenticationMethod::LIVENESS_DETECTION);
        $livenessApproved->setSubmittedData([
            'image_count' => 3,
            'image_format' => 'jpg',
            'image_size' => '1024x768',
        ]);
        $livenessApproved->updateStatus(
            AuthenticationStatus::APPROVED,
            [
                'confidence' => 0.94,
                'liveness_score' => 89.5,
                'face_quality' => 'good',
            ],
            [
                'provider_name' => $thirdPartyProvider->getName(),
                'request_id' => 'FACE_' . uniqid(),
                'response_code' => '0000',
                'response_message' => '活体检测通过',
                'processing_time' => 3200,
            ]
        );
        $livenessApproved->setExpireTime(new \DateTimeImmutable('+1 year'));
        $manager->persist($livenessApproved);

        // 7. 多个用户的认证历史（同一用户多次认证）
        $user007 = $adminUser;
        $userMultipleAuth1 = new RealNameAuthentication();
        $userMultipleAuth1->setUser($user007);
        $userMultipleAuth1->setType(AuthenticationType::PERSONAL);
        $userMultipleAuth1->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $userMultipleAuth1->setSubmittedData([
            'name' => '周八',
            'id_card' => '37010119900606100X',
        ]);
        $userMultipleAuth1->updateStatus(
            AuthenticationStatus::REJECTED,
            null,
            null,
            '身份证号码格式错误'
        );
        $manager->persist($userMultipleAuth1);

        // 同一用户的第二次认证（成功）
        $userMultipleAuth2 = new RealNameAuthentication();
        $userMultipleAuth2->setUser($user007);
        $userMultipleAuth2->setType(AuthenticationType::PERSONAL);
        $userMultipleAuth2->setMethod(AuthenticationMethod::CARRIER_THREE_ELEMENTS);
        $userMultipleAuth2->setSubmittedData([
            'name' => '周八',
            'id_card' => '37010119900606100X',
            'mobile' => '13700137000',
        ]);
        $userMultipleAuth2->updateStatus(
            AuthenticationStatus::APPROVED,
            ['confidence' => 0.92],
            ['provider_name' => $carrierProvider->getName()]
        );
        $userMultipleAuth2->setExpireTime(new \DateTimeImmutable('+1 year'));
        $manager->persist($userMultipleAuth2);

        // 8. 批量测试数据（模拟大量用户）
        for ($i = 1; $i <= 20; ++$i) {
            // 循环使用 admin 和 moderator 用户
            $testUser = 0 === $i % 2 ? $adminUser : $moderatorUser;
            $methods = [
                AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
                AuthenticationMethod::CARRIER_THREE_ELEMENTS,
                AuthenticationMethod::BANK_CARD_THREE_ELEMENTS,
            ];
            $statuses = [
                AuthenticationStatus::APPROVED,
                AuthenticationStatus::PENDING,
                AuthenticationStatus::REJECTED,
                AuthenticationStatus::PROCESSING,
            ];

            $selectedMethod = $methods[array_rand($methods)];
            $selectedStatus = $statuses[array_rand($statuses)];

            $testAuth = new RealNameAuthentication();
            $testAuth->setUser($testUser);
            $testAuth->setType(AuthenticationType::PERSONAL);
            $testAuth->setMethod($selectedMethod);
            $testAuth->setSubmittedData([
                'name' => '测试用户' . $i,
                'id_card' => '11010119900101100X',
                'mobile' => '138' . sprintf('%08d', $i),
                'bank_card' => '6222021234567894',
            ]);

            if (AuthenticationStatus::APPROVED === $selectedStatus) {
                $testAuth->updateStatus(
                    $selectedStatus,
                    ['confidence' => 0.8 + (rand(0, 20) / 100)],
                    ['provider_name' => 'Test Provider']
                );
                $testAuth->setExpireTime(new \DateTimeImmutable('+1 year'));
            } else {
                $testAuth->updateStatus($selectedStatus);
            }

            $manager->persist($testAuth);
        }

        $manager->flush();
    }

    /**
     * 创建用于测试的轻量用户对象
     *
     * 使用本地 FixtureUser 而非具体的用户实现，避免跨 Bundle 依赖
     */
    private function createFixtureUser(string $userIdentifier): UserInterface
    {
        return new FixtureUser($userIdentifier);
    }

    /**
     * @return array<int, class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            AuthenticationProviderFixtures::class,
        ];
    }
}
