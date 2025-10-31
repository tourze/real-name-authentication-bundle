<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;
use Tourze\RealNameAuthenticationBundle\Exception\ProviderNotAvailableException;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;
use Tourze\RealNameAuthenticationBundle\Service\AuthenticationProviderService;
use Tourze\RealNameAuthenticationBundle\Service\AuthenticationValidationService;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;
use Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO;

/**
 * @internal
 */
#[CoversClass(PersonalAuthenticationService::class)]
#[RunTestsInSeparateProcesses]
final class PersonalAuthenticationServiceTest extends AbstractIntegrationTestCase
{
    private MockObject&ValidatorInterface $validator;

    private MockObject&AuthenticationValidationService $validationService;

    private MockObject&RealNameAuthenticationRepository $authRepository;

    private MockObject&LoggerInterface $logger;

    private MockObject&AuthenticationProviderService $providerService;

    private PersonalAuthenticationService $service;

    protected function onSetUp(): void
    {
        // 创建所有需要的Mock对象
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->validationService = $this->createMock(AuthenticationValidationService::class);
        $this->authRepository = $this->createMock(RealNameAuthenticationRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->providerService = $this->createMock(AuthenticationProviderService::class);

        // 覆盖容器中的依赖并获取服务
        $container = self::getContainer();
        try {
            $container->set(ValidatorInterface::class, $this->validator);
        } catch (InvalidArgumentException) {
        }
        try {
            $container->set(AuthenticationValidationService::class, $this->validationService);
        } catch (InvalidArgumentException) {
        }
        try {
            $container->set(RealNameAuthenticationRepository::class, $this->authRepository);
        } catch (InvalidArgumentException) {
        }
        try {
            $container->set(LoggerInterface::class, $this->logger);
        } catch (InvalidArgumentException) {
        }
        try {
            $container->set('monolog.logger.real_name_authentication', $this->logger);
        } catch (InvalidArgumentException) {
        }
        try {
            $container->set(AuthenticationProviderService::class, $this->providerService);
        } catch (InvalidArgumentException) {
        }

        // 从容器获取服务实例，现在会使用我们注册的Mock对象
        $this->service = self::getService(PersonalAuthenticationService::class);
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(PersonalAuthenticationService::class, $this->service);
    }

    public function testSubmitAuthentication(): void
    {
        // 创建真实的用户实体（Doctrine 需要真实实体）
        $user = $this->createNormalUser('test@example.com', 'password');
        $dto = new PersonalAuthDTO(
            user: $user,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '张三',
            idCard: '110101199001011234'
        );

        // Mock验证器
        $emptyViolations = $this->createMock(ConstraintViolationListInterface::class);
        $emptyViolations->method('count')->willReturn(0);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($emptyViolations)
        ;

        // Mock频率限制检查
        $this->validationService->expects($this->once())
            ->method('checkRateLimiting')
            ->with('test@example.com', AuthenticationMethod::ID_CARD_TWO_ELEMENTS)
            ->willReturn(true)
        ;

        // Mock检查现有认证
        $this->authRepository->expects($this->once())
            ->method('findValidByUserAndType')
            ->with($user, AuthenticationType::PERSONAL)
            ->willReturn(null)
        ;

        // Mock数据清理
        $sanitizedData = ['name' => '张三', 'id_card' => '110101199001011234'];
        $this->validationService->expects($this->once())
            ->method('sanitizeInput')
            ->willReturn($sanitizedData)
        ;

        // EntityManager 是真实的，不需要 Mock
        // 测试会自动调用 persist 和 flush

        // Mock日志记录
        $this->logger->expects($this->once())
            ->method('info')
            ->with('个人认证提交成功', self::callback(function ($context): bool {
                /** @var array<string, mixed> $context */
                return 'test@example.com' === $context['user_identifier']
                    && 'id_card_two_elements' === $context['method'];
            }))
        ;

        $result = $this->service->submitAuthentication($dto);

        $this->assertInstanceOf(RealNameAuthentication::class, $result);
        $this->assertSame($user, $result->getUser());
        $this->assertSame(AuthenticationType::PERSONAL, $result->getType());
        $this->assertSame(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $result->getMethod());
        $this->assertSame(AuthenticationStatus::PENDING, $result->getStatus());
        $this->assertSame($sanitizedData, $result->getSubmittedData());
    }

    public function testSubmitAuthenticationWithValidationErrors(): void
    {
        // 使用真实用户实体
        $user = $this->createNormalUser('test@example.com', 'password');
        $dto = new PersonalAuthDTO(
            user: $user,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '',
            idCard: 'invalid'
        );

        // Mock验证错误
        $violations = new ConstraintViolationList([
            new ConstraintViolation('姓名不能为空', null, [], '', 'name', ''),
            new ConstraintViolation('身份证号格式不正确', null, [], '', 'idCard', 'invalid'),
        ]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($violations)
        ;

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('认证数据验证失败');

        $this->service->submitAuthentication($dto);
    }

    public function testSubmitAuthenticationWithRateLimit(): void
    {
        // 使用真实用户实体
        $user = $this->createNormalUser('test@example.com', 'password');
        $dto = new PersonalAuthDTO(
            user: $user,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '张三',
            idCard: '110101199001011234'
        );

        $emptyViolations = $this->createMock(ConstraintViolationListInterface::class);
        $emptyViolations->method('count')->willReturn(0);
        $this->validator->method('validate')->willReturn($emptyViolations);

        // Mock频率限制
        $this->validationService->expects($this->once())
            ->method('checkRateLimiting')
            ->willReturn(false)
        ;

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('认证请求过于频繁，请稍后再试');

        $this->service->submitAuthentication($dto);
    }

    public function testSubmitAuthenticationWithExistingAuth(): void
    {
        // 使用真实用户实体
        $user = $this->createNormalUser('test@example.com', 'password');
        $dto = new PersonalAuthDTO(
            user: $user,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '张三',
            idCard: '110101199001011234'
        );

        $emptyViolations = $this->createMock(ConstraintViolationListInterface::class);
        $emptyViolations->method('count')->willReturn(0);
        $this->validator->method('validate')->willReturn($emptyViolations);
        $this->validationService->method('checkRateLimiting')->willReturn(true);

        // Mock现有认证
        $existingAuth = $this->createMock(RealNameAuthentication::class);
        $this->authRepository->expects($this->once())
            ->method('findValidByUserAndType')
            ->willReturn($existingAuth)
        ;

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('用户已有有效的个人认证记录');

        $this->service->submitAuthentication($dto);
    }

    public function testCheckAuthenticationStatus(): void
    {
        $authId = 'test-auth-id';
        $authentication = $this->createMock(RealNameAuthentication::class);

        $this->authRepository->expects($this->once())
            ->method('find')
            ->with($authId)
            ->willReturn($authentication)
        ;

        $result = $this->service->checkAuthenticationStatus($authId);

        $this->assertSame($authentication, $result);
    }

    public function testCheckAuthenticationStatusWithNonExistentId(): void
    {
        $authId = 'non-existent-id';

        $this->authRepository->expects($this->once())
            ->method('find')
            ->with($authId)
            ->willReturn(null)
        ;

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('认证记录不存在');

        $this->service->checkAuthenticationStatus($authId);
    }

    public function testVerifyIdCardTwoElements(): void
    {
        $name = '张三';
        $idCard = '110101199001011234';

        // Mock格式验证
        $this->validationService->expects($this->once())
            ->method('validateIdCardFormat')
            ->with($idCard)
            ->willReturn(true)
        ;

        // Mock提供商选择
        $provider = $this->createMock(AuthenticationProvider::class);
        $this->providerService->expects($this->once())
            ->method('selectBestProvider')
            ->with(AuthenticationMethod::ID_CARD_TWO_ELEMENTS)
            ->willReturn($provider)
        ;

        // Mock验证执行
        $authResult = $this->createMock(AuthenticationResult::class);
        $this->providerService->expects($this->once())
            ->method('executeVerification')
            ->with($provider, ['name' => $name, 'id_card' => $idCard])
            ->willReturn($authResult)
        ;

        $result = $this->service->verifyIdCardTwoElements($name, $idCard);

        $this->assertSame($authResult, $result);
    }

    public function testVerifyIdCardTwoElementsWithInvalidFormat(): void
    {
        $name = '张三';
        $idCard = 'invalid-id-card';

        $this->validationService->expects($this->once())
            ->method('validateIdCardFormat')
            ->with($idCard)
            ->willReturn(false)
        ;

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('身份证号码格式不正确');

        $this->service->verifyIdCardTwoElements($name, $idCard);
    }

    public function testVerifyIdCardTwoElementsWithNoProvider(): void
    {
        $name = '张三';
        $idCard = '110101199001011234';

        $this->validationService->method('validateIdCardFormat')->willReturn(true);
        $this->providerService->expects($this->once())
            ->method('selectBestProvider')
            ->willReturn(null)
        ;

        $this->expectException(ProviderNotAvailableException::class);
        $this->expectExceptionMessage('没有可用的身份证验证提供商');

        $this->service->verifyIdCardTwoElements($name, $idCard);
    }

    public function testVerifyCarrierThreeElements(): void
    {
        $name = '张三';
        $idCard = '110101199001011234';
        $mobile = '13800138000';

        // Mock格式验证
        $this->validationService->expects($this->once())
            ->method('validateIdCardFormat')
            ->with($idCard)
            ->willReturn(true)
        ;
        $this->validationService->expects($this->once())
            ->method('validateMobileFormat')
            ->with($mobile)
            ->willReturn(true)
        ;

        // Mock提供商选择和执行
        $provider = $this->createMock(AuthenticationProvider::class);
        $this->providerService->method('selectBestProvider')->willReturn($provider);

        $authResult = $this->createMock(AuthenticationResult::class);
        $this->providerService->expects($this->once())
            ->method('executeVerification')
            ->with($provider, ['name' => $name, 'id_card' => $idCard, 'mobile' => $mobile])
            ->willReturn($authResult)
        ;

        $result = $this->service->verifyCarrierThreeElements($name, $idCard, $mobile);

        $this->assertSame($authResult, $result);
    }

    public function testVerifyCarrierThreeElementsWithInvalidMobile(): void
    {
        $name = '张三';
        $idCard = '110101199001011234';
        $mobile = 'invalid-mobile';

        $this->validationService->method('validateIdCardFormat')->willReturn(true);
        $this->validationService->expects($this->once())
            ->method('validateMobileFormat')
            ->with($mobile)
            ->willReturn(false)
        ;

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('手机号码格式不正确');

        $this->service->verifyCarrierThreeElements($name, $idCard, $mobile);
    }

    public function testVerifyBankCardThreeElements(): void
    {
        $name = '张三';
        $idCard = '110101199001011234';
        $bankCard = '6222021234567894';

        // Mock格式验证
        $this->validationService->method('validateIdCardFormat')->willReturn(true);
        $this->validationService->expects($this->once())
            ->method('validateBankCardFormat')
            ->with($bankCard)
            ->willReturn(true)
        ;

        // Mock提供商选择和执行
        $provider = $this->createMock(AuthenticationProvider::class);
        $this->providerService->method('selectBestProvider')->willReturn($provider);

        $authResult = $this->createMock(AuthenticationResult::class);
        $this->providerService->expects($this->once())
            ->method('executeVerification')
            ->with($provider, ['name' => $name, 'id_card' => $idCard, 'bank_card' => $bankCard])
            ->willReturn($authResult)
        ;

        $result = $this->service->verifyBankCardThreeElements($name, $idCard, $bankCard);

        $this->assertSame($authResult, $result);
    }

    public function testVerifyBankCardThreeElementsWithInvalidBankCard(): void
    {
        $name = '张三';
        $idCard = '110101199001011234';
        $bankCard = 'invalid-bank-card';

        $this->validationService->method('validateIdCardFormat')->willReturn(true);
        $this->validationService->expects($this->once())
            ->method('validateBankCardFormat')
            ->with($bankCard)
            ->willReturn(false)
        ;

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('银行卡号格式不正确');

        $this->service->verifyBankCardThreeElements($name, $idCard, $bankCard);
    }

    public function testVerifyBankCardFourElements(): void
    {
        $name = '张三';
        $idCard = '110101199001011234';
        $bankCard = '6222021234567894';
        $mobile = '13800138000';

        // Mock格式验证
        $this->validationService->method('validateIdCardFormat')->willReturn(true);
        $this->validationService->method('validateBankCardFormat')->willReturn(true);
        $this->validationService->method('validateMobileFormat')->willReturn(true);

        // Mock提供商选择和执行
        $provider = $this->createMock(AuthenticationProvider::class);
        $this->providerService->method('selectBestProvider')->willReturn($provider);

        $authResult = $this->createMock(AuthenticationResult::class);
        $this->providerService->expects($this->once())
            ->method('executeVerification')
            ->with($provider, [
                'name' => $name,
                'id_card' => $idCard,
                'bank_card' => $bankCard,
                'mobile' => $mobile,
            ])
            ->willReturn($authResult)
        ;

        $result = $this->service->verifyBankCardFourElements($name, $idCard, $bankCard, $mobile);

        $this->assertSame($authResult, $result);
    }

    public function testPerformLivenessDetection(): void
    {
        $image = $this->createMockUploadedFile('image.jpg', 'image/jpeg', 1024000);

        // Mock提供商选择和执行
        $provider = $this->createMock(AuthenticationProvider::class);
        $this->providerService->expects($this->once())
            ->method('selectBestProvider')
            ->with(AuthenticationMethod::LIVENESS_DETECTION)
            ->willReturn($provider)
        ;

        $authResult = $this->createMock(AuthenticationResult::class);
        $this->providerService->expects($this->once())
            ->method('executeVerification')
            ->with($provider, ['image' => $image])
            ->willReturn($authResult)
        ;

        $result = $this->service->performLivenessDetection($image);

        $this->assertSame($authResult, $result);
    }

    public function testPerformLivenessDetectionWithUnsupportedFormat(): void
    {
        $image = $this->createMockUploadedFile('image.gif', 'image/gif', 1024000);

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('不支持的图片格式');

        $this->service->performLivenessDetection($image);
    }

    public function testPerformLivenessDetectionWithOversizedFile(): void
    {
        $image = $this->createMockUploadedFile('image.jpg', 'image/jpeg', 6 * 1024 * 1024);

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('图片文件过大');

        $this->service->performLivenessDetection($image);
    }

    private function createMockUploadedFile(
        string $originalName,
        string $mimeType,
        int $size,
    ): MockObject&UploadedFile {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn($originalName);
        $file->method('getMimeType')->willReturn($mimeType);
        $file->method('getSize')->willReturn($size);

        return $file;
    }
}
