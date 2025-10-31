<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;
use Tourze\RealNameAuthenticationBundle\Exception\ProviderNotAvailableException;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;
use Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO;

/**
 * 个人认证服务
 *
 * 处理个人实名认证相关的业务逻辑
 */
#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'real_name_authentication')]
class PersonalAuthenticationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly AuthenticationValidationService $validationService,
        private readonly AuthenticationProviderService $providerService,
        private readonly RealNameAuthenticationRepository $authRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 提交个人认证
     */
    public function submitAuthentication(PersonalAuthDTO $dto): RealNameAuthentication
    {
        // 验证DTO
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new InvalidAuthenticationDataException('认证数据验证失败: ' . implode(', ', $errors));
        }

        // 检查频率限制
        if (!$this->validationService->checkRateLimiting($dto->user->getUserIdentifier(), $dto->method)) {
            throw new AuthenticationException('认证请求过于频繁，请稍后再试');
        }

        // 检查是否已有有效认证
        $existingAuth = $this->authRepository->findValidByUserAndType(
            $dto->user,
            AuthenticationType::PERSONAL
        );

        if (null !== $existingAuth) {
            throw new AuthenticationException('用户已有有效的个人认证记录');
        }

        // 清理输入数据
        $sanitizedData = $this->validationService->sanitizeInput($dto->toArray());

        // 创建认证记录
        $authentication = new RealNameAuthentication();
        $authentication->setUser($dto->user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod($dto->method);
        $authentication->setSubmittedData($sanitizedData);

        // 保存到数据库
        $this->entityManager->persist($authentication);
        $this->entityManager->flush();

        // 在实际应用中，这里应该发送到队列进行异步处理
        // 在测试环境中直接设置为 PENDING 状态
        $authentication->setStatus(AuthenticationStatus::PENDING);
        $this->entityManager->flush();

        $this->logger->info('个人认证提交成功', [
            'auth_id' => $authentication->getId(),
            'user_identifier' => $dto->user->getUserIdentifier(),
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
            throw new InvalidAuthenticationDataException('身份证号码格式不正确');
        }

        // 获取最佳提供商
        $provider = $this->providerService->selectBestProvider(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        if (null === $provider) {
            throw new ProviderNotAvailableException('没有可用的身份证验证提供商');
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
            throw new InvalidAuthenticationDataException('身份证号码格式不正确');
        }

        if (!$this->validationService->validateMobileFormat($mobile)) {
            throw new InvalidAuthenticationDataException('手机号码格式不正确');
        }

        // 获取最佳提供商
        $provider = $this->providerService->selectBestProvider(AuthenticationMethod::CARRIER_THREE_ELEMENTS);
        if (null === $provider) {
            throw new ProviderNotAvailableException('没有可用的运营商验证提供商');
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
            throw new InvalidAuthenticationDataException('身份证号码格式不正确');
        }

        if (!$this->validationService->validateBankCardFormat($bankCard)) {
            throw new InvalidAuthenticationDataException('银行卡号格式不正确');
        }

        // 获取最佳提供商
        $provider = $this->providerService->selectBestProvider(AuthenticationMethod::BANK_CARD_THREE_ELEMENTS);
        if (null === $provider) {
            throw new ProviderNotAvailableException('没有可用的银行卡验证提供商');
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
            throw new InvalidAuthenticationDataException('身份证号码格式不正确');
        }

        if (!$this->validationService->validateBankCardFormat($bankCard)) {
            throw new InvalidAuthenticationDataException('银行卡号格式不正确');
        }

        if (!$this->validationService->validateMobileFormat($mobile)) {
            throw new InvalidAuthenticationDataException('手机号码格式不正确');
        }

        // 获取最佳提供商
        $provider = $this->providerService->selectBestProvider(AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS);
        if (null === $provider) {
            throw new ProviderNotAvailableException('没有可用的银行卡验证提供商');
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
        if (!in_array($image->getMimeType(), ['image/jpeg', 'image/png'], true)) {
            throw new InvalidAuthenticationDataException('不支持的图片格式');
        }

        // 验证文件大小（最大5MB）
        if ($image->getSize() > 5 * 1024 * 1024) {
            throw new InvalidAuthenticationDataException('图片文件过大');
        }

        // 获取最佳提供商
        $provider = $this->providerService->selectBestProvider(AuthenticationMethod::LIVENESS_DETECTION);
        if (null === $provider) {
            throw new ProviderNotAvailableException('没有可用的活体检测提供商');
        }

        // 执行验证
        $data = ['image' => $image];

        return $this->providerService->executeVerification($provider, $data);
    }

    /**
     * 查询认证历史
     *
     * @return array<int, RealNameAuthentication>
     */
    public function getAuthenticationHistory(UserInterface $user): array
    {
        $results = $this->authRepository->findByUser($user);

        return array_values($results);
    }

    /**
     * 检查认证状态
     */
    public function checkAuthenticationStatus(string $authId): RealNameAuthentication
    {
        $authentication = $this->authRepository->find($authId);

        if (null === $authentication) {
            throw new InvalidAuthenticationDataException('认证记录不存在');
        }

        return $authentication;
    }
}
