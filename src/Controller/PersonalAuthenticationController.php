<?php

namespace Tourze\RealNameAuthenticationBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\RealNameAuthenticationBundle\Dto\PersonalAuthDto;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * 个人认证控制器
 */
#[Route('/api/auth/personal', name: 'api_auth_personal_')]
class PersonalAuthenticationController extends AbstractController
{
    public function __construct(
        private readonly PersonalAuthenticationService $personalAuthService,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * 提交个人认证
     */
    #[Route('/submit', name: 'submit', methods: ['POST'])]
    public function submitPersonalAuth(Request $request): JsonResponse
    {
        try {
            // 获取当前登录用户
            $user = $this->getUser();
            if (!$user instanceof UserInterface) {
                return new JsonResponse(['error' => '用户未登录'], Response::HTTP_UNAUTHORIZED);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['error' => '无效的请求数据'], Response::HTTP_BAD_REQUEST);
            }

            // 验证必须字段
            if (!isset($data['method'])) {
                return new JsonResponse(['error' => '缺少必须的字段'], Response::HTTP_BAD_REQUEST);
            }

            // 解析认证方式
            try {
                $method = AuthenticationMethod::from($data['method']);
            } catch (\ValueError) {
                return new JsonResponse(['error' => '无效的认证方式'], Response::HTTP_BAD_REQUEST);
            }

            // 创建DTO
            $dto = new PersonalAuthDto(
                user: $user,
                method: $method,
                name: $data['name'] ?? null,
                idCard: $data['idCard'] ?? null,
                mobile: $data['mobile'] ?? null,
                bankCard: $data['bankCard'] ?? null,
                image: $request->files->get('image')
            );

            // 验证DTO
            $violations = $this->validator->validate($dto);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }
                return new JsonResponse(['error' => implode(', ', $errors)], Response::HTTP_BAD_REQUEST);
            }

            // 提交认证
            $authentication = $this->personalAuthService->submitAuthentication($dto);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'authId' => $authentication->getId(),
                    'status' => $authentication->getStatus()->value,
                    'message' => '认证已提交，请等待处理结果'
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_TOO_MANY_REQUESTS);
        } catch (\Exception $e) {
            $this->logger->error('个人认证提交失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse(['error' => '服务器内部错误'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 查询认证历史
     */
    #[Route('/history', name: 'history', methods: ['GET'])]
    public function getAuthHistory(): JsonResponse
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

        } catch (\Exception $e) {
            $this->logger->error('查询认证历史失败', [
                'user_identifier' => $this->getUser()?->getUserIdentifier(),
                'error' => $e->getMessage()
            ]);

            return new JsonResponse(['error' => '服务器内部错误'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 查询认证状态
     */
    #[Route('/status/{authId}', name: 'status', methods: ['GET'])]
    public function checkAuthStatus(string $authId): JsonResponse
    {
        try {
            $authentication = $this->personalAuthService->checkAuthenticationStatus($authId);

            // 检查认证记录是否属于当前用户
            $currentUser = $this->getUser();
            if (!$currentUser instanceof UserInterface || 
                $authentication->getUser()->getUserIdentifier() !== $currentUser->getUserIdentifier()) {
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
                    'createTime' => $authentication->getCreateTime()->format('Y-m-d H:i:s'),
                    'updateTime' => $authentication->getUpdateTime()->format('Y-m-d H:i:s'),
                    'expireTime' => $authentication->getExpireTime()?->format('Y-m-d H:i:s'),
                    'isExpired' => $authentication->isExpired(),
                    'isApproved' => $authentication->isApproved(),
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('查询认证状态失败', [
                'authId' => $authId,
                'error' => $e->getMessage()
            ]);

            return new JsonResponse(['error' => '服务器内部错误'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 获取支持的认证方式
     */
    #[Route('/methods', name: 'methods', methods: ['GET'])]
    public function getSupportedMethods(): JsonResponse
    {
        $methods = [];
        
        foreach (AuthenticationMethod::cases() as $method) {
            if ($method->isPersonal()) {
                $methods[] = [
                    'value' => $method->value,
                    'label' => $method->getLabel(),
                    'requiredFields' => $method->getRequiredFields(),
                ];
            }
        }

        return new JsonResponse([
            'success' => true,
            'data' => $methods
        ]);
    }
}
