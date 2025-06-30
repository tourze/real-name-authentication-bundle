<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Api;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * 查询认证历史控制器
 */
class GetAuthHistoryController extends AbstractController
{
    public function __construct(
        private readonly PersonalAuthenticationService $personalAuthService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route(path: '/api/auth/personal/history', name: 'api_auth_personal_history', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            // 获取当前登录用户
            $user = $this->getUser();
            if (!$user instanceof UserInterface) {
                return new JsonResponse(['error' => '用户未登录'], Response::HTTP_UNAUTHORIZED);
            }

            $authentications = $this->personalAuthService->getAuthenticationHistory($user);
            
            $data = [];
            foreach ($authentications as $auth) {
                $data[] = [
                    'id' => $auth->getId(),
                    'type' => $auth->getType()->value,
                    'method' => $auth->getMethod()->value,
                    'methodLabel' => $auth->getMethod()->getLabel(),
                    'status' => $auth->getStatus()->value,
                    'statusLabel' => $auth->getStatus()->getLabel(),
                    'reason' => $auth->getReason(),
                    'createTime' => $auth->getCreateTime()->format('Y-m-d H:i:s'),
                    'updateTime' => $auth->getUpdateTime()->format('Y-m-d H:i:s'),
                    'expireTime' => $auth->getExpireTime()?->format('Y-m-d H:i:s'),
                    'isExpired' => $auth->isExpired(),
                    'isApproved' => $auth->isApproved(),
                ];
            }

            return new JsonResponse([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('查询认证历史失败', [
                'user_identifier' => $this->getUser()?->getUserIdentifier(),
                'error' => $e->getMessage()
            ]);

            return new JsonResponse(['error' => '服务器内部错误'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}