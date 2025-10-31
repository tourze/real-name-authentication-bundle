<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Api;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
#[WithMonologChannel(channel: 'real_name_authentication')]
final class SubmitPersonalAuthController extends AbstractController
{
    public function __construct(
        private readonly PersonalAuthenticationService $personalAuthService,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/api/auth/personal/submit', name: 'api_auth_personal_submit', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $this->validateUser();
            if (!$user instanceof UserInterface) {
                return $user; // JsonResponse with error
            }

            $data = $this->parseRequestData($request);
            if ($data instanceof JsonResponse) {
                return $data; // Error response
            }

            $method = $this->parseAuthenticationMethod($data);
            if ($method instanceof JsonResponse) {
                return $method; // Error response
            }

            $dto = $this->createPersonalAuthDTO($user, $method, $data, $request);
            $validationResponse = $this->validateDTO($dto);
            if ($validationResponse instanceof JsonResponse) {
                return $validationResponse; // Error response
            }

            $authentication = $this->personalAuthService->submitAuthentication($dto);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'authId' => $authentication->getId(),
                    'status' => $authentication->getStatus()->value,
                    'message' => '认证已提交，请等待处理结果',
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_TOO_MANY_REQUESTS);
        } catch (\Throwable $e) {
            $this->logger->error('个人认证提交失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(['error' => '服务器内部错误'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function validateUser(): UserInterface|JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            return new JsonResponse(['error' => '用户未登录'], Response::HTTP_UNAUTHORIZED);
        }

        return $user;
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function parseRequestData(Request $request): array|JsonResponse
    {
        $content = $request->getContent();
        if ('' === $content) {
            return new JsonResponse(['error' => '请求内容为空'], Response::HTTP_BAD_REQUEST);
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['error' => '无效的JSON格式'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($decoded)) {
            return new JsonResponse(['error' => '请求数据格式错误'], Response::HTTP_BAD_REQUEST);
        }

        // 确保所有键都是字符串类型
        foreach (array_keys($decoded) as $key) {
            if (!is_string($key)) {
                return new JsonResponse(['error' => '请求数据格式错误'], Response::HTTP_BAD_REQUEST);
            }
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function parseAuthenticationMethod(array $data): AuthenticationMethod|JsonResponse
    {
        if (!isset($data['method']) || !is_string($data['method'])) {
            return new JsonResponse(['error' => '缺少必须的字段'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return AuthenticationMethod::from($data['method']);
        } catch (\ValueError) {
            return new JsonResponse(['error' => '无效的认证方式'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createPersonalAuthDTO(
        UserInterface $user,
        AuthenticationMethod $method,
        array $data,
        Request $request,
    ): PersonalAuthDTO {
        $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : null;
        $idCard = isset($data['idCard']) && is_string($data['idCard']) ? $data['idCard'] : null;
        $mobile = isset($data['mobile']) && is_string($data['mobile']) ? $data['mobile'] : null;
        $bankCard = isset($data['bankCard']) && is_string($data['bankCard']) ? $data['bankCard'] : null;
        $image = $request->files->get('image');
        $imageFile = $image instanceof UploadedFile ? $image : null;

        return new PersonalAuthDTO(
            user: $user,
            method: $method,
            name: $name,
            idCard: $idCard,
            mobile: $mobile,
            bankCard: $bankCard,
            image: $imageFile
        );
    }

    private function validateDTO(PersonalAuthDTO $dto): ?JsonResponse
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return new JsonResponse(['error' => implode(', ', $errors)], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }
}
