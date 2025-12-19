<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;
use Tourze\RealNameAuthenticationBundle\Exception\ProviderNotAvailableException;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;
use Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO;

/**
 * @internal
 */
#[CoversClass(PersonalAuthenticationService::class)]
#[RunTestsInSeparateProcesses]
final class PersonalAuthenticationServiceTest extends AbstractIntegrationTestCase
{
    private PersonalAuthenticationService $service;

    protected function onSetUp(): void
    {
        // 从容器获取服务实例（集成测试模式，使用真实服务）
        $this->service = self::getService(PersonalAuthenticationService::class);
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(PersonalAuthenticationService::class, $this->service);
    }

    public function testSubmitAuthentication(): void
    {
        // 创建真实用户实体（使用不含保留字符的标识符）
        $user = $this->createNormalUser('test_user_submit', 'password');
        $dto = new PersonalAuthDTO(
            user: $user,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '张三',
            idCard: '110101199001012053'
        );

        // 执行提交
        $result = $this->service->submitAuthentication($dto);

        // 验证结果
        $this->assertInstanceOf(RealNameAuthentication::class, $result);
        $this->assertSame($user, $result->getUser());
        $this->assertSame(AuthenticationType::PERSONAL, $result->getType());
        $this->assertSame(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $result->getMethod());
        $this->assertSame(AuthenticationStatus::PENDING, $result->getStatus());
    }

    public function testSubmitAuthenticationWithValidationErrors(): void
    {
        // 使用真实用户实体
        $user = $this->createNormalUser('test_user_validation', 'password');
        $dto = new PersonalAuthDTO(
            user: $user,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '',
            idCard: 'invalid'
        );

        // 应该抛出验证异常
        $this->expectException(InvalidAuthenticationDataException::class);

        $this->service->submitAuthentication($dto);
    }

