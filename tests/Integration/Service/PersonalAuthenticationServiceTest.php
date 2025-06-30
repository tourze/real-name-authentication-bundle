<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;
use Tourze\RealNameAuthenticationBundle\Repository\AuthenticationProviderRepository;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;
use Tourze\RealNameAuthenticationBundle\Tests\Fixtures\TestUser;
use Tourze\RealNameAuthenticationBundle\Tests\Integration\IntegrationTestCase;
use Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService
 */
class PersonalAuthenticationServiceTest extends IntegrationTestCase
{
    private PersonalAuthenticationService $service;
    private EntityManagerInterface $entityManager;
    private RealNameAuthenticationRepository $realNameAuthenticationRepository;
    private AuthenticationProviderRepository $authenticationProviderRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = $this->getService(PersonalAuthenticationService::class);
        $this->entityManager = $this->getService('doctrine.orm.entity_manager');
        $this->realNameAuthenticationRepository = $this->getService(RealNameAuthenticationRepository::class);
        $this->authenticationProviderRepository = $this->getService(AuthenticationProviderRepository::class);
        
        // 创建测试数据
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // 创建测试用户
        $user = new TestUser('test_user_1');
        $this->entityManager->persist($user);
        
        // 创建认证提供商
        $provider = new AuthenticationProvider();
        $provider->setName('测试提供商');
        $provider->setCode('test_provider');
        $provider->setType(ProviderType::GOVERNMENT);
        $provider->setActive(true);
        $provider->setApiEndpoint('https://api.test.com');
        $provider->setSupportedMethods([
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value,
        ]);
        $provider->setConfig([
            'api_key' => 'test_key',
            'api_secret' => 'test_secret',
        ]);
        $this->entityManager->persist($provider);
        
        // 创建实名认证记录
        $authentication = new RealNameAuthentication();
        $authentication->setUser($user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setStatus(AuthenticationStatus::PENDING);
        $authentication->setSubmittedData([
            'real_name' => '张三',
            'id_card_number' => '110101199001011234',
        ]);
        $this->entityManager->persist($authentication);
        
        $this->entityManager->flush();
    }

    public function testSubmitAuthentication(): void
    {
        // 获取测试用户
        $user = new TestUser('new_test_user');
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // 创建认证DTO
        $dto = new PersonalAuthDTO(
            $user,
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            '李四',
            '110101199001012345'
        );
        
        // 执行认证
        $result = $this->service->submitAuthentication($dto);
        
        // 验证结果
        $this->assertInstanceOf(RealNameAuthentication::class, $result);
        $this->assertEquals(AuthenticationType::PERSONAL, $result->getType());
        $this->assertEquals(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $result->getMethod());
        $this->assertSame($user, $result->getUser());
        $this->assertContains($result->getStatus(), [
            AuthenticationStatus::PENDING,
            AuthenticationStatus::PROCESSING,
        ]);
    }

    public function testVerifyIdCardTwoElements(): void
    {
        // 执行验证
        $result = $this->service->verifyIdCardTwoElements('张三', '110101199001011234');
        
        // 验证结果
        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertNotNull($result->getProvider());
    }

    public function testGetAuthenticationHistory(): void
    {
        // 获取测试用户
        $user = $this->entityManager->createQuery(
            'SELECT u FROM ' . TestUser::class . ' u WHERE u.username = :username'
        )->setParameter('username', 'test_user_1')->getSingleResult();
        $this->assertNotNull($user);
        
        // 获取认证历史
        $history = $this->service->getAuthenticationHistory($user);
        
        // 验证结果
        $this->assertGreaterThanOrEqual(1, count($history));
        
        foreach ($history as $auth) {
            $this->assertInstanceOf(RealNameAuthentication::class, $auth);
            $this->assertSame($user, $auth->getUser());
        }
    }

    public function testCheckAuthenticationStatus(): void
    {
        // 获取测试认证记录
        $authentication = $this->realNameAuthenticationRepository
            ->findOneBy(['status' => AuthenticationStatus::PENDING]);
        $this->assertNotNull($authentication);
        
        // 检查认证状态
        $result = $this->service->checkAuthenticationStatus($authentication->getId());
        
        // 验证结果
        $this->assertInstanceOf(RealNameAuthentication::class, $result);
        $this->assertEquals($authentication->getId(), $result->getId());
        $this->assertEquals(AuthenticationStatus::PENDING, $result->getStatus());
    }

