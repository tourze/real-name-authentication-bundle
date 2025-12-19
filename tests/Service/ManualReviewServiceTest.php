<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;
use Tourze\RealNameAuthenticationBundle\Service\ManualReviewService;

/**
 * @internal
 */
#[CoversClass(ManualReviewService::class)]
#[RunTestsInSeparateProcesses]
final class ManualReviewServiceTest extends AbstractIntegrationTestCase
{
    private ManualReviewService $service;

    protected function onSetUp(): void
    {
        // 从容器获取服务实例（集成测试模式，使用真实服务）
        $this->service = self::getService(ManualReviewService::class);
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(ManualReviewService::class, $this->service);
    }

    public function testApproveAuthentication(): void
    {
        $reviewNote = '证件信息核实无误';

        // 创建真实的认证记录
        $authentication = $this->createRealAuthentication(AuthenticationStatus::PENDING);

        // 执行审核操作
        $result = $this->service->approveAuthentication($authentication->getId(), $reviewNote);

        // 验证结果
        $this->assertSame($authentication->getId(), $result->getId());
        $this->assertSame(AuthenticationStatus::APPROVED, $result->getStatus());
        $this->assertNotNull($result->getExpireTime());

        // 验证认证结果包含审核信息
        $verificationResult = $result->getVerificationResult();
        $this->assertNotNull($verificationResult);
        /** @var array<string, mixed> $verificationResult */
        $this->assertTrue($verificationResult['manual_review'] ?? false);
        $this->assertNotEmpty($verificationResult['reviewer'] ?? '');
        $this->assertSame($reviewNote, $verificationResult['review_note'] ?? '');
        $this->assertSame(1.0, $verificationResult['confidence'] ?? 0);
    }

    public function testApproveAuthenticationWithNonExistentRecord(): void
    {
        $authId = 'non-existent-id-' . uniqid();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('认证记录不存在');

        $this->service->approveAuthentication($authId);
    }

    public function testApproveAuthenticationWithInvalidStatus(): void
    {
        // 创建已批准的认证记录
        $authentication = $this->createRealAuthentication(AuthenticationStatus::APPROVED);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('该认证记录当前状态不允许审核');

        $this->service->approveAuthentication($authentication->getId());
    }

    public function testRejectAuthentication(): void
    {
        $reason = '身份证信息与公安部数据不符';
        $reviewNote = '经核实用户提供的身份证信息有误';

        // 创建真实的认证记录
        $authentication = $this->createRealAuthentication(AuthenticationStatus::PENDING);

        // 执行拒绝操作
        $result = $this->service->rejectAuthentication($authentication->getId(), $reason, $reviewNote);

        // 验证结果
        $this->assertSame(AuthenticationStatus::REJECTED, $result->getStatus());

        // 验证认证结果包含拒绝信息
        $verificationResult = $result->getVerificationResult();
        $this->assertNotNull($verificationResult);
        /** @var array<string, mixed> $verificationResult */
        $this->assertTrue($verificationResult['manual_review'] ?? false);
        $this->assertNotEmpty($verificationResult['reviewer'] ?? '');
        $this->assertSame($reviewNote, $verificationResult['review_note'] ?? '');
        $this->assertSame(0.0, $verificationResult['confidence'] ?? 1);
    }

    public function testRejectAuthenticationWithoutReason(): void
    {
        $authId = 'test-auth-id';
        $reason = '';

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('拒绝认证必须提供拒绝原因');

        $this->service->rejectAuthentication($authId, $reason);
    }

