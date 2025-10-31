<?php

namespace Tourze\RealNameAuthenticationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;

/**
 * 认证结果数据填充
 *
 * 创建各种认证结果记录，用于测试和演示
 */
#[When(env: 'test')]
#[When(env: 'dev')]
class AuthenticationResultFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $personalApproved = $this->getReference(RealNameAuthenticationFixtures::PERSONAL_APPROVED_AUTH_REFERENCE, RealNameAuthentication::class);
        assert($personalApproved instanceof RealNameAuthentication);

        $personalRejected = $this->getReference(RealNameAuthenticationFixtures::PERSONAL_REJECTED_AUTH_REFERENCE, RealNameAuthentication::class);
        assert($personalRejected instanceof RealNameAuthentication);

        $governmentProvider = $this->getReference(AuthenticationProviderFixtures::GOVERNMENT_PROVIDER_REFERENCE, AuthenticationProvider::class);
        assert($governmentProvider instanceof AuthenticationProvider);

        $bankUnionProvider = $this->getReference(AuthenticationProviderFixtures::BANK_UNION_PROVIDER_REFERENCE, AuthenticationProvider::class);
        assert($bankUnionProvider instanceof AuthenticationProvider);

        // 1. 成功的认证结果
        $successResult = new AuthenticationResult();
        $successResult->setAuthentication($personalApproved);
        $successResult->setProvider($governmentProvider);
        $successResult->setRequestId('REQ_SUCCESS_' . uniqid());
        $successResult->setSuccess(true);
        $successResult->setConfidence(0.98);
        $successResult->setResponseData([
            'match_score' => 95.6,
            'verification_time' => '2024-01-27 10:30:15',
            'response_code' => '0000',
            'response_message' => '认证成功',
            'provider_reference' => 'GOV_REF_' . uniqid(),
        ]);
        $successResult->setProcessingTime(1250);
        $manager->persist($successResult);

        // 2. 失败的认证结果
        $failureResult = new AuthenticationResult();
        $failureResult->setAuthentication($personalRejected);
        $failureResult->setProvider($bankUnionProvider);
        $failureResult->setRequestId('REQ_FAILURE_' . uniqid());
        $failureResult->setSuccess(false);
        $failureResult->setConfidence(0.12);
        $failureResult->setResponseData([
            'error_details' => '姓名与银行卡信息不匹配',
            'response_code' => '1001',
            'response_message' => '姓名与银行卡信息不匹配',
            'provider_reference' => 'BANK_REF_' . uniqid(),
        ]);
        $failureResult->setErrorCode('1001');
        $failureResult->setErrorMessage('姓名与银行卡信息不匹配');
        $failureResult->setProcessingTime(890);
        $manager->persist($failureResult);

        // 3. 超时的认证结果
        $timeoutResult = new AuthenticationResult();
        $timeoutResult->setAuthentication($personalRejected);
        $timeoutResult->setProvider($bankUnionProvider);
        $timeoutResult->setRequestId('REQ_TIMEOUT_' . uniqid());
        $timeoutResult->setSuccess(false);
        $timeoutResult->setConfidence(0.0);
        $timeoutResult->setResponseData([
            'timeout_duration' => 30000,
            'retry_count' => 3,
            'last_error' => 'Connection timeout',
        ]);
        $timeoutResult->setErrorCode('TIMEOUT');
        $timeoutResult->setErrorMessage('请求超时，请稍后重试');
        $timeoutResult->setProcessingTime(30000);
        $timeoutResult->setValid(false);
        $manager->persist($timeoutResult);

        // 4. 高置信度认证结果
        $highConfidenceResult = new AuthenticationResult();
        $highConfidenceResult->setAuthentication($personalApproved);
        $highConfidenceResult->setProvider($governmentProvider);
        $highConfidenceResult->setRequestId('REQ_HIGH_CONF_' . uniqid());
        $highConfidenceResult->setSuccess(true);
        $highConfidenceResult->setConfidence(0.995);
        $highConfidenceResult->setResponseData([
            'match_score' => 99.5,
            'verification_method' => 'government_database',
            'verification_time' => '2024-01-27 11:15:30',
            'response_code' => '0000',
            'response_message' => '高置信度认证成功',
            'additional_checks' => [
                'photo_match' => true,
                'address_match' => true,
                'phone_match' => true,
            ],
        ]);
        $highConfidenceResult->setProcessingTime(2100);
        $manager->persist($highConfidenceResult);

        // 5. 批量创建测试结果
        for ($i = 1; $i <= 10; ++$i) {
            $testResult = new AuthenticationResult();
            $testResult->setAuthentication($personalApproved);
            $testResult->setProvider($governmentProvider);
            $testResult->setRequestId('REQ_TEST_' . $i . '_' . uniqid());
            $testResult->setSuccess(1 === rand(0, 1));
            $testResult->setConfidence(rand(50, 100) / 100);
            $testResult->setResponseData([
                'test_data' => true,
                'batch_id' => $i,
                'response_code' => 1 === rand(0, 1) ? '0000' : '1001',
                'response_message' => 1 === rand(0, 1) ? '测试成功' : '测试失败',
            ]);
            $testResult->setProcessingTime(rand(500, 3000));

            if (!$testResult->isSuccess()) {
                $testResult->setErrorCode('TEST_ERROR_' . $i);
                $testResult->setErrorMessage('测试错误消息 ' . $i);
            }

            $manager->persist($testResult);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AuthenticationProviderFixtures::class,
            RealNameAuthenticationFixtures::class,
        ];
    }
}
