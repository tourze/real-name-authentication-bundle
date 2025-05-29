<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Entity;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;

/**
 * 认证结果实体测试
 */
class AuthenticationResultTest extends TestCase
{
    private UserInterface&MockObject $mockUser;
    private RealNameAuthentication $authentication;
    private AuthenticationProvider $provider;

    protected function setUp(): void
    {
        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockUser->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('test_user');

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
    public function test_entity_creation_and_properties(): void
    {
        $result = new AuthenticationResult();
        
        // 设置必需的属性避免未初始化错误
        $result->setAuthentication($this->authentication);
        $result->setProvider($this->provider);
        $result->setRequestId('TEST_REQ_001');
        $result->setSuccess(false);
        $result->setProcessingTime(0);

        // 验证默认值
        $this->assertIsString($result->getId());
        $this->assertNotEmpty($result->getId());
        $this->assertFalse($result->isSuccess());
        $this->assertNull($result->getConfidence());
        $this->assertEquals([], $result->getResponseData());
        $this->assertNull($result->getErrorCode());
        $this->assertNull($result->getErrorMessage());
        $this->assertEquals(0, $result->getProcessingTime());
        $this->assertTrue($result->isValid());
        $this->assertNotNull($result->getCreateTime());
    }

    /**
     * 测试设置和获取认证记录
     */
    public function test_set_and_get_authentication(): void
    {
        $result = new AuthenticationResult();
        $result->setAuthentication($this->authentication);

        $this->assertEquals($this->authentication, $result->getAuthentication());
    }

    /**
     * 测试设置和获取提供商
     */
    public function test_set_and_get_provider(): void
    {
        $result = new AuthenticationResult();
        $result->setProvider($this->provider);

        $this->assertEquals($this->provider, $result->getProvider());
    }

    /**
     * 测试设置和获取请求ID
     */
    public function test_set_and_get_request_id(): void
    {
        $result = new AuthenticationResult();
        $requestId = 'REQ_' . uniqid();

        $result->setRequestId($requestId);
        $this->assertEquals($requestId, $result->getRequestId());
    }

    /**
     * 测试成功结果
     */
    public function test_success_result(): void
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
    public function test_failure_result(): void
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
    public function test_confidence_setting(): void
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
    public function test_response_data_setting(): void
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
        $this->assertEquals('0000', $result->getResponseData()['code']);
        $this->assertTrue($result->getResponseData()['data']['name_match']);
    }

    /**
     * 测试处理时间设置
     */
    public function test_processing_time_setting(): void
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
    public function test_error_information_setting(): void
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
    public function test_validity_setting(): void
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
    public function test_to_string(): void
    {
        $result = new AuthenticationResult();
        $result->setAuthentication($this->authentication);
        $result->setProvider($this->provider);
        $result->setRequestId('REQ_TEST_001');
        $result->setSuccess(true);

        $expected = '测试提供商 - 成功 (REQ_TEST_001)';
        $this->assertEquals($expected, (string)$result);

        // 测试失败情况
        $result->setSuccess(false);
        $expected = '测试提供商 - 失败 (REQ_TEST_001)';
        $this->assertEquals($expected, (string)$result);
    }

    /**
     * 测试关联关系
     */
    public function test_relationship_associations(): void
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
     * 测试完整的认证结果场景
     */
    public function test_complete_authentication_result_scenario(): void
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
        $this->assertEquals('0000', $responseData['result_code']);
        $this->assertEquals('身份认证成功', $responseData['result_message']);
        $this->assertTrue($responseData['verification_details']['name_verified']);
        $this->assertEquals('GOV_REF_12345', $responseData['provider_reference']);
    }
} 