    public function testBatchReview(): void
    {
        // 创建多个真实的认证记录
        $authentication1 = $this->createRealAuthentication(AuthenticationStatus::PENDING);
        $authentication2 = $this->createRealAuthentication(AuthenticationStatus::PENDING);
        $authentication3 = $this->createRealAuthentication(AuthenticationStatus::PENDING);

        $authIds = [
            $authentication1->getId(),
            $authentication2->getId(),
            $authentication3->getId(),
        ];
        $action = 'approve';
        $reviewNote = '批量审核通过';

        // 执行批量审核
        $results = $this->service->batchReview($authIds, $action, null, $reviewNote);

        // 验证结果
        $this->assertCount(3, $results);

        foreach ($authIds as $authId) {
            $this->assertArrayHasKey($authId, $results);
            /** @var array<string, RealNameAuthentication> $results */
            $this->assertInstanceOf(RealNameAuthentication::class, $results[$authId]);
            $this->assertSame(AuthenticationStatus::APPROVED, $results[$authId]->getStatus());
        }
    }

    public function testBatchReviewWithReject(): void
    {
        // 创建两个真实的认证记录
        $authentication1 = $this->createRealAuthentication(AuthenticationStatus::PENDING);
        $authentication2 = $this->createRealAuthentication(AuthenticationStatus::PENDING);

        $authIds = [
            $authentication1->getId(),
            $authentication2->getId(),
        ];
        $action = 'reject';
        $reason = '批量拒绝原因';
        $reviewNote = '批量审核拒绝';

        // 执行批量审核
        $results = $this->service->batchReview($authIds, $action, $reason, $reviewNote);

        // 验证结果
        $this->assertCount(2, $results);

        foreach ($authIds as $authId) {
            $this->assertArrayHasKey($authId, $results);
            /** @var array<string, RealNameAuthentication> $results */
            $this->assertInstanceOf(RealNameAuthentication::class, $results[$authId]);
            $this->assertSame(AuthenticationStatus::REJECTED, $results[$authId]->getStatus());
        }
    }

    public function testBatchReviewWithInvalidAction(): void
    {
        $authIds = ['auth-1'];
        $action = 'invalid_action';

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('无效的审核操作');

        $this->service->batchReview($authIds, $action);
    }

    public function testBatchReviewRejectWithoutReason(): void
    {
        $authIds = ['auth-1'];
        $action = 'reject';
        $reason = null;

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('批量拒绝必须提供拒绝原因');

        $this->service->batchReview($authIds, $action, $reason);
    }

    public function testBatchReviewWithPartialFailure(): void
    {
        // 创建一个真实的认证记录
        $authentication1 = $this->createRealAuthentication(AuthenticationStatus::PENDING);

        $authIds = [
            $authentication1->getId(),
            'non-existent-id-' . uniqid(), // 不存在的ID
        ];
        $action = 'approve';

        // 执行批量审核
        $results = $this->service->batchReview($authIds, $action);

        // 验证结果
        $this->assertCount(2, $results);
        // 第一个成功
        $this->assertInstanceOf(RealNameAuthentication::class, $results[$authIds[0]]);
        // 第二个失败
        $this->assertIsArray($results[$authIds[1]]);
        $this->assertArrayHasKey('error', $results[$authIds[1]]);
        $errorResult = $results[$authIds[1]];
        $this->assertIsArray($errorResult);
        /** @var array{error: string} $errorResult */
        $this->assertStringContainsString('认证记录不存在', $errorResult['error']);
    }

    /**
     * 创建真实的认证实体对象用于测试
     */
    private function createRealAuthentication(
        AuthenticationStatus $status,
    ): RealNameAuthentication {
        $entityManager = self::getEntityManager();

        // 创建真实的用户对象
        $user = $this->createNormalUser('test_user_' . uniqid());

        // 创建认证实体对象
        $authentication = new RealNameAuthentication();

        // 设置必要的属性
        $authentication->setUser($user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setSubmittedData([
            'name' => '张三',
            'id_card' => '110101199001011234',
        ]);

        // 使用反射设置状态
        $reflection = new \ReflectionClass($authentication);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $statusProperty->setValue($authentication, $status);

        // 持久化到数据库
        $entityManager->persist($authentication);
        $entityManager->flush();

        return $authentication;
    }
}
