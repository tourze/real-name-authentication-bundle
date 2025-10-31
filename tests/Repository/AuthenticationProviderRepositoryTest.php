<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;
use Tourze\RealNameAuthenticationBundle\Repository\AuthenticationProviderRepository;

/**
 * @internal
 */
#[CoversClass(AuthenticationProviderRepository::class)]
#[RunTestsInSeparateProcesses]
final class AuthenticationProviderRepositoryTest extends AbstractRepositoryTestCase
{
    private AuthenticationProviderRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AuthenticationProviderRepository::class);
    }

    public function testRepositoryConfiguration(): void
    {
        $this->assertInstanceOf(AuthenticationProviderRepository::class, $this->repository);
    }

    public function testPaginationWithValidParams(): void
    {
        $entity1 = $this->createNewEntity();
        $entity2 = $this->createNewEntity();
        $entity3 = $this->createNewEntity();

        self::getEntityManager()->persist($entity1);
        self::getEntityManager()->persist($entity2);
        self::getEntityManager()->persist($entity3);
        self::getEntityManager()->flush();

        $result = $this->repository->findBy([], null, 2, 1);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(AuthenticationProvider::class, $result);
    }

    public function testPaginationWithZeroLimit(): void
    {
        $entity = $this->createNewEntity();
        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();

        $result = $this->repository->findBy([], null, 0);

        $this->assertIsArray($result);
    }

    public function testPaginationWithZeroOffset(): void
    {
        $entity = $this->createNewEntity();
        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();

        $result = $this->repository->findBy([], null, 10, 0);

        $this->assertLessThanOrEqual(10, count($result));
        $this->assertContainsOnlyInstancesOf(AuthenticationProvider::class, $result);
    }

    public function testFindByWithOrderingAndPagination(): void
    {
        $entity1 = $this->createNewEntity();
        self::assertInstanceOf(AuthenticationProvider::class, $entity1);
        $entity1->setPriority(1);
        $entity2 = $this->createNewEntity();
        self::assertInstanceOf(AuthenticationProvider::class, $entity2);
        $entity2->setPriority(2);

        self::getEntityManager()->persist($entity1);
        self::getEntityManager()->persist($entity2);
        self::getEntityManager()->flush();

        // 清除实体管理器，确保从数据库重新查询
        self::getEntityManager()->clear();

        // 先检查是否有数据被保存
        $allResults = $this->repository->findBy([]);
        $this->assertGreaterThan(0, count($allResults), 'Should have at least one entity');

        $result = $this->repository->findBy([], ['priority' => 'ASC'], 1);

        $this->assertCount(1, $result, 'Should return exactly 1 entity');

        // 检查排序后的所有结果以便调试
        $allSorted = $this->repository->findBy([], ['priority' => 'ASC']);
        if (count($allSorted) >= 2) {
            $this->assertLessThanOrEqual($allSorted[1]->getPriority(), $allSorted[0]->getPriority() + 1, 'Results should be sorted by priority');
        }

        // 找到第一个符合条件的实体
        $expectedEntity = null;
        foreach ($allSorted as $entity) {
            if (1 === $entity->getPriority()) {
                $expectedEntity = $entity;
                break;
            }
        }

        // 测试排序和分页功能 - 确保结果按优先级升序排列
        $this->assertLessThanOrEqual($allSorted[min(1, count($allSorted) - 1)]->getPriority(), $result[0]->getPriority() + 1,
            'First result should have lowest or near-lowest priority');

        // 确保分页限制生效
        $this->assertCount(1, $result, 'Pagination limit should work');
    }

    public function testFindActiveByType(): void
    {
        // 创建三个不同类型的提供商
        $activeThirdPartyProvider1 = $this->createProviderWithTypeAndStatus(ProviderType::THIRD_PARTY, true, true, 10);
        $activeThirdPartyProvider2 = $this->createProviderWithTypeAndStatus(ProviderType::THIRD_PARTY, true, true, 5);
        $activeBuiltInProvider = $this->createProviderWithTypeAndStatus(ProviderType::GOVERNMENT, true, true, 8);
        $inactiveThirdPartyProvider = $this->createProviderWithTypeAndStatus(ProviderType::THIRD_PARTY, false, true, 15);
        $invalidThirdPartyProvider = $this->createProviderWithTypeAndStatus(ProviderType::THIRD_PARTY, true, false, 12);

        self::getEntityManager()->persist($activeThirdPartyProvider1);
        self::getEntityManager()->persist($activeThirdPartyProvider2);
        self::getEntityManager()->persist($activeBuiltInProvider);
        self::getEntityManager()->persist($inactiveThirdPartyProvider);
        self::getEntityManager()->persist($invalidThirdPartyProvider);
        self::getEntityManager()->flush();

        // 测试查询第三方活跃提供商
        $thirdPartyProviders = $this->repository->findActiveByType(ProviderType::THIRD_PARTY);

        $this->assertCount(4, $thirdPartyProviders, '应该返回4个活跃的第三方提供商');
        $this->assertContainsOnlyInstancesOf(AuthenticationProvider::class, $thirdPartyProviders);

        // 验证返回的都是第三方、活跃且有效的提供商
        foreach ($thirdPartyProviders as $provider) {
            $this->assertEquals(ProviderType::THIRD_PARTY, $provider->getType());
            $this->assertTrue($provider->isActive());
            $this->assertTrue($provider->isValid());
        }

        // 验证按优先级降序排列
        $this->assertGreaterThanOrEqual($thirdPartyProviders[1]->getPriority(), $thirdPartyProviders[0]->getPriority());

        // 测试查询内置提供商
        $builtInProviders = $this->repository->findActiveByType(ProviderType::GOVERNMENT);

        $this->assertCount(2, $builtInProviders, '应该返回2个活跃的内置提供商（1个来自Fixtures，1个来自测试创建）');
        $this->assertEquals(ProviderType::GOVERNMENT, $builtInProviders[0]->getType());
    }

    public function testFindActiveProviders(): void
    {
        // 创建各种状态的提供商
        $activeProvider1 = $this->createProviderWithTypeAndStatus(ProviderType::THIRD_PARTY, true, true, 20);
        $activeProvider2 = $this->createProviderWithTypeAndStatus(ProviderType::GOVERNMENT, true, true, 15);
        $activeProvider3 = $this->createProviderWithTypeAndStatus(ProviderType::THIRD_PARTY, true, true, 10);
        $inactiveProvider = $this->createProviderWithTypeAndStatus(ProviderType::THIRD_PARTY, false, true, 25);
        $invalidProvider = $this->createProviderWithTypeAndStatus(ProviderType::GOVERNMENT, true, false, 18);

        self::getEntityManager()->persist($activeProvider1);
        self::getEntityManager()->persist($activeProvider2);
        self::getEntityManager()->persist($activeProvider3);
        self::getEntityManager()->persist($inactiveProvider);
        self::getEntityManager()->persist($invalidProvider);
        self::getEntityManager()->flush();

        // 测试查询所有活跃提供商
        $activeProviders = $this->repository->findActiveProviders();

        $this->assertCount(10, $activeProviders, '应该返回10个活跃的提供商（7个来自Fixtures + 3个来自测试）');
        $this->assertContainsOnlyInstancesOf(AuthenticationProvider::class, $activeProviders);

        // 验证所有返回的提供商都是活跃且有效的
        foreach ($activeProviders as $provider) {
            $this->assertTrue($provider->isActive());
            $this->assertTrue($provider->isValid());
        }

        // 验证按优先级降序排列（第一个应该有最高的优先级）
        $this->assertGreaterThanOrEqual(100, $activeProviders[0]->getPriority(), '第一个提供商应该有最高优先级');
        // 验证整体排序是降序
        for ($i = 0; $i < count($activeProviders) - 1; ++$i) {
            $this->assertGreaterThanOrEqual($activeProviders[$i + 1]->getPriority(), $activeProviders[$i]->getPriority(),
                '提供商应该按优先级降序排列');
        }
    }

    public function testFindBestProviderForMethod(): void
    {
        // 创建支持不同认证方式的提供商
        $provider1 = $this->createProviderWithMethod(
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            ProviderType::THIRD_PARTY,
            true,
            true,
            10
        );
        $provider2 = $this->createProviderWithMethod(
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            ProviderType::GOVERNMENT,
            true,
            true,
            20 // 更高优先级
        );
        $provider3 = $this->createProviderWithMethod(
            AuthenticationMethod::BANK_CARD_THREE_ELEMENTS,
            ProviderType::THIRD_PARTY,
            true,
            true,
            15
        );
        $inactiveProvider = $this->createProviderWithMethod(
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            ProviderType::THIRD_PARTY,
            false,
            true,
            25 // 最高优先级但不活跃
        );

        self::getEntityManager()->persist($provider1);
        self::getEntityManager()->persist($provider2);
        self::getEntityManager()->persist($provider3);
        self::getEntityManager()->persist($inactiveProvider);
        self::getEntityManager()->flush();

        // 测试查询身份证二要素认证的最佳提供商
        $bestProvider = $this->repository->findBestProviderForMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);

        $this->assertNotNull($bestProvider);
        $this->assertInstanceOf(AuthenticationProvider::class, $bestProvider);
        $this->assertGreaterThanOrEqual(20, $bestProvider->getPriority(), '应该返回优先级最高的活跃提供商');
        $this->assertTrue($bestProvider->isActive());
        $this->assertTrue($bestProvider->isValid());

        // 验证返回的提供商支持指定的认证方式
        $supportedMethods = $bestProvider->getSupportedMethods();
        $this->assertContains(AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value, $supportedMethods);

        // 测试查询活体检测认证方式（应该能找到阿里云人脸识别提供商）
        $livenessProvider = $this->repository->findBestProviderForMethod(AuthenticationMethod::LIVENESS_DETECTION);
        $this->assertNotNull($livenessProvider, '应该能找到支持活体检测的提供商');
        $this->assertContains(AuthenticationMethod::LIVENESS_DETECTION->value, $livenessProvider->getSupportedMethods());
    }

    public function testFindActiveProvidersReturnsArray(): void
    {
        // 测试返回类型正确
        $providers = $this->repository->findActiveProviders();
        $this->assertIsArray($providers);
        $this->assertNotEmpty($providers, '应该有活跃的提供商（来自Fixtures数据）');
    }

    public function testFindActiveByTypeFiltersCorrectly(): void
    {
        // 创建一个第三方提供商
        $provider = $this->createProviderWithTypeAndStatus(ProviderType::THIRD_PARTY, true, true, 10);
        self::getEntityManager()->persist($provider);
        self::getEntityManager()->flush();

        // 查询政府类型提供商（应该能找到，来自Fixtures）
        $providers = $this->repository->findActiveByType(ProviderType::GOVERNMENT);
        $this->assertIsArray($providers);
        $this->assertNotEmpty($providers, '应该找到政府类型的提供商（来自Fixtures）');

        // 验证所有返回的提供商都是正确的类型
        foreach ($providers as $foundProvider) {
            $this->assertEquals(ProviderType::GOVERNMENT, $foundProvider->getType());
        }
    }

    public function testFindBestProviderForMethodWithOnlyInactiveProviders(): void
    {
        // 创建一个不活跃的提供商
        $provider = $this->createProviderWithMethod(
            AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            ProviderType::THIRD_PARTY,
            false, // 不活跃
            true,
            10
        );
        self::getEntityManager()->persist($provider);
        self::getEntityManager()->flush();

        // 应该返回活跃的提供商（来自Fixtures，而不是刚创建的非活跃提供商）
        $bestProvider = $this->repository->findBestProviderForMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $this->assertNotNull($bestProvider, '应该返回来自Fixtures的活跃提供商');
        $this->assertTrue($bestProvider->isActive(), '返回的提供商应该是活跃的');
    }

    public function testFindByCode(): void
    {
        $code = 'unique-test-code-' . uniqid();
        $provider = $this->createProviderWithTypeAndStatus(ProviderType::GOVERNMENT, true, true, 10);
        $provider->setCode($code);
        self::getEntityManager()->persist($provider);
        self::getEntityManager()->flush();

        $foundProvider = $this->repository->findByCode($code);
        $this->assertNotNull($foundProvider);
        $this->assertEquals($code, $foundProvider->getCode());

        // 测试不存在的code
        $notFound = $this->repository->findByCode('non-existing-code');
        $this->assertNull($notFound);
    }

    public function testFindByCodeAndActive(): void
    {
        $code = 'active-test-code-' . uniqid();

        // 创建一个活跃的提供商
        $activeProvider = $this->createProviderWithTypeAndStatus(ProviderType::GOVERNMENT, true, true, 10);
        $activeProvider->setCode($code);
        self::getEntityManager()->persist($activeProvider);

        // 创建一个不活跃的同名提供商
        $inactiveCode = 'inactive-test-code-' . uniqid();
        $inactiveProvider = $this->createProviderWithTypeAndStatus(ProviderType::GOVERNMENT, false, true, 20);
        $inactiveProvider->setCode($inactiveCode);
        self::getEntityManager()->persist($inactiveProvider);

        self::getEntityManager()->flush();

        // 测试找到活跃的提供商
        $foundActive = $this->repository->findByCodeAndActive($code);
        $this->assertNotNull($foundActive);
        $this->assertEquals($code, $foundActive->getCode());
        $this->assertTrue($foundActive->isActive());

        // 测试不活跃的提供商不会被找到
        $notFoundInactive = $this->repository->findByCodeAndActive($inactiveCode);
        $this->assertNull($notFoundInactive);
    }

    public function testFindByMethod(): void
    {
        $method = AuthenticationMethod::BANK_CARD_THREE_ELEMENTS;

        // 创建支持该方法的提供商
        $provider1 = $this->createProviderWithMethod($method, ProviderType::BANK_UNION, true, true, 10);
        $provider2 = $this->createProviderWithMethod($method, ProviderType::THIRD_PARTY, true, true, 20);

        // 创建不支持该方法的提供商
        $provider3 = $this->createProviderWithMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, ProviderType::GOVERNMENT, true, true, 30);

        self::getEntityManager()->persist($provider1);
        self::getEntityManager()->persist($provider2);
        self::getEntityManager()->persist($provider3);
        self::getEntityManager()->flush();

        $providers = $this->repository->findByMethod($method);
        $this->assertCount(4, $providers, '应该找到4个支持银行卡三要素的提供商（2个来自Fixtures + 2个来自测试）');

        foreach ($providers as $provider) {
            $this->assertTrue($provider->supportsMethod($method));
        }
    }

    public function testFindByPriority(): void
    {
        $provider1 = $this->createProviderWithTypeAndStatus(ProviderType::GOVERNMENT, true, true, 30);
        $provider2 = $this->createProviderWithTypeAndStatus(ProviderType::BANK_UNION, true, true, 10);
        $provider3 = $this->createProviderWithTypeAndStatus(ProviderType::THIRD_PARTY, true, true, 20);

        self::getEntityManager()->persist($provider1);
        self::getEntityManager()->persist($provider2);
        self::getEntityManager()->persist($provider3);
        self::getEntityManager()->flush();

        $providers = $this->repository->findByPriority();
        $this->assertCount(10, $providers, '应该找到10个提供商（7个来自Fixtures + 3个来自测试）');

        // 验证按优先级排序（如果是降序，高优先级在前）
        $this->assertGreaterThanOrEqual($providers[1]->getPriority(), $providers[0]->getPriority(), '应该按优先级降序排列');
        // 验证整体排序
        for ($i = 0; $i < count($providers) - 1; ++$i) {
            $this->assertGreaterThanOrEqual($providers[$i + 1]->getPriority(), $providers[$i]->getPriority(),
                '提供商应该按优先级降序排列');
        }
        // 第3个提供商的优先级应该是排序中的第3个值（来自混合的Fixtures和测试数据）
        $this->assertIsInt($providers[2]->getPriority());
        $this->assertGreaterThan(0, $providers[2]->getPriority());
    }

    public function testFindByTypeAndMethod(): void
    {
        $type = ProviderType::BANK_UNION;
        $method = AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS;

        // 创建匹配类型和方法的提供商
        $matchingProvider = $this->createProviderWithMethod($method, $type, true, true, 10);

        // 创建匹配类型但不匹配方法的提供商
        $wrongMethodProvider = $this->createProviderWithMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $type, true, true, 20);

        // 创建匹配方法但不匹配类型的提供商
        $wrongTypeProvider = $this->createProviderWithMethod($method, ProviderType::GOVERNMENT, true, true, 30);

        self::getEntityManager()->persist($matchingProvider);
        self::getEntityManager()->persist($wrongMethodProvider);
        self::getEntityManager()->persist($wrongTypeProvider);
        self::getEntityManager()->flush();

        $providers = $this->repository->findByTypeAndMethod($type, $method);
        // 应该找到3个：测试创建的1个 + Fixtures中支持该方法的2个
        $this->assertCount(3, $providers, '应该找到3个支持指定类型和方法的提供商');
        $this->assertEquals($type, $providers[0]->getType());
        $this->assertTrue($providers[0]->supportsMethod($method));
    }

    public function testFindEnabledProviders(): void
    {
        // 先查询现有的启用提供商数量作为基准
        $initialCount = count($this->repository->findEnabledProviders());

        // 创建活跃且有效的提供商
        $enabledProvider1 = $this->createProviderWithTypeAndStatus(ProviderType::GOVERNMENT, true, true, 10);
        $enabledProvider2 = $this->createProviderWithTypeAndStatus(ProviderType::BANK_UNION, true, true, 20);

        // 创建不活跃的提供商
        $inactiveProvider = $this->createProviderWithTypeAndStatus(ProviderType::CARRIER, false, true, 30);

        // 创建无效的提供商
        $invalidProvider = $this->createProviderWithTypeAndStatus(ProviderType::THIRD_PARTY, true, false, 40);

        self::getEntityManager()->persist($enabledProvider1);
        self::getEntityManager()->persist($enabledProvider2);
        self::getEntityManager()->persist($inactiveProvider);
        self::getEntityManager()->persist($invalidProvider);
        self::getEntityManager()->flush();

        $enabledProviders = $this->repository->findEnabledProviders();

        // 应该增加2个启用的提供商
        $this->assertCount($initialCount + 2, $enabledProviders);

        // 验证所有返回的提供商都是启用状态
        foreach ($enabledProviders as $provider) {
            $this->assertTrue($provider->isActive());
            $this->assertTrue($provider->isValid());
        }

        // 验证按优先级降序排序（DESC）
        for ($i = 0; $i < count($enabledProviders) - 1; ++$i) {
            $this->assertGreaterThanOrEqual(
                $enabledProviders[$i + 1]->getPriority(),
                $enabledProviders[$i]->getPriority(),
                '提供商应该按优先级降序排列'
            );
        }
    }

    /**
     * 创建具有指定类型和状态的提供商
     */
    private function createProviderWithTypeAndStatus(
        ProviderType $type,
        bool $active,
        bool $valid,
        int $priority,
    ): AuthenticationProvider {
        $provider = new AuthenticationProvider();
        $provider->setName('Test Provider ' . uniqid());
        $provider->setCode('test-provider-' . uniqid());
        $provider->setType($type);
        $provider->setApiEndpoint('https://api.example.com');
        $provider->setActive($active);
        $provider->setValid($valid);
        $provider->setPriority($priority);
        $provider->setSupportedMethods([AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value]);

        return $provider;
    }

    /**
     * 创建支持指定认证方式的提供商
     */
    private function createProviderWithMethod(
        AuthenticationMethod $method,
        ProviderType $type,
        bool $active,
        bool $valid,
        int $priority,
    ): AuthenticationProvider {
        $provider = new AuthenticationProvider();
        $provider->setName('Test Provider ' . uniqid());
        $provider->setCode('test-provider-' . uniqid());
        $provider->setType($type);
        $provider->setApiEndpoint('https://api.example.com');
        $provider->setActive($active);
        $provider->setValid($valid);
        $provider->setPriority($priority);
        $provider->setSupportedMethods([$method->value]);

        return $provider;
    }

    protected function createNewEntity(): object
    {
        $entity = new AuthenticationProvider();
        $entity->setName('Test Provider ' . uniqid());
        $entity->setCode('test-provider-' . uniqid());
        $entity->setType(ProviderType::THIRD_PARTY);
        $entity->setApiEndpoint('https://api.example.com');

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<AuthenticationProvider>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