    public function testSubmitAuthenticationWithExistingApprovedAuth(): void
    {
        // 创建真实用户和现有认证
        $user = $this->createNormalUser('test_user_existing', 'password');

        // 先提交一个认证
        $dto1 = new PersonalAuthDTO(
            user: $user,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '张三',
            idCard: '110101199001012053'
        );
        $authentication = $this->service->submitAuthentication($dto1);

        // 手动将第一个认证设为已批准状态
        $entityManager = self::getEntityManager();
        $reflection = new \ReflectionClass($authentication);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setValue($authentication, AuthenticationStatus::APPROVED);
        $validProperty = $reflection->getProperty('valid');
        $validProperty->setValue($authentication, true);
        $entityManager->flush();

        // 再尝试提交另一个认证（应该因为已有有效认证而失败）
        $dto2 = new PersonalAuthDTO(
            user: $user,
            method: AuthenticationMethod::CARRIER_THREE_ELEMENTS,
            name: '张三',
            idCard: '110101199001012053',
            mobile: '13800138000'
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('用户已有有效的个人认证记录');

        $this->service->submitAuthentication($dto2);
    }

    public function testCheckAuthenticationStatus(): void
    {
        // 创建真实的认证
        $user = $this->createNormalUser('test_user_status', 'password');
        $dto = new PersonalAuthDTO(
            user: $user,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '张三',
            idCard: '110101199001012053'
        );
        $authentication = $this->service->submitAuthentication($dto);

        // 检查认证状态
        $result = $this->service->checkAuthenticationStatus($authentication->getId());

        $this->assertSame($authentication->getId(), $result->getId());
        $this->assertSame(AuthenticationStatus::PENDING, $result->getStatus());
    }

    public function testCheckAuthenticationStatusWithNonExistentId(): void
    {
        $authId = 'non-existent-id-' . uniqid();

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('认证记录不存在');

        $this->service->checkAuthenticationStatus($authId);
    }

    public function testVerifyIdCardTwoElements(): void
    {
        $name = '张三';
        $idCard = '110101199001012053';

        // 执行验证（会尝试调用真实的提供商）
        $result = $this->service->verifyIdCardTwoElements($name, $idCard);

        // 验证返回结果类型
        $this->assertInstanceOf(\Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult::class, $result);
    }

    public function testVerifyIdCardTwoElementsWithInvalidFormat(): void
    {
        $name = '张三';
        $idCard = 'invalid-id-card';

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('身份证号码格式不正确');

        $this->service->verifyIdCardTwoElements($name, $idCard);
    }

    public function testVerifyCarrierThreeElements(): void
    {
        $name = '张三';
        $idCard = '110101199001012053';
        $mobile = '13800138000';

        // 执行验证
        $result = $this->service->verifyCarrierThreeElements($name, $idCard, $mobile);

        // 验证返回结果类型
        $this->assertInstanceOf(\Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult::class, $result);
    }

    public function testVerifyCarrierThreeElementsWithInvalidMobile(): void
    {
        $name = '张三';
        $idCard = '110101199001012053';
        $mobile = 'invalid-mobile';

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('手机号码格式不正确');

        $this->service->verifyCarrierThreeElements($name, $idCard, $mobile);
    }

    public function testVerifyBankCardThreeElements(): void
    {
        $name = '张三';
        $idCard = '110101199001012053';
        $bankCard = '6222021234567894';

        // 执行验证
        $result = $this->service->verifyBankCardThreeElements($name, $idCard, $bankCard);

        // 验证返回结果类型
        $this->assertInstanceOf(\Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult::class, $result);
    }

    public function testVerifyBankCardThreeElementsWithInvalidBankCard(): void
    {
        $name = '张三';
        $idCard = '110101199001012053';
        $bankCard = 'invalid-bank-card';

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('银行卡号格式不正确');

        $this->service->verifyBankCardThreeElements($name, $idCard, $bankCard);
    }

    public function testVerifyBankCardFourElements(): void
    {
        $name = '张三';
        $idCard = '110101199001012053';
        $bankCard = '6222021234567894';
        $mobile = '13800138000';

        // 执行验证
        $result = $this->service->verifyBankCardFourElements($name, $idCard, $bankCard, $mobile);

        // 验证返回结果类型
        $this->assertInstanceOf(\Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult::class, $result);
    }

    public function testPerformLivenessDetection(): void
    {
        $image = $this->createRealUploadedFile('image.jpg', 'image/jpeg', 1024000);

        // 执行活体检测
        $result = $this->service->performLivenessDetection($image);

        // 验证返回结果类型
        $this->assertInstanceOf(\Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult::class, $result);
    }

    public function testPerformLivenessDetectionWithUnsupportedFormat(): void
    {
        $image = $this->createRealUploadedFile('image.gif', 'image/gif', 1024000);

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('不支持的图片格式');

        $this->service->performLivenessDetection($image);
    }

    public function testPerformLivenessDetectionWithOversizedFile(): void
    {
        $image = $this->createRealUploadedFile('image.jpg', 'image/jpeg', 6 * 1024 * 1024);

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('图片文件过大');

        $this->service->performLivenessDetection($image);
    }

    private function createRealUploadedFile(
        string $originalName,
        string $mimeType,
        int $size,
    ): UploadedFile {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image_');
        if (false === $tempFile) {
            throw new \RuntimeException('无法创建临时文件');
        }

        // 根据 MIME 类型创建有效的文件内容
        if ('image/jpeg' === $mimeType) {
            // 创建一个最小的有效 JPEG 文件
            $jpegHeader = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00";
            $jpegFooter = "\xFF\xD9";
            $content = $jpegHeader . str_repeat("\x00", max(0, $size - strlen($jpegHeader) - strlen($jpegFooter))) . $jpegFooter;
            file_put_contents($tempFile, $content);
        } elseif ('image/png' === $mimeType) {
            // 创建一个最小的有效 PNG 文件
            $pngHeader = "\x89PNG\x0D\x0A\x1A\x0A";
            $content = $pngHeader . str_repeat("\x00", max(0, $size - strlen($pngHeader)));
            file_put_contents($tempFile, $content);
        } elseif ('image/gif' === $mimeType) {
            // 创建一个有效的 GIF 文件头
            $gifHeader = "GIF89a\x01\x00\x01\x00\x80\x00\x00\xFF\xFF\xFF\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3B";
            $content = $gifHeader . str_repeat("\x00", max(0, $size - strlen($gifHeader)));
            file_put_contents($tempFile, $content);
        } else {
            // 其他类型，创建随机内容
            file_put_contents($tempFile, str_repeat('x', $size));
        }

        return new UploadedFile(
            $tempFile,
            $originalName,
            $mimeType,
            null,
            true
        );
    }
}
