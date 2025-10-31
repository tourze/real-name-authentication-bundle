<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;

/**
 * 实名认证实体测试
 *
 * @internal
 */
#[CoversClass(RealNameAuthentication::class)]
final class RealNameAuthenticationTest extends AbstractEntityTestCase
{
    private UserInterface&MockObject $mockUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockUser->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('test_user')
        ;
    }

    /**
     * 测试实体创建时的默认值设置
     */
    public function testEntityCreationWithDefaults(): void
    {
        $authentication = new RealNameAuthentication();

        // 验证默认值
        $this->assertNotEmpty($authentication->getId());
        $this->assertEquals(AuthenticationStatus::PENDING, $authentication->getStatus());
        $this->assertTrue($authentication->isValid());
        // 时间戳字段使用TimestampableAware trait，在构造函数中为null
        // 实际的时间戳会在持久化时由Doctrine监听器设置
        $this->assertNull($authentication->getCreateTime());
        $this->assertNull($authentication->getUpdateTime());
        $this->assertNull($authentication->getExpireTime());
        $this->assertNull($authentication->getVerificationResult());
        $this->assertNull($authentication->getProviderResponse());
        $this->assertNull($authentication->getReason());
    }

    /**
     * 测试设置和获取用户
     */
    public function testSetAndGetUser(): void
    {
        $authentication = new RealNameAuthentication();
        $authentication->setUser($this->mockUser);

        $this->assertEquals($this->mockUser, $authentication->getUser());
        $this->assertEquals('test_user', $authentication->getUserIdentifier());
    }

    /**
     * 测试设置和获取认证类型
     */
    public function testSetAndGetType(): void
    {
        $authentication = new RealNameAuthentication();
        $authentication->setType(AuthenticationType::PERSONAL);

        $this->assertEquals(AuthenticationType::PERSONAL, $authentication->getType());
    }

    /**
     * 测试设置和获取认证方式
     */
    public function testSetAndGetMethod(): void
    {
        $authentication = new RealNameAuthentication();
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);

        $this->assertEquals(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $authentication->getMethod());
    }

    /**
     * 测试状态更新方法
     */
    public function testUpdateStatus(): void
    {
        $authentication = new RealNameAuthentication();

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
        // 时间戳由TimestampableAware trait管理，在持久化时自动更新，不在updateStatus方法中更新
    }

    /**
     * 测试过期判断逻辑
     */
    public function testIsExpired(): void
    {
        $authentication = new RealNameAuthentication();

        // 没有设置过期时间时不过期
        $this->assertFalse($authentication->isExpired());

        // 设置未来时间
        $futureTime = new \DateTimeImmutable('+1 year');
        $authentication->setExpireTime($futureTime);
        $this->assertFalse($authentication->isExpired());

        // 设置过去时间
        $pastTime = new \DateTimeImmutable('-1 day');
        $authentication->setExpireTime($pastTime);
        $this->assertTrue($authentication->isExpired());
    }

    /**
     * 测试审核通过判断逻辑
     */
    public function testIsApproved(): void
    {
        $authentication = new RealNameAuthentication();
        $authentication->setStatus(AuthenticationStatus::APPROVED);

        // 未设置过期时间的已通过认证
        $this->assertTrue($authentication->isApproved());

        // 设置未来过期时间的已通过认证
        $futureTime = new \DateTimeImmutable('+1 year');
        $authentication->setExpireTime($futureTime);
        $this->assertTrue($authentication->isApproved());

        // 设置过去过期时间的已通过认证（已过期）
        $pastTime = new \DateTimeImmutable('-1 day');
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
    public function testSetAndGetSubmittedData(): void
    {
        $authentication = new RealNameAuthentication();
        $data = ['name' => '张三', 'idCard' => '11010119900101100X'];

        $authentication->setSubmittedData($data);
        $this->assertEquals($data, $authentication->getSubmittedData());
    }

    /**
     * 测试设置和获取验证结果
     */
    public function testSetAndGetVerificationResult(): void
    {
        $authentication = new RealNameAuthentication();
        $result = ['passed' => true, 'confidence' => 0.98];

        $authentication->setVerificationResult($result);
        $this->assertEquals($result, $authentication->getVerificationResult());
    }

    /**
     * 测试设置和获取提供商响应
     */
    public function testSetAndGetProviderResponse(): void
    {
        $authentication = new RealNameAuthentication();
        $response = ['code' => '0000', 'message' => 'success', 'data' => []];

        $authentication->setProviderResponse($response);
        $this->assertEquals($response, $authentication->getProviderResponse());
    }

    /**
     * 测试设置和获取拒绝原因
     */
    public function testSetAndGetReason(): void
    {
        $authentication = new RealNameAuthentication();
        $reason = '身份证信息不匹配';

        $authentication->setReason($reason);
        $this->assertEquals($reason, $authentication->getReason());
    }

    /**
     * 测试设置和获取过期时间
     */
    public function testSetAndGetExpireTime(): void
    {
        $authentication = new RealNameAuthentication();
        $expireTime = new \DateTimeImmutable('+1 year');

        $authentication->setExpireTime($expireTime);
        $this->assertEquals($expireTime, $authentication->getExpireTime());
    }

    /**
     * 测试有效性设置
     */
    public function testSetAndGetValid(): void
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
    public function testToString(): void
    {
        $authentication = new RealNameAuthentication();
        $authentication->setUser($this->mockUser);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setStatus(AuthenticationStatus::PENDING);

        $expected = '个人认证-身份证二要素(待审核)';
        $this->assertEquals($expected, (string) $authentication);
    }

    /**
     * 创建被测实体的实例
     */
    protected function createEntity(): object
    {
        $entity = new RealNameAuthentication();
        $entity->setUser($this->mockUser);
        $entity->setType(AuthenticationType::PERSONAL);
        $entity->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);

        return $entity;
    }

    /**
     * 提供属性及其样本值的 Data Provider
     *
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'type' => ['type', AuthenticationType::PERSONAL];
        yield 'status' => ['status', AuthenticationStatus::APPROVED];
        yield 'method' => ['method', AuthenticationMethod::ID_CARD_TWO_ELEMENTS];
        yield 'submittedData' => ['submittedData', ['name' => '张三', 'idCard' => '11010119900101100X']];
        yield 'verificationResult' => ['verificationResult', ['passed' => true, 'confidence' => 0.95]];
        yield 'providerResponse' => ['providerResponse', ['code' => '0000', 'message' => 'success']];
        yield 'reason' => ['reason', '认证通过'];
        yield 'expireTime' => ['expireTime', new \DateTimeImmutable('+1 year')];
        yield 'valid' => ['valid', false];
    }

    /**
     * 测试时间戳由TimestampableAware trait管理
     */
    public function testTimestampManagedByTrait(): void
    {
        $authentication = new RealNameAuthentication();

        // 使用TimestampableAware trait时，时间戳在构造函数中为null
        $this->assertNull($authentication->getCreateTime());
        $this->assertNull($authentication->getUpdateTime());

        // setter方法不会自动更新时间戳，时间戳由Doctrine监听器在持久化时设置
        $authentication->setStatus(AuthenticationStatus::PROCESSING);
        $this->assertNull($authentication->getUpdateTime());

        $authentication->setMethod(AuthenticationMethod::CARRIER_THREE_ELEMENTS);
        $this->assertNull($authentication->getUpdateTime());
    }
}
