<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\RealNameAuthenticationBundle\Dto\PersonalAuthDto;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;

/**
 * 个人认证服务
 * 
 * 处理个人实名认证相关的业务逻辑
 */
class PersonalAuthenticationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly AuthenticationValidationService $validationService,
        private readonly AuthenticationProviderService $providerService,
        private readonly RealNameAuthenticationRepository $authRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * 提交个人认证
     */
    public function submitAuthentication(PersonalAuthDto $dto): RealNameAuthentication
    {
        // 验证DTO
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new \InvalidArgumentException('认证数据验证失败: ' . (string) $violations);
        }

        // 检查频率限制
        if (!$this->validationService->checkRateLimiting($dto->userId, $dto->method)) {
            throw new \RuntimeException('认证请求过于频繁，请稍后再试');
        }

        // 检查是否已有有效认证
        $existingAuth = $this->authRepository->findValidByUserIdAndType(
            $dto->userId,
            AuthenticationType::PERSONAL
        );

        if ($existingAuth) {
            throw new \RuntimeException('用户已有有效的个人认证记录');
        }

        // 清理输入数据
        $sanitizedData = $this->validationService->sanitizeInput($dto->toArray());

        // 创建认证记录
        $authentication = new RealNameAuthentication();
        $authentication->setUserId($dto->userId);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod($dto->method);
        $authentication->setSubmittedData($sanitizedData);

        // 保存到数据库
        $this->entityManager->persist($authentication);
        $this->entityManager->flush();

        // 异步处理认证
        $this->processAuthentication($authentication, $dto);

        $this->logger->info('个人认证提交成功', [
            'auth_id' => $authentication->getId(),
            'user_id' => $dto->userId,
            'method' => $dto->method->value,
        ]);

        return $authentication;
    }

    /**
     * 身份证二要素验证
     */
    public function verifyIdCardTwoElements(string $name, string $idCard): AuthenticationResult
    {
        // 格式验证
        if (!$this->validationService->validateIdCardFormat($idCard)) {
            throw new \InvalidArgumentException('身份证号码格式不正确');
        }

        // 获取最佳提供商
        $provider = $this->providerService->selectBestProvider(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        if (!$provider) {
            throw new \RuntimeException('没有可用的身份证验证提供商');
        }

        // 执行验证
        $data = ['name' => $name, 'id_card' => $idCard];
        return $this->providerService->executeVerification($provider, $data);
    }

    /**
     * 运营商三要素验证
     */
    public function verifyCarrierThreeElements(string $name, string $idCard, string $mobile): AuthenticationResult
    {
        // 格式验证
        if (!$this->validationService->validateIdCardFormat($idCard)) {
            throw new \InvalidArgumentException('身份证号码格式不正确');
        }

        if (!$this->validationService->validateMobileFormat($mobile)) {
            throw new \InvalidArgumentException('手机号码格式不正确');
        }

        // 获取最佳提供商
        $provider = $this->providerService->selectBestProvider(AuthenticationMethod::CARRIER_THREE_ELEMENTS);
        if (!$provider) {
            throw new \RuntimeException('没有可用的运营商验证提供商');
        }

        // 执行验证
        $data = ['name' => $name, 'id_card' => $idCard, 'mobile' => $mobile];
        return $this->providerService->executeVerification($provider, $data);
    }

    /**
     * 银行卡三要素验证
     */
    public function verifyBankCardThreeElements(string $name, string $idCard, string $bankCard): AuthenticationResult
    {
        // 格式验证
        if (!$this->validationService->validateIdCardFormat($idCard)) {
            throw new \InvalidArgumentException('身份证号码格式不正确');
        }
        
        if (!$this->validationService->validateBankCardFormat($bankCard)) {
            throw new \InvalidArgumentException('银行卡号格式不正确');
        }

        // 获取最佳提供商
        $provider = $this->providerService->selectBestProvider(AuthenticationMethod::BANK_CARD_THREE_ELEMENTS);
        if (!$provider) {
            throw new \RuntimeException('没有可用的银行卡验证提供商');
        }

        // 执行验证
        $data = ['name' => $name, 'id_card' => $idCard, 'bank_card' => $bankCard];
        return $this->providerService->executeVerification($provider, $data);
    }

    /**
     * 银行卡四要素验证
     */
    public function verifyBankCardFourElements(string $name, string $idCard, string $bankCard, string $mobile): AuthenticationResult
    {
        // 格式验证
        if (!$this->validationService->validateIdCardFormat($idCard)) {
            throw new \InvalidArgumentException('身份证号码格式不正确');
        }
        
        if (!$this->validationService->validateBankCardFormat($bankCard)) {
            throw new \InvalidArgumentException('银行卡号格式不正确');
        }
        
        if (!$this->validationService->validateMobileFormat($mobile)) {
            throw new \InvalidArgumentException('手机号码格式不正确');
        }

        // 获取最佳提供商
        $provider = $this->providerService->selectBestProvider(AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS);
        if (!$provider) {
            throw new \RuntimeException('没有可用的银行卡验证提供商');
        }

        // 执行验证
        $data = ['name' => $name, 'id_card' => $idCard, 'bank_card' => $bankCard, 'mobile' => $mobile];
        return $this->providerService->executeVerification($provider, $data);
    }

    /**
     * 活体检测
     */
    public function performLivenessDetection(UploadedFile $image): AuthenticationResult
    {
        // 验证文件类型
        if (!in_array($image->getMimeType(), ['image/jpeg', 'image/png'])) {
            throw new \InvalidArgumentException('不支持的图片格式');
        }

        // 验证文件大小（最大5MB）
        if ($image->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('图片文件过大');
        }

        // 获取最佳提供商
        $provider = $this->providerService->selectBestProvider(AuthenticationMethod::LIVENESS_DETECTION);
        if (!$provider) {
            throw new \RuntimeException('没有可用的活体检测提供商');
        }

        // 执行验证
        $data = ['image' => $image];
        return $this->providerService->executeVerification($provider, $data);
    }

    /**
     * 获取认证历史
     */
    public function getAuthenticationHistory(string $userId): array
    {
        return $this->authRepository->findByUserId($userId);
    }

    /**
     * 检查认证状态
     */
    public function checkAuthenticationStatus(string $authId): RealNameAuthentication
    {
        $authentication = $this->entityManager->find(RealNameAuthentication::class, $authId);

        if (!$authentication) {
            throw new \InvalidArgumentException('认证记录不存在');
        }

        return $authentication;
    }

    /**
     * 处理认证（异步）
     */
    private function processAuthentication(RealNameAuthentication $authentication, PersonalAuthDto $dto): void
    {
        try {
            // 更新状态为处理中
            $authentication->updateStatus(AuthenticationStatus::PROCESSING);
            $this->entityManager->flush();

            $result = match ($dto->method) {
                AuthenticationMethod::ID_CARD_TWO_ELEMENTS =>
                    $this->verifyIdCardTwoElements($dto->name, $dto->idCard),
                AuthenticationMethod::CARRIER_THREE_ELEMENTS =>
                    $this->verifyCarrierThreeElements($dto->name, $dto->idCard, $dto->mobile),
                AuthenticationMethod::BANK_CARD_THREE_ELEMENTS =>
                    $this->verifyBankCardThreeElements($dto->name, $dto->idCard, $dto->bankCard),
                AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS =>
                    $this->verifyBankCardFourElements($dto->name, $dto->idCard, $dto->bankCard, $dto->mobile),
                AuthenticationMethod::LIVENESS_DETECTION =>
                    $this->performLivenessDetection($dto->image),
                default => throw new \InvalidArgumentException('不支持的认证方式'),
            };

            // 更新认证结果
            $status = $result->isSuccess() ? AuthenticationStatus::APPROVED : AuthenticationStatus::REJECTED;
            $authentication->updateStatus(
                $status,
                ['confidence' => $result->getConfidence()],
                $result->getResponseData(),
                $result->getErrorMessage()
            );

            // 如果认证成功，设置过期时间（一年）
            if ($result->isSuccess()) {
                $authentication->setExpireTime(new \DateTimeImmutable('+1 year'));
            }

        } catch (\Exception $e) {
            // 处理异常
            $authentication->updateStatus(
                AuthenticationStatus::REJECTED,
                null,
                null,
                '认证处理异常: ' . $e->getMessage()
            );

            $this->logger->error('个人认证处理失败', [
                'auth_id' => $authentication->getId(),
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->entityManager->flush();
        }
    }
}
