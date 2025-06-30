<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;
use Tourze\RealNameAuthenticationBundle\Service\ManualReviewService;
use Tourze\RealNameAuthenticationBundle\Tests\Fixtures\TestUser;
use Tourze\RealNameAuthenticationBundle\Tests\Integration\IntegrationTestCase;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Service\ManualReviewService
 */
class ManualReviewServiceTest extends IntegrationTestCase
{
    private ManualReviewService $service;
    private EntityManagerInterface $entityManager;
    private RealNameAuthenticationRepository $realNameAuthenticationRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = $this->getService(ManualReviewService::class);
        $this->entityManager = $this->getService('doctrine.orm.entity_manager');
        $this->realNameAuthenticationRepository = $this->getService(RealNameAuthenticationRepository::class);
        
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // 创建认证提供商
        $provider = new AuthenticationProvider();
        $provider->setName('测试提供商');
        $provider->setCode('test_provider');
        $provider->setType(ProviderType::GOVERNMENT);
        $provider->setActive(true);
        $provider->setApiEndpoint('https://api.test.com');
        $provider->setConfig(['api_key' => 'test_key']);
        $this->entityManager->persist($provider);
        
        // 创建待审核的认证记录
        for ($i = 1; $i <= 5; $i++) {
            $user = new TestUser("review_user_{$i}");
            $this->entityManager->persist($user);
            
            $authentication = new RealNameAuthentication();
            $authentication->setUser($user);
            $authentication->setType(AuthenticationType::PERSONAL);
            $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
            $authentication->setStatus(AuthenticationStatus::PENDING);
            $authentication->setSubmittedData([
                'real_name' => "待审核用户{$i}",
                'id_card_number' => "11010119900101100{$i}",
            ]);
            
            $this->entityManager->persist($authentication);
        }
        
        // 创建已审核的记录
        $reviewedUser = new TestUser('reviewed_user');
        $this->entityManager->persist($reviewedUser);
        
        $reviewedAuth = new RealNameAuthentication();
        $reviewedAuth->setUser($reviewedUser);
        $reviewedAuth->setType(AuthenticationType::PERSONAL);
        $reviewedAuth->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $reviewedAuth->setStatus(AuthenticationStatus::APPROVED);
        $reviewedAuth->setSubmittedData([
            'real_name' => '已审核用户',
            'id_card_number' => '110101199001012000',
        ]);
        
