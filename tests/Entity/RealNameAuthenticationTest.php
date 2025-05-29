<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;

/**
 * 实名认证实体测试
 */
class RealNameAuthenticationTest extends TestCase
{
    private UserInterface&MockObject $mockUser;

    protected function setUp(): void
    {
        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockUser->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('test_user');
    }

    /**
     * 测试实体创建时的默认值设置
     */
    public function test_entity_creation_with_defaults(): void
    {
        $authentication = new RealNameAuthentication();

        // 验证默认值
        $this->assertIsString($authentication->getId());
        $this->assertNotEmpty($authentication->getId());
        $this->assertEquals(AuthenticationStatus::PENDING, $authentication->getStatus());
        $this->assertTrue($authentication->isValid());
        $this->assertInstanceOf(DateTimeImmutable::class, $authentication->getCreateTime());
        $this->assertInstanceOf(DateTimeImmutable::class, $authentication->getUpdateTime());
        $this->assertNull($authentication->getExpireTime());
        $this->assertNull($authentication->getVerificationResult());
        $this->assertNull($authentication->getProviderResponse());
        $this->assertNull($authentication->getReason());
    }

    /**
     * 测试设置和获取用户
     */
    public function test_set_and_get_user(): void
    {
        $authentication = new RealNameAuthentication();
        $authentication->setUser($this->mockUser);

        $this->assertEquals($this->mockUser, $authentication->getUser());
        $this->assertEquals('test_user', $authentication->getUserIdentifier());
    }

    /**
     * 测试设置和获取认证类型
     */
    public function test_set_and_get_type(): void
    {
        $authentication = new RealNameAuthentication();
        $authentication->setType(AuthenticationType::PERSONAL);

        $this->assertEquals(AuthenticationType::PERSONAL, $authentication->getType());
    }

    /**
     * 测试设置和获取认证方式
     */
    public function test_set_and_get_method(): void
    {
        $authentication = new RealNameAuthentication();
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);

