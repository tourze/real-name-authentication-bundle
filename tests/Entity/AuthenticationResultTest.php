<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;

/**
 * 认证结果实体测试
 *
 * @internal
 */
#[CoversClass(AuthenticationResult::class)]
final class AuthenticationResultTest extends AbstractEntityTestCase
{
    private UserInterface&MockObject $mockUser;

    private RealNameAuthentication $authentication;

    private AuthenticationProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockUser->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('test_user')
        ;

        // 创建认证记录
        $this->authentication = new RealNameAuthentication();
        $this->authentication->setUser($this->mockUser);
        $this->authentication->setType(AuthenticationType::PERSONAL);
        $this->authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $this->authentication->setStatus(AuthenticationStatus::PENDING);

        // 创建提供商
        $this->provider = new AuthenticationProvider();
        $this->provider->setName('测试提供商');
        $this->provider->setCode('test_provider');
        $this->provider->setType(ProviderType::GOVERNMENT);
        $this->provider->setApiEndpoint('https://api.example.com');
    }

    /**
     * 测试实体创建和属性设置
     */
    public function testEntityCreationAndProperties(): void
    {
        $result = new AuthenticationResult();

        // 设置必需的属性避免未初始化错误
        $result->setAuthentication($this->authentication);
        $result->setProvider($this->provider);
        $result->setRequestId('TEST_REQ_001');
        $result->setSuccess(false);
        $result->setProcessingTime(0);

        // 验证默认值
        $this->assertNotEmpty($result->getId());
        $this->assertFalse($result->isSuccess());
        $this->assertNull($result->getConfidence());
        $this->assertEquals([], $result->getResponseData());
        $this->assertNull($result->getErrorCode());
        $this->assertNull($result->getErrorMessage());
        $this->assertEquals(0, $result->getProcessingTime());
        $this->assertTrue($result->isValid());
        // 时间戳会在持久化时由 Doctrine 自动设置
        $this->assertNull($result->getCreateTime());
    }

    /**
     * 测试设置和获取认证记录
     */
    public function testSetAndGetAuthentication(): void
    {
        $result = new AuthenticationResult();
        $result->setAuthentication($this->authentication);

        $this->assertEquals($this->authentication, $result->getAuthentication());
    }

    /**
     * 测试设置和获取提供商
     */
    public function testSetAndGetProvider(): void
    {
        $result = new AuthenticationResult();
        $result->setProvider($this->provider);

        $this->assertEquals($this->provider, $result->getProvider());
    }

    /**
     * 测试设置和获取请求ID
     */
    public function testSetAndGetRequestId(): void
    {
        $result = new AuthenticationResult();
        $requestId = 'REQ_' . uniqid();

        $result->setRequestId($requestId);
        $this->assertEquals($requestId, $result->getRequestId());
    }

    /**
     * 测试成功结果
     */
    public function testSuccessResult(): void
    {
        $result = new AuthenticationResult();
        $result->setAuthentication($this->authentication);
        $result->setProvider($this->provider);
        $result->setRequestId('REQ_SUCCESS_001');
        $result->setSuccess(true);
        $result->setConfidence(0.95);
        $result->setResponseData([
            'match_score' => 95.5,
            'verification_time' => '2024-01-27 10:30:00',
            'status' => 'verified',
        ]);
        $result->setProcessingTime(1500);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(0.95, $result->getConfidence());
        $this->assertEquals(1500, $result->getProcessingTime());
        $this->assertNull($result->getErrorCode());
        $this->assertNull($result->getErrorMessage());

        $responseData = $result->getResponseData();
        $this->assertEquals(95.5, $responseData['match_score']);
        $this->assertEquals('verified', $responseData['status']);
    }

    /**
     * 测试失败结果
     */
    public function testFailureResult(): void
    {
        $result = new AuthenticationResult();
        $result->setAuthentication($this->authentication);
        $result->setProvider($this->provider);
        $result->setRequestId('REQ_FAILURE_001');
        $result->setSuccess(false);
        $result->setConfidence(0.1);
        $result->setResponseData([
            'error_details' => '身份证信息不匹配',
            'error_time' => '2024-01-27 10:35:00',
        ]);
        $result->setErrorCode('ID_MISMATCH');
        $result->setErrorMessage('身份证信息与姓名不匹配');
        $result->setProcessingTime(800);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals(0.1, $result->getConfidence());
        $this->assertEquals('ID_MISMATCH', $result->getErrorCode());
        $this->assertEquals('身份证信息与姓名不匹配', $result->getErrorMessage());
        $this->assertEquals(800, $result->getProcessingTime());

        $responseData = $result->getResponseData();
        $this->assertEquals('身份证信息不匹配', $responseData['error_details']);
    }

    /**
     * 测试置信度设置
     */
    public function testConfidenceSetting(): void
    {
        $result = new AuthenticationResult();

        // 测试有效的置信度值
        $result->setConfidence(0.0);
        $this->assertEquals(0.0, $result->getConfidence());

        $result->setConfidence(0.5);
        $this->assertEquals(0.5, $result->getConfidence());

        $result->setConfidence(1.0);
        $this->assertEquals(1.0, $result->getConfidence());

        $result->setConfidence(0.999);
        $this->assertEquals(0.999, $result->getConfidence());

        // 测试null值
        $result->setConfidence(null);
        $this->assertNull($result->getConfidence());
    }

    /**
     * 测试响应数据设置
     */
    public function testResponseDataSetting(): void
    {
        $result = new AuthenticationResult();

        $responseData = [
            'code' => '0000',
            'message' => 'success',
            'data' => [
                'name_match' => true,
                'id_card_match' => true,
                'score' => 98.5,
            ],
            'timestamp' => '2024-01-27T10:30:00Z',
        ];

        $result->setResponseData($responseData);
        $this->assertEquals($responseData, $result->getResponseData());
        $fetchedData = $result->getResponseData();
        /** @var array<string, mixed> $fetchedData */
        $this->assertEquals('0000', $fetchedData['code']);
        $dataSection = $fetchedData['data'];
        $this->assertIsArray($dataSection);
        /** @var array<string, mixed> $dataSection */
        $this->assertTrue($dataSection['name_match']);
    }

    /**
     * 测试处理时间设置
     */
    public function testProcessingTimeSetting(): void
    {
        $result = new AuthenticationResult();

        $result->setProcessingTime(1000);
        $this->assertEquals(1000, $result->getProcessingTime());

        $result->setProcessingTime(0);
        $this->assertEquals(0, $result->getProcessingTime());

        $result->setProcessingTime(30000);
        $this->assertEquals(30000, $result->getProcessingTime());
    }

    /**
     * 测试错误信息设置
     */
    public function testErrorInformationSetting(): void
    {
        $result = new AuthenticationResult();

        $result->setErrorCode('TIMEOUT');
        $result->setErrorMessage('请求超时，请稍后重试');

        $this->assertEquals('TIMEOUT', $result->getErrorCode());
        $this->assertEquals('请求超时，请稍后重试', $result->getErrorMessage());

        // 测试清空错误信息
        $result->setErrorCode(null);
        $result->setErrorMessage(null);

        $this->assertNull($result->getErrorCode());
        $this->assertNull($result->getErrorMessage());
    }

    /**
     * 测试有效性设置
     */
    public function testValiditySetting(): void
    {
        $result = new AuthenticationResult();

        // 默认是有效的
        $this->assertTrue($result->isValid());

        $result->setValid(false);
        $this->assertFalse($result->isValid());

        $result->setValid(true);
        $this->assertTrue($result->isValid());
    }

    /**
     * 测试toString方法
     */
    public function testToString(): void
    {
        $result = new AuthenticationResult();
        $result->setAuthentication($this->authentication);
        $result->setProvider($this->provider);
        $result->setRequestId('REQ_TEST_001');
        $result->setSuccess(true);

        $expected = '测试提供商 - 成功 (REQ_TEST_001)';
        $this->assertEquals($expected, (string) $result);

        // 测试失败情况
        $result->setSuccess(false);
        $expected = '测试提供商 - 失败 (REQ_TEST_001)';
        $this->assertEquals($expected, (string) $result);
    }

    /**
     * 测试关联关系
     */
    public function testRelationshipAssociations(): void
    {
        $result = new AuthenticationResult();
        $result->setAuthentication($this->authentication);
        $result->setProvider($this->provider);

        // 验证关联关系
        $this->assertInstanceOf(RealNameAuthentication::class, $result->getAuthentication());
        $this->assertInstanceOf(AuthenticationProvider::class, $result->getProvider());

        // 验证关联对象的属性
        $this->assertEquals('test_user', $result->getAuthentication()->getUserIdentifier());
        $this->assertEquals('测试提供商', $result->getProvider()->getName());
    }

    /**
     * 创建被测实体的实例
     */
    protected function createEntity(): object
    {
        $entity = new AuthenticationResult();
        $entity->setAuthentication($this->authentication);
        $entity->setProvider($this->provider);
        $entity->setRequestId('TEST_REQ_001');
        $entity->setSuccess(false);
        $entity->setProcessingTime(0);

        return $entity;
    }

    /**
     * 提供属性及其样本值的 Data Provider
     *
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        // 注意：由于需要复杂的 setUp，这里只测试简单属性
        yield 'requestId' => ['requestId', 'TEST_REQ_002'];
        yield 'success' => ['success', true];
        yield 'confidence' => ['confidence', 0.95];
        yield 'responseData' => ['responseData', ['code' => '0000', 'message' => 'success']];
        yield 'errorCode' => ['errorCode', 'ERROR_CODE'];
        yield 'errorMessage' => ['errorMessage', '错误消息'];
        yield 'processingTime' => ['processingTime', 1000];
        yield 'valid' => ['valid', false];
    }

    /**
     * 测试完整的认证结果场景
     */
    public function testCompleteAuthenticationResultScenario(): void
    {
        $result = new AuthenticationResult();

        // 设置完整的认证结果
        $result->setAuthentication($this->authentication);
        $result->setProvider($this->provider);
        $result->setRequestId('REQ_COMPLETE_001');
        $result->setSuccess(true);
        $result->setConfidence(0.987);
        $result->setResponseData([
            'result_code' => '0000',
            'result_message' => '身份认证成功',
            'verification_details' => [
                'name_verified' => true,
                'id_card_verified' => true,
                'photo_verified' => true,
            ],
            'api_response_time' => '2024-01-27T10:30:15.123Z',
            'provider_reference' => 'GOV_REF_12345',
        ]);
        $result->setProcessingTime(2300);

        // 验证所有属性
        $this->assertEquals($this->authentication, $result->getAuthentication());
        $this->assertEquals($this->provider, $result->getProvider());
        $this->assertEquals('REQ_COMPLETE_001', $result->getRequestId());
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(0.987, $result->getConfidence());
        $this->assertEquals(2300, $result->getProcessingTime());
        $this->assertTrue($result->isValid());

        // 验证响应数据
        $responseData = $result->getResponseData();
        /** @var array<string, mixed> $responseData */
        $this->assertEquals('0000', $responseData['result_code']);
        $this->assertEquals('身份认证成功', $responseData['result_message']);
        $verificationDetails = $responseData['verification_details'];
        $this->assertIsArray($verificationDetails);
        /** @var array<string, mixed> $verificationDetails */
        $this->assertTrue($verificationDetails['name_verified']);
        $this->assertEquals('GOV_REF_12345', $responseData['provider_reference']);
    }
}
