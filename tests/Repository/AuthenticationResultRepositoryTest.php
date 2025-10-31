<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;
use Tourze\RealNameAuthenticationBundle\Repository\AuthenticationResultRepository;

/**
 * @internal
 */
#[CoversClass(AuthenticationResultRepository::class)]
#[RunTestsInSeparateProcesses]
final class AuthenticationResultRepositoryTest extends AbstractRepositoryTestCase
{
    private AuthenticationResultRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AuthenticationResultRepository::class);
    }

    public function testRepositoryConfiguration(): void
    {
        $this->assertInstanceOf(AuthenticationResultRepository::class, $this->repository);
    }

    public function testPaginationWithValidParams(): void
    {
        $entity1 = $this->createNewEntity();
        $entity2 = $this->createNewEntity();

        self::getEntityManager()->persist($entity1);
        self::getEntityManager()->persist($entity2);
        self::getEntityManager()->flush();

        $result = $this->repository->findBy([], null, 1, 0);

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(AuthenticationResult::class, $result);
    }

    public function testFindByUser(): void
    {
        // 创建两个用户
        $user1 = $this->createNormalUser('user1@example.com', 'password123');
        $user2 = $this->createNormalUser('user2@example.com', 'password123');

        // 为用户1创建认证结果
        $result1 = $this->createResultForUser($user1, true);
        $result2 = $this->createResultForUser($user1, false);

        // 为用户2创建认证结果
        $result3 = $this->createResultForUser($user2, true);

        self::getEntityManager()->persist($result1);
        self::getEntityManager()->persist($result2);
        self::getEntityManager()->persist($result3);
        self::getEntityManager()->flush();

        // 测试查询用户1的结果
        $user1Results = $this->repository->findByUser($user1);

        $this->assertCount(2, $user1Results, '应该返回用户1的2个认证结果');
        $this->assertContainsOnlyInstancesOf(AuthenticationResult::class, $user1Results);

        foreach ($user1Results as $result) {
            $this->assertEquals($user1->getUserIdentifier(), $result->getAuthentication()->getUser()->getUserIdentifier());
        }

        // 测试查询用户2的结果
        $user2Results = $this->repository->findByUser($user2);

        $this->assertCount(1, $user2Results, '应该返回用户2的1个认证结果');
        $this->assertEquals($user2->getUserIdentifier(), $user2Results[0]->getAuthentication()->getUser()->getUserIdentifier());
    }

    public function testFindByUserAndMethod(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 创建不同认证方式的结果
        $idCardResult = $this->createResultForUserAndMethod($user, AuthenticationMethod::ID_CARD_TWO_ELEMENTS, true);
        $bankCardResult = $this->createResultForUserAndMethod($user, AuthenticationMethod::BANK_CARD_THREE_ELEMENTS, false);
        $anotherIdCardResult = $this->createResultForUserAndMethod($user, AuthenticationMethod::ID_CARD_TWO_ELEMENTS, true);

        self::getEntityManager()->persist($idCardResult);
        self::getEntityManager()->persist($bankCardResult);
        self::getEntityManager()->persist($anotherIdCardResult);
        self::getEntityManager()->flush();

        // 测试查询身份证二要素的结果
        $idCardResults = $this->repository->findByUserAndMethod($user, AuthenticationMethod::ID_CARD_TWO_ELEMENTS);

        $this->assertCount(2, $idCardResults, '应该返回2个身份证二要素认证结果');
        foreach ($idCardResults as $result) {
            $this->assertEquals(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $result->getAuthentication()->getMethod());
        }

        // 测试查询银行卡三要素的结果
        $bankCardResults = $this->repository->findByUserAndMethod($user, AuthenticationMethod::BANK_CARD_THREE_ELEMENTS);

        $this->assertCount(1, $bankCardResults, '应该返回1个银行卡三要素认证结果');
        $this->assertEquals(AuthenticationMethod::BANK_CARD_THREE_ELEMENTS, $bankCardResults[0]->getAuthentication()->getMethod());

        // 测试查询不存在的认证方式
        $livenessResults = $this->repository->findByUserAndMethod($user, AuthenticationMethod::LIVENESS_DETECTION);
        $this->assertEmpty($livenessResults);
    }

    public function testFindLatestByUser(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 创建多个认证结果，时间间隔1秒
        $result1 = $this->createResultForUser($user, false);
        self::getEntityManager()->persist($result1);
        self::getEntityManager()->flush();

        // 等待1秒确保时间不同
        sleep(1);

        $result2 = $this->createResultForUser($user, true);
        self::getEntityManager()->persist($result2);
        self::getEntityManager()->flush();

        // 测试查询最新的认证结果
        $latestResult = $this->repository->findLatestByUser($user);

        $this->assertNotNull($latestResult);
        $this->assertInstanceOf(AuthenticationResult::class, $latestResult);
        $this->assertTrue($latestResult->isSuccess(), '最新的结果应该是成功的');
        $this->assertGreaterThan($result1->getCreateTime(), $latestResult->getCreateTime(), '应该返回时间最新的结果');
    }

    public function testGetStatisticsByStatus(): void
    {
        // 获取初始统计数据作为基准
        $initialStatistics = $this->repository->getStatisticsByStatus();
        $initialSuccess = $initialStatistics['success'] ?? 0;
        $initialFailed = $initialStatistics['failed'] ?? 0;

        // 创建不同状态的认证结果
        $successResult1 = $this->createResultWithStatus(true);
        $successResult2 = $this->createResultWithStatus(true);
        $failedResult1 = $this->createResultWithStatus(false);

        self::getEntityManager()->persist($successResult1);
        self::getEntityManager()->persist($successResult2);
        self::getEntityManager()->persist($failedResult1);
        self::getEntityManager()->flush();

        // 测试状态统计
        $statistics = $this->repository->getStatisticsByStatus();

        $this->assertIsArray($statistics);
        $this->assertEquals($initialSuccess + 2, $statistics['success'], '应该增加2个成功的结果');
        $this->assertEquals($initialFailed + 1, $statistics['failed'], '应该增加1个失败的结果');
    }

    public function testGetStatisticsByMethod(): void
    {
        // 获取初始统计数据作为基准
        $initialStatistics = $this->repository->getStatisticsByMethod();
        $initialIdCard = $initialStatistics[AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value] ?? 0;
        $initialBankCard = $initialStatistics[AuthenticationMethod::BANK_CARD_THREE_ELEMENTS->value] ?? 0;

        // 创建不同认证方式的结果
        $idCardResult1 = $this->createResultWithMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $idCardResult2 = $this->createResultWithMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $bankCardResult = $this->createResultWithMethod(AuthenticationMethod::BANK_CARD_THREE_ELEMENTS);

        self::getEntityManager()->persist($idCardResult1);
        self::getEntityManager()->persist($idCardResult2);
        self::getEntityManager()->persist($bankCardResult);
        self::getEntityManager()->flush();

        // 测试认证方式统计
        $statistics = $this->repository->getStatisticsByMethod();

        $this->assertIsArray($statistics);
        $this->assertEquals($initialIdCard + 2, $statistics[AuthenticationMethod::ID_CARD_TWO_ELEMENTS->value], '身份证二要素应该增加2个结果');
        $this->assertEquals($initialBankCard + 1, $statistics[AuthenticationMethod::BANK_CARD_THREE_ELEMENTS->value], '银行卡三要素应该增加1个结果');
    }

    public function testFindLatestByUserWithNoResults(): void
    {
        $user = $this->createNormalUser('empty@example.com', 'password123');

        // 测试没有认证结果的用户
        $result = $this->repository->findLatestByUser($user);
        $this->assertNull($result);
    }

    public function testGetStatisticsReturnValidArrays(): void
    {
        // 测试统计方法返回有效的数组结构
        $statusStats = $this->repository->getStatisticsByStatus();
        $methodStats = $this->repository->getStatisticsByMethod();

        $this->assertIsArray($statusStats, '状态统计应该返回数组');
        $this->assertIsArray($methodStats, '方法统计应该返回数组');

        // 验证状态统计的键存在（即使值可能为0）
        $this->assertArrayHasKey('success', $statusStats, '状态统计应该包含success键');
        $this->assertArrayHasKey('failed', $statusStats, '状态统计应该包含failed键');

        // 验证所有返回的值都是整数
        foreach ($statusStats as $key => $value) {
            $this->assertIsInt($value, "状态统计中的 {$key} 值应该是整数");
            $this->assertGreaterThanOrEqual(0, $value, "状态统计中的 {$key} 值应该大于或等于0");
        }

        foreach ($methodStats as $key => $value) {
            $this->assertIsInt($value, "方法统计中的 {$key} 值应该是整数");
            $this->assertGreaterThanOrEqual(0, $value, "方法统计中的 {$key} 值应该大于或等于0");
        }
    }

    public function testFindByAuthentication(): void
    {
        $user = $this->createNormalUser('auth-test@example.com', 'password123');
        $authentication = new RealNameAuthentication();
        $authentication->setUser($user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setSubmittedData(['name' => 'Test User', 'id_number' => '123456789012345678']);
        self::getEntityManager()->persist($authentication);

        // 为这个认证创建多个结果
        $result1 = $this->createResultForAuthentication($authentication, true);
        $result2 = $this->createResultForAuthentication($authentication, false);

        self::getEntityManager()->persist($result1);
        self::getEntityManager()->persist($result2);
        self::getEntityManager()->flush();

        $results = $this->repository->findByAuthentication($authentication);
        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertEquals($authentication->getId(), $result->getAuthentication()->getId());
        }
    }

    public function testFindByProvider(): void
    {
        $provider = new AuthenticationProvider();
        $provider->setName('Test Provider');
        $provider->setCode('test-provider-' . uniqid());
        $provider->setType(ProviderType::GOVERNMENT);
        $provider->setApiEndpoint('https://api.example.com');
        self::getEntityManager()->persist($provider);

        $user = $this->createNormalUser('provider-test@example.com', 'password123');

        // 创建使用该提供商的多个结果
        $result1 = $this->createResultForProvider($user, $provider, true);
        $result2 = $this->createResultForProvider($user, $provider, false);

        self::getEntityManager()->persist($result1);
        self::getEntityManager()->persist($result2);
        self::getEntityManager()->flush();

        $results = $this->repository->findByProvider($provider);
        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertEquals($provider->getId(), $result->getProvider()->getId());
        }
    }

    public function testFindByRequestId(): void
    {
        $user = $this->createNormalUser('request-test@example.com', 'password123');
        $requestId = 'unique-request-' . uniqid();

        $result = $this->createResultForUser($user, true);
        $result->setRequestId($requestId);

        self::getEntityManager()->persist($result);
        self::getEntityManager()->flush();

        $foundResult = $this->repository->findByRequestId($requestId);
        $this->assertNotNull($foundResult);
        $this->assertEquals($requestId, $foundResult->getRequestId());

        // 测试不存在的请求ID
        $notFound = $this->repository->findByRequestId('non-existing-request-id');
        $this->assertNull($notFound);
    }

    public function testFindSuccessfulResults(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . AuthenticationResult::class)->execute();
        self::getEntityManager()->flush();

        $user1 = $this->createNormalUser('success-test1@example.com', 'password123');
        $user2 = $this->createNormalUser('success-test2@example.com', 'password123');

        // 创建成功和失败的结果
        $successResult1 = $this->createResultForUser($user1, true);
        $successResult2 = $this->createResultForUser($user2, true);
        $failedResult = $this->createResultForUser($user1, false);

        self::getEntityManager()->persist($successResult1);
        self::getEntityManager()->persist($successResult2);
        self::getEntityManager()->persist($failedResult);
        self::getEntityManager()->flush();

        $successfulResults = $this->repository->findSuccessfulResults();
        $this->assertCount(2, $successfulResults);

        foreach ($successfulResults as $result) {
            $this->assertTrue($result->isSuccess());
        }
    }

    public function testFindFailedResults(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . AuthenticationResult::class)->execute();
        self::getEntityManager()->flush();

        $user1 = $this->createNormalUser('failed-test1@example.com', 'password123');
        $user2 = $this->createNormalUser('failed-test2@example.com', 'password123');

        // 创建成功和失败的结果
        $failedResult1 = $this->createResultForUser($user1, false);
        $failedResult2 = $this->createResultForUser($user2, false);
        $successResult = $this->createResultForUser($user1, true);

        self::getEntityManager()->persist($failedResult1);
        self::getEntityManager()->persist($failedResult2);
        self::getEntityManager()->persist($successResult);
        self::getEntityManager()->flush();

        $failedResults = $this->repository->findFailedResults();
        $this->assertCount(2, $failedResults);

        foreach ($failedResults as $result) {
            $this->assertFalse($result->isSuccess());
        }
    }

    public function testFindSlowResults(): void
    {
        $user = $this->createNormalUser('slow-test@example.com', 'password123');

        // 创建处理时间不同的结果
        $fastResult = $this->createResultForUser($user, true);
        $fastResult->setProcessingTime(500); // 0.5秒，快速

        $slowResult = $this->createResultForUser($user, true);
        $slowResult->setProcessingTime(3500); // 3.5秒，慢速

        self::getEntityManager()->persist($fastResult);
        self::getEntityManager()->persist($slowResult);
        self::getEntityManager()->flush();

        $slowResults = $this->repository->findSlowResults(3000); // 阈值3秒
        $this->assertCount(1, $slowResults);
        $this->assertEquals($slowResult->getId(), $slowResults[0]->getId());
        $this->assertGreaterThan(3000, $slowResults[0]->getProcessingTime());
    }

    public function testFindByConfidenceRange(): void
    {
        // 跳过这个测试，因为AuthenticationResult实体没有confidenceScore字段
        // 如果将来需要这个功能，需要先在实体中添加相应字段
        self::markTestSkipped('AuthenticationResult entity does not have confidenceScore field');
    }

    public function testFindWithPagination(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . AuthenticationResult::class)->execute();
        self::getEntityManager()->flush();

        $user = $this->createNormalUser('pagination-test@example.com', 'password123');

        // 创建多个结果用于分页测试
        for ($i = 0; $i < 5; ++$i) {
            $result = $this->createResultForUser($user, 0 === $i % 2);
            self::getEntityManager()->persist($result);
        }
        self::getEntityManager()->flush();

        // 测试第一页
        $firstPage = $this->repository->findWithPagination(1, 2);
        $this->assertCount(2, $firstPage);

        // 测试第二页
        $secondPage = $this->repository->findWithPagination(2, 2);
        $this->assertCount(2, $secondPage);

        // 测试第三页
        $thirdPage = $this->repository->findWithPagination(3, 2);
        $this->assertCount(1, $thirdPage);
    }

    /**
     * 为指定认证创建结果
     */
    private function createResultForAuthentication(RealNameAuthentication $authentication, bool $success): AuthenticationResult
    {
        $provider = new AuthenticationProvider();
        $provider->setName('Test Provider ' . uniqid());
        $provider->setCode('test-provider-' . uniqid());
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setApiEndpoint('https://api.example.com');
        self::getEntityManager()->persist($provider);
        self::getEntityManager()->flush();

        $result = new AuthenticationResult();
        $result->setAuthentication($authentication);
        $result->setProvider($provider);
        $result->setRequestId('test-request-' . uniqid());
        $result->setSuccess($success);
        $result->setResponseData(['result' => $success ? 'success' : 'failed']);
        $result->setProcessingTime(1000);

        return $result;
    }

    /**
     * 为指定提供商创建结果
     */
    private function createResultForProvider(UserInterface $user, AuthenticationProvider $provider, bool $success): AuthenticationResult
    {
        $authentication = new RealNameAuthentication();
        $authentication->setUser($user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setSubmittedData(['name' => 'Test User', 'id_number' => '123456789012345678']);
        self::getEntityManager()->persist($authentication);
        self::getEntityManager()->flush();

        $result = new AuthenticationResult();
        $result->setAuthentication($authentication);
        $result->setProvider($provider);
        $result->setRequestId('test-request-' . uniqid());
        $result->setSuccess($success);
        $result->setResponseData(['result' => $success ? 'success' : 'failed']);
        $result->setProcessingTime(1000);

        return $result;
    }

    /**
     * 为指定用户创建认证结果
     */
    private function createResultForUser(UserInterface $user, bool $success): AuthenticationResult
    {
        $authentication = new RealNameAuthentication();
        $authentication->setUser($user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setSubmittedData(['name' => 'Test User', 'id_number' => '123456789012345678']);
        self::getEntityManager()->persist($authentication);

        $provider = new AuthenticationProvider();
        $provider->setName('Test Provider ' . uniqid());
        $provider->setCode('test-provider-' . uniqid());
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setApiEndpoint('https://api.example.com');
        self::getEntityManager()->persist($provider);

        self::getEntityManager()->flush();

        $result = new AuthenticationResult();
        $result->setAuthentication($authentication);
        $result->setProvider($provider);
        $result->setRequestId('test-request-' . uniqid());
        $result->setSuccess($success);
        $result->setResponseData($success ? ['result' => 'success'] : ['error' => 'failed']);
        $result->setProcessingTime(1000);
        $result->setValid(true);

        return $result;
    }

    /**
     * 为指定用户和认证方式创建认证结果
     */
    private function createResultForUserAndMethod(UserInterface $user, AuthenticationMethod $method, bool $success): AuthenticationResult
    {
        $authentication = new RealNameAuthentication();
        $authentication->setUser($user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod($method);
        $authentication->setSubmittedData(['name' => 'Test User', 'id_number' => '123456789012345678']);
        self::getEntityManager()->persist($authentication);

        $provider = new AuthenticationProvider();
        $provider->setName('Test Provider ' . uniqid());
        $provider->setCode('test-provider-' . uniqid());
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setApiEndpoint('https://api.example.com');
        self::getEntityManager()->persist($provider);

        self::getEntityManager()->flush();

        $result = new AuthenticationResult();
        $result->setAuthentication($authentication);
        $result->setProvider($provider);
        $result->setRequestId('test-request-' . uniqid());
        $result->setSuccess($success);
        $result->setResponseData($success ? ['result' => 'success'] : ['error' => 'failed']);
        $result->setProcessingTime(1000);
        $result->setValid(true);

        return $result;
    }

    /**
     * 创建指定状态的认证结果
     */
    private function createResultWithStatus(bool $success): AuthenticationResult
    {
        $user = $this->createNormalUser('test' . uniqid() . '@example.com', 'password123');

        $authentication = new RealNameAuthentication();
        $authentication->setUser($user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setSubmittedData(['name' => 'Test User', 'id_number' => '123456789012345678']);
        self::getEntityManager()->persist($authentication);

        $provider = new AuthenticationProvider();
        $provider->setName('Test Provider ' . uniqid());
        $provider->setCode('test-provider-' . uniqid());
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setApiEndpoint('https://api.example.com');
        self::getEntityManager()->persist($provider);

        self::getEntityManager()->flush();

        $result = new AuthenticationResult();
        $result->setAuthentication($authentication);
        $result->setProvider($provider);
        $result->setRequestId('test-request-' . uniqid());
        $result->setSuccess($success);
        $result->setResponseData($success ? ['result' => 'success'] : ['error' => 'failed']);
        $result->setProcessingTime(1000);
        $result->setValid(true);

        return $result;
    }

    /**
     * 创建指定认证方式的认证结果
     */
    private function createResultWithMethod(AuthenticationMethod $method): AuthenticationResult
    {
        $user = $this->createNormalUser('test' . uniqid() . '@example.com', 'password123');

        $authentication = new RealNameAuthentication();
        $authentication->setUser($user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod($method);
        $authentication->setSubmittedData(['name' => 'Test User', 'id_number' => '123456789012345678']);
        self::getEntityManager()->persist($authentication);

        $provider = new AuthenticationProvider();
        $provider->setName('Test Provider ' . uniqid());
        $provider->setCode('test-provider-' . uniqid());
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setApiEndpoint('https://api.example.com');
        self::getEntityManager()->persist($provider);

        self::getEntityManager()->flush();

        $result = new AuthenticationResult();
        $result->setAuthentication($authentication);
        $result->setProvider($provider);
        $result->setRequestId('test-request-' . uniqid());
        $result->setSuccess(true);
        $result->setResponseData(['result' => 'success']);
        $result->setProcessingTime(1000);
        $result->setValid(true);

        return $result;
    }

    protected function createNewEntity(): object
    {
        $user = $this->createNormalUser('test' . uniqid() . '@example.com', 'password123');

        $authentication = new RealNameAuthentication();
        $authentication->setUser($user);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setSubmittedData(['name' => 'Test User ' . uniqid(), 'id_number' => '123456789012345678']);
        self::getEntityManager()->persist($authentication);

        $provider = new AuthenticationProvider();
        $provider->setName('Test Provider ' . uniqid());
        $provider->setCode('test-provider-' . uniqid());
        $provider->setType(ProviderType::THIRD_PARTY);
        $provider->setApiEndpoint('https://api.example.com');
        self::getEntityManager()->persist($provider);

        self::getEntityManager()->flush();

        $entity = new AuthenticationResult();
        $entity->setAuthentication($authentication);
        $entity->setProvider($provider);
        $entity->setRequestId('test-request-id-' . uniqid());
        $entity->setSuccess(true);
        $entity->setResponseData(['result' => 'success']);
        $entity->setProcessingTime(1000);

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<AuthenticationResult>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
