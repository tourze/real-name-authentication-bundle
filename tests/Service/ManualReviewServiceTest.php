<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;
use Tourze\RealNameAuthenticationBundle\Service\ManualReviewService;

/**
 * @internal
 */
#[CoversClass(ManualReviewService::class)]
#[RunTestsInSeparateProcesses]
final class ManualReviewServiceTest extends AbstractIntegrationTestCase
{
    private MockObject&RealNameAuthenticationRepository $authRepository;

    private MockObject&Security $security;

    private MockObject&LoggerInterface $logger;

    private ManualReviewService $service;

    protected function onSetUp(): void
    {
        // 创建所有需要的Mock对象
        $this->authRepository = $this->createMock(RealNameAuthenticationRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 将Mock服务注入容器，只注入未被初始化的服务
        self::getContainer()->set(RealNameAuthenticationRepository::class, $this->authRepository);

        // Security 服务可能已经初始化，尝试使用别名
        if (!self::getContainer()->has(Security::class)) {
            self::getContainer()->set(Security::class, $this->security);
        }

        // Logger 服务可能已经初始化，尝试使用别名
        if (!self::getContainer()->has(LoggerInterface::class)) {
            self::getContainer()->set(LoggerInterface::class, $this->logger);
        }

        // 从容器获取服务实例
        $this->service = self::getService(ManualReviewService::class);
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(ManualReviewService::class, $this->service);
    }

    public function testApproveAuthentication(): void
    {
        $authId = 'test-auth-id';
        $reviewNote = '证件信息核实无误';
        $reviewer = 'admin@example.com';

        // 创建待审核的认证记录
        $authentication = $this->createMockAuthentication($authId, AuthenticationStatus::PENDING);

        // Mock repository查找
        $this->authRepository->expects($this->once())
            ->method('find')
            ->with($authId)
            ->willReturn($authentication)
        ;

        // Mock当前用户 - 只有在我们的Mock被使用时才设置期望
        $user = $this->createMockUser($reviewer);
        if ($this->isUsingMockSecurity()) {
            $this->security->expects($this->once())
                ->method('getUser')
                ->willReturn($user)
            ;
        }

        // EntityManager 是真实的，不需要 Mock

        // Mock日志记录 - 只有在我们的Mock被使用时才设置期望
        if ($this->isUsingMockLogger()) {
            $this->logger->expects($this->once())
                ->method('info')
                ->with('人工审核操作', self::callback(function ($context) use ($authId, $reviewer): bool {
                    /** @var array<string, mixed> $context */
                    return $context['auth_id'] === $authId
                        && 'approve' === $context['action']
                        && $context['reviewer'] === $reviewer;
                }))
            ;
        }

        $result = $this->service->approveAuthentication($authId, $reviewNote);

        $this->assertSame($authentication, $result);
        $this->assertSame(AuthenticationStatus::APPROVED, $authentication->getStatus());
        $this->assertNotNull($authentication->getExpireTime());

        // 验证认证结果包含审核信息
        $verificationResult = $authentication->getVerificationResult();
        $this->assertNotNull($verificationResult);
        /** @var array<string, mixed> $verificationResult */
        $this->assertTrue($verificationResult['manual_review'] ?? false);

        // 如果使用真实的Security服务，审核者可能不是我们期望的用户
        if ($this->isUsingMockSecurity()) {
            // 使用Mock时，应该返回我们期望的用户
            $this->assertSame($reviewer, $verificationResult['reviewer'] ?? '');
        } else {
            // 使用真实Security服务时，至少要有一个审核者
            $this->assertNotEmpty($verificationResult['reviewer'] ?? '');
        }

        $this->assertSame($reviewNote, $verificationResult['review_note'] ?? '');
        $this->assertSame(1.0, $verificationResult['confidence'] ?? 0);
    }

    public function testApproveAuthenticationWithNonExistentRecord(): void
    {
        $authId = 'non-existent-id';

        $this->authRepository->expects($this->once())
            ->method('find')
            ->with($authId)
            ->willReturn(null)
        ;

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('认证记录不存在');

        $this->service->approveAuthentication($authId);
    }

    public function testApproveAuthenticationWithInvalidStatus(): void
    {
        $authId = 'test-auth-id';
        $authentication = $this->createMockAuthentication($authId, AuthenticationStatus::APPROVED);

        $this->authRepository->expects($this->once())
            ->method('find')
            ->with($authId)
            ->willReturn($authentication)
        ;

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('该认证记录当前状态不允许审核');

        $this->service->approveAuthentication($authId);
    }

    public function testRejectAuthentication(): void
    {
        $authId = 'test-auth-id';
        $reason = '身份证信息与公安部数据不符';
        $reviewNote = '经核实用户提供的身份证信息有误';
        $reviewer = 'admin@example.com';

        $authentication = $this->createMockAuthentication($authId, AuthenticationStatus::PENDING);

        $this->authRepository->expects($this->once())
            ->method('find')
            ->with($authId)
            ->willReturn($authentication)
        ;

        // Mock当前用户 - 只有在我们的Mock被使用时才设置期望
        $user = $this->createMockUser($reviewer);
        if ($this->isUsingMockSecurity()) {
            $this->security->expects($this->once())
                ->method('getUser')
                ->willReturn($user)
            ;
        }

        // EntityManager 是真实的，不需要 Mock

        // Mock日志记录 - 只有在我们的Mock被使用时才设置期望
        if ($this->isUsingMockLogger()) {
            $this->logger->expects($this->once())
                ->method('info')
                ->with('人工审核操作', self::callback(function ($context) use ($authId, $reviewer, $reason): bool {
                    /** @var array<string, mixed> $context */
                    return $context['auth_id'] === $authId
                        && 'reject' === $context['action']
                        && $context['reviewer'] === $reviewer
                        && $context['reason'] === $reason;
                }))
            ;
        }

        $result = $this->service->rejectAuthentication($authId, $reason, $reviewNote);

        $this->assertSame($authentication, $result);
        $this->assertSame(AuthenticationStatus::REJECTED, $authentication->getStatus());

        // 验证认证结果包含拒绝信息
        $verificationResult = $authentication->getVerificationResult();
        $this->assertNotNull($verificationResult);
        /** @var array<string, mixed> $verificationResult */
        $this->assertTrue($verificationResult['manual_review'] ?? false);

        // 如果使用真实的Security服务，审核者可能不是我们期望的用户
        if ($this->isUsingMockSecurity()) {
            // 使用Mock时，应该返回我们期望的用户
            $this->assertSame($reviewer, $verificationResult['reviewer'] ?? '');
        } else {
            // 使用真实Security服务时，至少要有一个审核者
            $this->assertNotEmpty($verificationResult['reviewer'] ?? '');
        }

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
        $authIds = ['auth-1', 'auth-2', 'auth-3'];
        $action = 'approve';
        $reviewNote = '批量审核通过';
        $reviewer = 'admin@example.com';

        // 创建认证记录
        $authentications = [
            $this->createMockAuthentication('auth-1', AuthenticationStatus::PENDING),
            $this->createMockAuthentication('auth-2', AuthenticationStatus::PENDING),
            $this->createMockAuthentication('auth-3', AuthenticationStatus::PENDING),
        ];

        // Mock repository查找
        $this->authRepository->expects($this->exactly(3))
            ->method('find')
            ->willReturnOnConsecutiveCalls(...$authentications)
        ;

        // Mock当前用户 - 只有在我们的Mock被使用时才设置期望
        $user = $this->createMockUser($reviewer);
        if ($this->isUsingMockSecurity()) {
            $this->security->expects($this->exactly(3))
                ->method('getUser')
                ->willReturn($user)
            ;
        }

        // Mock EntityManager操作
        // EntityManager 是真实的，不需要 Mock

        // Mock日志记录 - 只有在我们的Mock被使用时才设置期望
        if ($this->isUsingMockLogger()) {
            $this->logger->expects($this->exactly(3))
                ->method('info')
                ->with('人工审核操作', self::callback(fn ($value) => is_array($value)))
            ;
        }

        $results = $this->service->batchReview($authIds, $action, null, $reviewNote);

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
        $authIds = ['auth-1', 'auth-2'];
        $action = 'reject';
        $reason = '批量拒绝原因';
        $reviewNote = '批量审核拒绝';

        // 创建真实的认证实体对象
        $authentications = [
            $this->createMockAuthentication('auth-1', AuthenticationStatus::PENDING),
            $this->createMockAuthentication('auth-2', AuthenticationStatus::PENDING),
        ];

        // 使用callback来查找正确的认证对象
        $this->authRepository->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(function ($id) use ($authentications) {
                foreach ($authentications as $auth) {
                    if ($auth->getId() === $id) {
                        return $auth;
                    }
                }

                return null;
            })
        ;

        // Mock当前用户 - 只有在我们的Mock被使用时才设置期望
        $user = $this->createMockUser('admin@example.com');
        if ($this->isUsingMockSecurity()) {
            $this->security->expects($this->exactly(2))
                ->method('getUser')
                ->willReturn($user)
            ;
        }

        // EntityManager 是真实的，不需要 Mock

        // Mock日志记录 - 只有在我们的Mock被使用时才设置期望
        if ($this->isUsingMockLogger()) {
            $this->logger->expects($this->exactly(2))
                ->method('info')
            ;
        }

        $results = $this->service->batchReview($authIds, $action, $reason, $reviewNote);

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
        $authIds = ['auth-1', 'auth-2'];
        $action = 'approve';

        // 第一个成功，第二个失败（不存在）
        $authentication1 = $this->createMockAuthentication('auth-1', AuthenticationStatus::PENDING);

        $this->authRepository->expects($this->exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls($authentication1, null)
        ;

        // EntityManager 是真实的，不需要 Mock

        // 只有在我们的Mock被使用时才设置Logger期望
        if ($this->isUsingMockLogger()) {
            $this->logger->expects($this->once())
                ->method('info')
            ;

            // Mock错误日志
            $this->logger->expects($this->once())
                ->method('error')
                ->with('批量审核失败', self::callback(function ($context): bool {
                    /** @var array{auth_id: string, action: string, error: string} $context */
                    return 'auth-2' === $context['auth_id']
                        && 'approve' === $context['action']
                        && str_contains($context['error'], '认证记录不存在');
                }))
            ;
        }

        $results = $this->service->batchReview($authIds, $action);

        $this->assertCount(2, $results);
        /** @var non-empty-array<string, RealNameAuthentication|array{error: string}> $results */
        $this->assertInstanceOf(RealNameAuthentication::class, $results['auth-1']);
        $this->assertIsArray($results['auth-2']);
        $this->assertArrayHasKey('error', $results['auth-2']);
        $errorResult = $results['auth-2'];
        $this->assertIsArray($errorResult);
        /** @var array{error: string} $errorResult */
        $this->assertStringContainsString('认证记录不存在', $errorResult['error']);
    }

    /**
     * 创建真实的认证实体对象用于测试
     * 使用真实对象而非Mock，以便正确追踪状态变化
     */
    private function createMockAuthentication(
        string $id,
        AuthenticationStatus $status,
    ): RealNameAuthentication {
        $entityManager = self::getEntityManager();

        // 创建真实的用户对象
        $user = $this->createRealUser('test_user_' . $id);
        $entityManager->persist($user);
        $entityManager->flush();

        // 使用反射创建实体对象并设置初始状态
        $authentication = new RealNameAuthentication();

        // 使用反射设置ID（因为ID是通过构造函数自动生成的）
        $reflection = new \ReflectionClass($authentication);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($authentication, $id);

        // 设置必要的属性
        $authentication->setUser($user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setSubmittedData([
            'name' => '张三',
            'id_card' => '110101199001011234',
        ]);

        // 使用反射设置状态（避免通过updateStatus触发验证）
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $statusProperty->setValue($authentication, $status);

        // 持久化到数据库
        $entityManager->persist($authentication);
        $entityManager->flush();

        return $authentication;
    }

    /**
     * 创建真实的用户实体
     */
    private function createRealUser(string $identifier): UserInterface
    {
        // 使用BizUser实体类创建真实用户
        $userClass = 'BizUserBundle\Entity\BizUser';
        $user = new $userClass();

        // 使用反射设置用户标识
        $reflection = new \ReflectionClass($user);
        if ($reflection->hasProperty('username')) {
            $property = $reflection->getProperty('username');
            $property->setAccessible(true);
            $property->setValue($user, $identifier);
        }

        return $user;
    }

    /**
     * 检查是否使用的是我们的Mock服务
     */
    private function isUsingMockSecurity(): bool
    {
        return self::getContainer()->has(Security::class)
            && self::getContainer()->get(Security::class) === $this->security;
    }

    /**
     * 检查是否使用的是我们的Mock Logger
     */
    private function isUsingMockLogger(): bool
    {
        return self::getContainer()->has(LoggerInterface::class)
            && self::getContainer()->get(LoggerInterface::class) === $this->logger;
    }

    private function createMockUser(string $identifier): MockObject&UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($identifier);
        $user->method('getRoles')->willReturn(['ROLE_ADMIN']);

        return $user;
    }
}