        $this->entityManager->persist($reviewedAuth);
        $this->entityManager->flush();
    }

    public function testGetPendingAuthentications(): void
    {
        $pendingAuths = $this->service->getPendingAuthentications();
        
        // 应该有5条待审核的记录
        $this->assertCount(5, $pendingAuths);
        
        foreach ($pendingAuths as $auth) {
            $this->assertEquals(AuthenticationStatus::PENDING, $auth->getStatus());
            $this->assertTrue($auth->isValid());
        }
    }

    public function testApproveAuthentication(): void
    {
        $authentication = $this->realNameAuthenticationRepository
            ->findOneBy(['status' => AuthenticationStatus::PENDING]);
        
        $this->assertNotNull($authentication);
        $this->assertEquals(AuthenticationStatus::PENDING, $authentication->getStatus());
        
        // 执行审核通过
        $result = $this->service->approveAuthentication($authentication->getId(), '信息核实无误');
        
        // 验证状态更新
        $this->assertEquals(AuthenticationStatus::APPROVED, $result->getStatus());
        $this->assertNotNull($result->getExpireTime());
        $this->assertGreaterThan(new \DateTimeImmutable(), $result->getExpireTime());
    }

    public function testRejectAuthentication(): void
    {
        $authentication = $this->realNameAuthenticationRepository
            ->findOneBy(['status' => AuthenticationStatus::PENDING]);
        
        $this->assertNotNull($authentication);
        $this->assertEquals(AuthenticationStatus::PENDING, $authentication->getStatus());
        
        // 执行审核拒绝
        $result = $this->service->rejectAuthentication(
            $authentication->getId(),
            '身份证号码无效',
            '经核实，身份证号码不存在'
        );
        
        // 验证状态更新
        $this->assertEquals(AuthenticationStatus::REJECTED, $result->getStatus());
        $this->assertEquals('身份证号码无效', $result->getReason());
    }

    public function testBatchReview(): void
    {
        // 获取待审核记录
        $pendingAuths = $this->service->getPendingAuthentications(3);
        $authIds = array_map(fn($auth) => $auth->getId(), $pendingAuths);
        
        // 批量审核通过
        $results = $this->service->batchReview($authIds, 'approve', null, '批量审核通过');
        
        $this->assertCount(3, $results);
        
        // 验证所有记录都已通过
        foreach ($results as $authId => $result) {
            if ($result instanceof RealNameAuthentication) {
                $this->assertEquals(AuthenticationStatus::APPROVED, $result->getStatus());
            } else {
                $this->fail("审核失败: {$authId}");
            }
        }
    }

    public function testBatchRejectWithoutReason(): void
    {
        $pendingAuths = $this->service->getPendingAuthentications(2);
        $authIds = array_map(fn($auth) => $auth->getId(), $pendingAuths);
        
        // 预期抛出异常
        $this->expectException(\Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('批量拒绝必须提供拒绝原因');
        
        // 批量拒绝但不提供原因
        $this->service->batchReview($authIds, 'reject');
    }

    public function testBatchRejectWithReason(): void
    {
        $pendingAuths = $this->service->getPendingAuthentications(2);
        $authIds = array_map(fn($auth) => $auth->getId(), $pendingAuths);
        
        // 批量审核拒绝
        $results = $this->service->batchReview($authIds, 'reject', '信息不符', '批量拒绝审核');
        
        $this->assertCount(2, $results);
        
        // 验证所有记录都已拒绝
        foreach ($results as $authId => $result) {
            if ($result instanceof RealNameAuthentication) {
                $this->assertEquals(AuthenticationStatus::REJECTED, $result->getStatus());
                $this->assertEquals('信息不符', $result->getReason());
            } else {
                $this->fail("审核失败: {$authId}");
            }
        }
    }

    public function testGetReviewStatistics(): void
    {
        // 审核一些记录
        $pendingAuths = $this->service->getPendingAuthentications(4);
        
        // 2个通过，2个拒绝
        for ($i = 0; $i < 4; $i++) {
            if ($i < 2) {
                $this->service->approveAuthentication($pendingAuths[$i]->getId(), '审核通过');
            } else {
                $this->service->rejectAuthentication($pendingAuths[$i]->getId(), '信息不符', '审核拒绝');
            }
        }
        
        // 获取统计
        $startDate = new \DateTimeImmutable('-1 day');
        $endDate = new \DateTimeImmutable('+1 day');
        $stats = $this->service->getReviewStatistics($startDate, $endDate);
        
        $this->assertArrayHasKey('approved', $stats);
        $this->assertArrayHasKey('rejected', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('approval_rate', $stats);
        
        // 验证统计数据（包括之前创建的已审核记录）
        $this->assertGreaterThanOrEqual(3, $stats['approved']); // 2个新审核通过 + 1个已存在的
        $this->assertEquals(2, $stats['rejected']);
        $this->assertEquals(1, $stats['pending']); // 还剩1个待审核
        $this->assertGreaterThan(0, $stats['approval_rate']);
    }

    public function testCannotReviewAlreadyApproved(): void
    {
        // 获取已审核的记录
        $approvedAuth = $this->realNameAuthenticationRepository
            ->findOneBy(['status' => AuthenticationStatus::APPROVED]);
        
        $this->assertNotNull($approvedAuth);
        
        // 预期抛出异常
        $this->expectException(\Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException::class);
        $this->expectExceptionMessage('该认证记录当前状态不允许审核');
        
        // 尝试再次审核
        $this->service->approveAuthentication($approvedAuth->getId());
    }

    public function testInvalidReviewAction(): void
    {
        $pendingAuth = $this->service->getPendingAuthentications(1)[0];
        
        // 预期抛出异常
        $this->expectException(\Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('无效的审核操作');
        
        // 使用无效的操作
        $this->service->batchReview([$pendingAuth->getId()], 'invalid_action');
    }

    public function testRejectWithoutReason(): void
    {
        $pendingAuth = $this->service->getPendingAuthentications(1)[0];
        
        // 预期抛出异常
        $this->expectException(\Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('拒绝认证必须提供拒绝原因');
        
        // 拒绝但不提供原因
        $this->service->rejectAuthentication($pendingAuth->getId(), '');
    }
}