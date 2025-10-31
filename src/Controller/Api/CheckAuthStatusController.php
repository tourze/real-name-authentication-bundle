<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Api;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * 查询认证状态控制器
 */
#[WithMonologChannel(channel: 'real_name_authentication')]
final class CheckAuthStatusController extends AbstractController
{
    public function __construct(
        private readonly PersonalAuthenticationService $personalAuthService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/api/auth/personal/status/{authId}', name: 'api_auth_personal_status', methods: ['GET'])]
    public function __invoke(string $authId): JsonResponse
    {
        try {
            $authentication = $this->personalAuthService->checkAuthenticationStatus($authId);

            // 检查认证记录是否属于当前用户
            $currentUser = $this->getUser();
            if (!$currentUser instanceof UserInterface
                || $authentication->getUser()->getUserIdentifier() !== $currentUser->getUserIdentifier()) {
                return new JsonResponse(['error' => '无权限访问此认证记录'], Response::HTTP_FORBIDDEN);
            }

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $authentication->getId(),
                    'userIdentifier' => $authentication->getUserIdentifier(),
                    'type' => $authentication->getType()->value,
                    'method' => $authentication->getMethod()->value,
                    'methodLabel' => $authentication->getMethod()->getLabel(),
                    'status' => $authentication->getStatus()->value,
                    'statusLabel' => $authentication->getStatus()->getLabel(),
                    'reason' => $authentication->getReason(),
                    'createTime' => $authentication->getCreateTime()?->format('Y-m-d H:i:s'),
                    'updateTime' => $authentication->getUpdateTime()?->format('Y-m-d H:i:s'),
                    'expireTime' => $authentication->getExpireTime()?->format('Y-m-d H:i:s'),
                    'isExpired' => $authentication->isExpired(),
                    'isApproved' => $authentication->isApproved(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            $this->logger->error('查询认证状态失败', [
                'authId' => $authId,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse(['error' => '服务器内部错误'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