    public function testVerifyCarrierThreeElements(): void
    {
        // 创建运营商提供商
        $provider = new AuthenticationProvider();
        $provider->setName('运营商提供商');
        $provider->setCode('carrier_provider');
        $provider->setType(ProviderType::CARRIER);
        $provider->setActive(true);
        $provider->setApiEndpoint('https://api.carrier.com');
        $provider->setSupportedMethods([
            AuthenticationMethod::CARRIER_THREE_ELEMENTS->value,
        ]);
        $provider->setConfig(['api_key' => 'carrier_key']);
        $this->entityManager->persist($provider);
        $this->entityManager->flush();
        
        // 执行验证
        $result = $this->service->verifyCarrierThreeElements('王五', '110101199001013456', '13812345678');
        
        // 验证结果
        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertNotNull($result->getProvider());
    }

    public function testVerifyBankCardThreeElements(): void
    {
        // 创建银行提供商
        $provider = new AuthenticationProvider();
        $provider->setName('银行提供商');
        $provider->setCode('bank_provider');
        $provider->setType(ProviderType::BANK_UNION);
        $provider->setActive(true);
        $provider->setApiEndpoint('https://api.bank.com');
        $provider->setSupportedMethods([
            AuthenticationMethod::BANK_CARD_THREE_ELEMENTS->value,
        ]);
        $provider->setConfig(['api_key' => 'bank_key']);
        $this->entityManager->persist($provider);
        $this->entityManager->flush();
        
        // 执行验证
        $result = $this->service->verifyBankCardThreeElements('赵六', '110101199001014567', '6222021234567890123');
        
        // 验证结果
        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertNotNull($result->getProvider());
    }

    public function testVerifyBankCardFourElements(): void
    {
        // 创建银行提供商
        $provider = new AuthenticationProvider();
        $provider->setName('银行四要素提供商');
        $provider->setCode('bank_four_provider');
        $provider->setType(ProviderType::BANK_UNION);
        $provider->setActive(true);
        $provider->setApiEndpoint('https://api.bank4.com');
        $provider->setSupportedMethods([
            AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS->value,
        ]);
        $provider->setConfig(['api_key' => 'bank4_key']);
        $this->entityManager->persist($provider);
        $this->entityManager->flush();
        
        // 执行验证
        $result = $this->service->verifyBankCardFourElements(
            '钱七',
            '110101199001015678',
            '6222021234567890456',
            '13987654321'
        );
        
        // 验证结果
        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertNotNull($result->getProvider());
    }

    public function testSubmitAuthenticationWithExistingAuth(): void
    {
        // 获取已有认证的用户
        $user = $this->entityManager->createQuery(
            'SELECT u FROM ' . TestUser::class . ' u WHERE u.username = :username'
        )->setParameter('username', 'test_user_1')->getSingleResult();
        $this->assertNotNull($user);
        
        // 更新现有认证状态为已认证
        $existingAuth = $this->realNameAuthenticationRepository
            ->findOneBy(['user' => $user]);
        $existingAuth->setStatus(AuthenticationStatus::APPROVED);
        $this->entityManager->flush();
        
        // 创建新的认证DTO
        $dto = new PersonalAuthDTO(
            $user,
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            '张三新',
            '110101199001016789'
        );
        
        // 预期抛出异常
        $this->expectException(\Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException::class);
        $this->expectExceptionMessage('用户已有有效的个人认证记录');
        
        // 执行认证
        $this->service->submitAuthentication($dto);
    }

    public function testInvalidIdCardFormat(): void
    {
        // 预期抛出异常
        $this->expectException(\Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('身份证号码格式不正确');
        
        // 使用无效的身份证号码
        $this->service->verifyIdCardTwoElements('孙八', '123');
    }

    public function testNoAvailableProvider(): void
    {
        // 禁用所有提供商
        $providers = $this->authenticationProviderRepository->findAll();
        foreach ($providers as $provider) {
            $provider->setActive(false);
        }
        $this->entityManager->flush();
        
        // 预期抛出异常
        $this->expectException(\Tourze\RealNameAuthenticationBundle\Exception\ProviderNotAvailableException::class);
        $this->expectExceptionMessage('没有可用的身份证验证提供商');
        
        // 执行验证
        $this->service->verifyIdCardTwoElements('周九', '110101199001017890');
    }
}