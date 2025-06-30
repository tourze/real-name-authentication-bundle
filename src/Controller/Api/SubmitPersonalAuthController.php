<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Api;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;
use Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO;

/**
 * 提交个人认证控制器
 */
class SubmitPersonalAuthController extends AbstractController
{
    public function __construct(
        private readonly PersonalAuthenticationService $personalAuthService,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route(path: '/api/auth/personal/submit', name: 'api_auth_personal_submit', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 获取当前登录用户
            $user = $this->getUser();
            if (!$user instanceof UserInterface) {
                return new JsonResponse(['error' => '用户未登录'], Response::HTTP_UNAUTHORIZED);
            }

            $data = json_decode($request->getContent(), true);
            
            if ($data === null || $data === false) {
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
            $dto = new PersonalAuthDTO(
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
        } catch (\Throwable $e) {
            $this->logger->error('个人认证提交失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse(['error' => '服务器内部错误'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}