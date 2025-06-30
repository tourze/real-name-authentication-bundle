<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;

/**
 * 人工审核服务
 *
 * 提供后台管理员手动审核认证申请的功能
 */
class ManualReviewService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RealNameAuthenticationRepository $authRepository,
        private readonly Security $security,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * 通过认证申请
     */
    public function approveAuthentication(string $authId, ?string $reviewNote = null): RealNameAuthentication
    {
        $authentication = $this->getAuthenticationForReview($authId);
        
        // 检查当前状态是否可以审核
        if (!$this->canReview($authentication)) {
            throw new AuthenticationException('该认证记录当前状态不允许审核');
        }

        $reviewer = $this->getCurrentReviewer();
        
        // 更新认证状态为通过
        $authentication->updateStatus(
            AuthenticationStatus::APPROVED,
            [
                'manual_review' => true,
                'reviewer' => $reviewer,
                'review_time' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'review_note' => $reviewNote,
                'confidence' => 1.0, // 人工审核给予最高置信度
            ],
            [
                'review_method' => 'manual',
                'reviewer' => $reviewer,
                'review_action' => 'approve',
                'review_note' => $reviewNote,
            ]
        );

        // 设置过期时间（1年后）
        $authentication->setExpireTime(new DateTimeImmutable('+1 year'));

        $this->entityManager->flush();

        // 记录审核日志
        $this->logReviewAction($authentication, 'approve', $reviewer, $reviewNote);

        return $authentication;
    }

    /**
     * 拒绝认证申请
     */
    public function rejectAuthentication(string $authId, string $reason, ?string $reviewNote = null): RealNameAuthentication
    {
        if (empty($reason)) {
            throw new InvalidAuthenticationDataException('拒绝认证必须提供拒绝原因');
        }

        $authentication = $this->getAuthenticationForReview($authId);
        
        // 检查当前状态是否可以审核
        if (!$this->canReview($authentication)) {
            throw new AuthenticationException('该认证记录当前状态不允许审核');
        }

        $reviewer = $this->getCurrentReviewer();
        
        // 更新认证状态为拒绝
        $authentication->updateStatus(
            AuthenticationStatus::REJECTED,
            [
                'manual_review' => true,
                'reviewer' => $reviewer,
                'review_time' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'review_note' => $reviewNote,
                'confidence' => 0.0,
            ],
            [
                'review_method' => 'manual',
                'reviewer' => $reviewer,
                'review_action' => 'reject',
                'review_note' => $reviewNote,
            ],
            $reason
        );

        $this->entityManager->flush();

        // 记录审核日志
        $this->logReviewAction($authentication, 'reject', $reviewer, $reviewNote, $reason);

        return $authentication;
    }

    /**
     * 批量审核认证申请
     */
    public function batchReview(array $authIds, string $action, ?string $reason = null, ?string $reviewNote = null): array
    {
        if (!in_array($action, ['approve', 'reject'])) {
            throw new InvalidAuthenticationDataException('无效的审核操作');
        }

        if ($action === 'reject' && empty($reason)) {
            throw new InvalidAuthenticationDataException('批量拒绝必须提供拒绝原因');
        }

        $results = [];
        
        foreach ($authIds as $authId) {
            try {
                if ($action === 'approve') {
                    $results[$authId] = $this->approveAuthentication($authId, $reviewNote);
                } else {
                    $results[$authId] = $this->rejectAuthentication($authId, $reason, $reviewNote);
                }
            } catch (\Throwable $e) {
                $this->logger->error('批量审核失败', [
                    'auth_id' => $authId,
                    'action' => $action,
                    'error' => $e->getMessage()
                ]);
                $results[$authId] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * 获取待审核的认证记录
     */
    public function getPendingAuthentications(int $limit = 50): array
    {
        return $this->authRepository->findBy(
            ['status' => AuthenticationStatus::PENDING, 'valid' => true],
            ['createTime' => 'ASC'],
            $limit
        );
    }

    /**
     * 获取审核统计信息
     */
    public function getReviewStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $approved = $this->authRepository->countByStatusAndDateRange(
            AuthenticationStatus::APPROVED,
            $startDate,
            $endDate
        );

        $rejected = $this->authRepository->countByStatusAndDateRange(
            AuthenticationStatus::REJECTED,
            $startDate,
            $endDate
        );

        $pending = $this->authRepository->countByStatusAndDateRange(
            AuthenticationStatus::PENDING,
            $startDate,
            $endDate
        );

        return [
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
            'total' => $approved + $rejected + $pending,
            'approval_rate' => $approved + $rejected > 0 ? round($approved / ($approved + $rejected) * 100, 2) : 0,
        ];
    }

    /**
     * 获取认证记录用于审核
     */
    private function getAuthenticationForReview(string $authId): RealNameAuthentication
    {
        $authentication = $this->authRepository->find($authId);
        
        if ($authentication === null) {
            throw new AuthenticationException('认证记录不存在');
        }

        if (!$authentication->isValid()) {
            throw new AuthenticationException('认证记录已失效');
        }

        return $authentication;
    }

    /**
     * 检查是否可以审核
     */
    private function canReview(RealNameAuthentication $authentication): bool
    {
        return in_array($authentication->getStatus(), [
            AuthenticationStatus::PENDING,
            AuthenticationStatus::PROCESSING
        ]);
    }

    /**
     * 获取当前审核人
     */
    private function getCurrentReviewer(): string
    {
        $user = $this->security->getUser();
        return $user !== null ? $user->getUserIdentifier() : 'system';
    }

    /**
     * 记录审核操作日志
     */
    private function logReviewAction(
        RealNameAuthentication $authentication,
        string $action,
        string $reviewer,
        ?string $reviewNote = null,
        ?string $reason = null
    ): void {
        $this->logger->info('人工审核操作', [
            'auth_id' => $authentication->getId(),
            'user_identifier' => $authentication->getUserIdentifier(),
            'action' => $action,
            'reviewer' => $reviewer,
            'review_note' => $reviewNote,
            'reason' => $reason,
            'timestamp' => time(),
        ]);
    }
} 