        $this->assertEquals(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $authentication->getMethod());
    }

    /**
     * 测试状态更新方法
     */
    public function test_update_status(): void
    {
        $authentication = new RealNameAuthentication();
        $originalUpdateTime = $authentication->getUpdateTime();

        // 等待一毫秒确保时间戳不同
        usleep(1000);

        $verificationResult = ['result' => 'passed', 'confidence' => 0.95];
        $providerResponse = ['code' => '0000', 'message' => 'success'];
        $reason = '审核通过';

        $authentication->updateStatus(
            AuthenticationStatus::APPROVED,
            $verificationResult,
            $providerResponse,
            $reason
        );

        $this->assertEquals(AuthenticationStatus::APPROVED, $authentication->getStatus());
        $this->assertEquals($verificationResult, $authentication->getVerificationResult());
        $this->assertEquals($providerResponse, $authentication->getProviderResponse());
        $this->assertEquals($reason, $authentication->getReason());
        $this->assertGreaterThan($originalUpdateTime, $authentication->getUpdateTime());
    }

    /**
     * 测试过期判断逻辑
     */
    public function test_is_expired(): void
    {
        $authentication = new RealNameAuthentication();

        // 没有设置过期时间时不过期
        $this->assertFalse($authentication->isExpired());

        // 设置未来时间
        $futureTime = new DateTimeImmutable('+1 year');
        $authentication->setExpireTime($futureTime);
        $this->assertFalse($authentication->isExpired());

        // 设置过去时间
        $pastTime = new DateTimeImmutable('-1 day');
        $authentication->setExpireTime($pastTime);
        $this->assertTrue($authentication->isExpired());
    }

    /**
     * 测试审核通过判断逻辑
     */
    public function test_is_approved(): void
    {
        $authentication = new RealNameAuthentication();
        $authentication->setStatus(AuthenticationStatus::APPROVED);

        // 未设置过期时间的已通过认证
        $this->assertTrue($authentication->isApproved());

        // 设置未来过期时间的已通过认证
        $futureTime = new DateTimeImmutable('+1 year');
        $authentication->setExpireTime($futureTime);
        $this->assertTrue($authentication->isApproved());

        // 设置过去过期时间的已通过认证（已过期）
        $pastTime = new DateTimeImmutable('-1 day');
        $authentication->setExpireTime($pastTime);
        $this->assertFalse($authentication->isApproved());

        // 非通过状态
        $authentication->setStatus(AuthenticationStatus::PENDING);
        $authentication->setExpireTime($futureTime);
        $this->assertFalse($authentication->isApproved());
    }

    /**
     * 测试设置和获取提交数据
     */
    public function test_set_and_get_submitted_data(): void
    {
        $authentication = new RealNameAuthentication();
        $data = ['name' => '张三', 'idCard' => '110101199001011234'];

        $authentication->setSubmittedData($data);
        $this->assertEquals($data, $authentication->getSubmittedData());
    }

    /**
     * 测试设置和获取验证结果
     */
    public function test_set_and_get_verification_result(): void
    {
        $authentication = new RealNameAuthentication();
        $result = ['passed' => true, 'confidence' => 0.98];

        $authentication->setVerificationResult($result);
        $this->assertEquals($result, $authentication->getVerificationResult());
    }

    /**
     * 测试设置和获取提供商响应
     */
    public function test_set_and_get_provider_response(): void
    {
        $authentication = new RealNameAuthentication();
        $response = ['code' => '0000', 'message' => 'success', 'data' => []];

        $authentication->setProviderResponse($response);
        $this->assertEquals($response, $authentication->getProviderResponse());
    }

    /**
     * 测试设置和获取拒绝原因
     */
    public function test_set_and_get_reason(): void
    {
        $authentication = new RealNameAuthentication();
        $reason = '身份证信息不匹配';

        $authentication->setReason($reason);
        $this->assertEquals($reason, $authentication->getReason());
    }

    /**
     * 测试设置和获取过期时间
     */
    public function test_set_and_get_expire_time(): void
    {
        $authentication = new RealNameAuthentication();
        $expireTime = new DateTimeImmutable('+1 year');

        $authentication->setExpireTime($expireTime);
        $this->assertEquals($expireTime, $authentication->getExpireTime());
    }

    /**
     * 测试有效性设置
     */
    public function test_set_and_get_valid(): void
    {
        $authentication = new RealNameAuthentication();

        // 默认是有效的
        $this->assertTrue($authentication->isValid());

        $authentication->setValid(false);
        $this->assertFalse($authentication->isValid());
    }

    /**
     * 测试toString方法
     */
    public function test_to_string(): void
    {
        $authentication = new RealNameAuthentication();
        $authentication->setUser($this->mockUser);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setStatus(AuthenticationStatus::PENDING);

        $expected = '个人认证-身份证二要素(待审核)';
        $this->assertEquals($expected, (string)$authentication);
    }

    /**
     * 测试审计字段设置
     */
    public function test_audit_fields(): void
    {
        $authentication = new RealNameAuthentication();

        $authentication->setCreatedBy('admin');
        $authentication->setUpdatedBy('admin2');
        $authentication->setCreatedFromIp('192.168.1.1');
        $authentication->setUpdatedFromIp('192.168.1.2');

        $this->assertEquals('admin', $authentication->getCreatedBy());
        $this->assertEquals('admin2', $authentication->getUpdatedBy());
        $this->assertEquals('192.168.1.1', $authentication->getCreatedFromIp());
        $this->assertEquals('192.168.1.2', $authentication->getUpdatedFromIp());
    }

    /**
     * 测试更新时间自动更新
     */
    public function test_update_time_auto_update(): void
    {
        $authentication = new RealNameAuthentication();
        $originalUpdateTime = $authentication->getUpdateTime();

        // 等待一毫秒确保时间戳不同
        usleep(1000);

        // 任何设置操作都应该更新时间戳
        $authentication->setStatus(AuthenticationStatus::PROCESSING);
        $this->assertGreaterThan($originalUpdateTime, $authentication->getUpdateTime());

        $newUpdateTime = $authentication->getUpdateTime();
        usleep(1000);

        $authentication->setMethod(AuthenticationMethod::CARRIER_THREE_ELEMENTS);
        $this->assertGreaterThan($newUpdateTime, $authentication->getUpdateTime());
    }
} 