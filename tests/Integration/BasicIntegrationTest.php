<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;
use Tourze\RealNameAuthenticationBundle\Tests\Fixtures\TestUser;

/**
 * 基础集成测试
 * 用于验证测试环境配置是否正确
 */
class BasicIntegrationTest extends IntegrationTestCase
{
    public function testKernelBoot(): void
    {
        $this->assertNotNull($this->kernel->getContainer());
        $this->assertTrue($this->kernel->getContainer()->has('doctrine.orm.entity_manager'));
    }

    public function testEntityManagerWorks(): void
    {
        $entityManager = $this->getService('doctrine.orm.entity_manager');
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
        
        // 测试用户实体映射
        $user = new TestUser('test_basic');
        $entityManager->persist($user);
        $entityManager->flush();
        
        $this->assertNotNull($user->getId());
        
        // 从数据库重新获取
        $entityManager->clear();
        $foundUser = $entityManager->find(TestUser::class, $user->getId());
        $this->assertNotNull($foundUser);
        $this->assertEquals('test_basic', $foundUser->getUsername());
    }

    public function testAuthenticationProviderEntity(): void
    {
        $entityManager = $this->getService('doctrine.orm.entity_manager');
        
        // 创建提供商
        $provider = new AuthenticationProvider();
        $provider->setName('测试提供商');
        $provider->setCode('test_provider');
        $provider->setType(ProviderType::GOVERNMENT);
        $provider->setApiEndpoint('https://api.test.com');
        $provider->setActive(true);
        $provider->setConfig(['api_key' => 'test']);
        
        $entityManager->persist($provider);
        $entityManager->flush();
        
        $this->assertNotNull($provider->getId());
        
        // 验证数据
        $entityManager->clear();
        $foundProvider = $entityManager->find(AuthenticationProvider::class, $provider->getId());
        $this->assertNotNull($foundProvider);
        $this->assertEquals('测试提供商', $foundProvider->getName());
        $this->assertEquals('test_provider', $foundProvider->getCode());
        $this->assertTrue($foundProvider->isActive());
    }

    public function testServiceAvailability(): void
    {
        // 检查服务是否可用
        $services = [
            'Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService',
            'Tourze\RealNameAuthenticationBundle\Service\AuthenticationProviderService',
            'Tourze\RealNameAuthenticationBundle\Service\ManualReviewService',
            'Tourze\RealNameAuthenticationBundle\Service\BatchImportService',
        ];
        
        foreach ($services as $serviceId) {
            $this->assertTrue(
                $this->kernel->getContainer()->has($serviceId),
                "Service {$serviceId} should be available"
            );
        }
    }
}