<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;

/**
 * @internal
 */
#[CoversClass(RealNameAuthenticationRepository::class)]
#[RunTestsInSeparateProcesses]
final class RealNameAuthenticationRepositoryTest extends AbstractRepositoryTestCase
{
    private RealNameAuthenticationRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(RealNameAuthenticationRepository::class);
    }

    public function testRepositoryConfiguration(): void
    {
        $this->assertInstanceOf(RealNameAuthenticationRepository::class, $this->repository);
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
        $this->assertContainsOnlyInstancesOf(RealNameAuthentication::class, $result);
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
        $this->assertContainsOnlyInstancesOf(RealNameAuthentication::class, $result);
    }

    public function testFindByWithStatusFilterAndPagination(): void
    {
        // Clear any existing data to ensure clean test state
        self::getEntityManager()->createQuery('DELETE FROM ' . RealNameAuthentication::class)->execute();
        self::getEntityManager()->flush();

        $user1 = $this->createNormalUser('test1@example.com', 'password123');
        $user2 = $this->createNormalUser('test2@example.com', 'password123');

        $entity1 = new RealNameAuthentication();
        $entity1->setUser($user1);
        $entity1->setType(AuthenticationType::PERSONAL);
        $entity1->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $entity1->setSubmittedData(['name' => 'Test User 1', 'id_number' => '123456789012345678']);
        $entity1->setStatus(AuthenticationStatus::PENDING);

        $entity2 = new RealNameAuthentication();
        $entity2->setUser($user2);
        $entity2->setType(AuthenticationType::PERSONAL);
        $entity2->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $entity2->setSubmittedData(['name' => 'Test User 2', 'id_number' => '123456789012345679']);
        $entity2->setStatus(AuthenticationStatus::APPROVED);

        self::getEntityManager()->persist($entity1);
        self::getEntityManager()->persist($entity2);
        self::getEntityManager()->flush();

        $result = $this->repository->findBy(['status' => AuthenticationStatus::PENDING], null, 10);

        $this->assertCount(1, $result);
        $this->assertEquals(AuthenticationStatus::PENDING, $result[0]->getStatus());
    }

    public function testCountByStatusAndDateRange(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . RealNameAuthentication::class)->execute();
        self::getEntityManager()->flush();

        $user = $this->createNormalUser('test@example.com', 'password123');
        $now = new \DateTimeImmutable();
        $yesterday = $now->modify('-1 day');
        $tomorrow = $now->modify('+1 day');

        $entity1 = $this->createAuthenticationWithUserAndStatus($user, AuthenticationStatus::APPROVED);
        $this->setEntityCreateTime($entity1, $now);
        $entity2 = $this->createAuthenticationWithUserAndStatus($user, AuthenticationStatus::APPROVED);
        $this->setEntityCreateTime($entity2, $now);

        self::getEntityManager()->persist($entity1);
        self::getEntityManager()->persist($entity2);
        self::getEntityManager()->flush();

        $count = $this->repository->countByStatusAndDateRange(AuthenticationStatus::APPROVED, $yesterday, $tomorrow);
        $this->assertEquals(2, $count);

        $count = $this->repository->countByStatusAndDateRange(AuthenticationStatus::PENDING, $yesterday, $tomorrow);
        $this->assertEquals(0, $count);
    }

    public function testFindByStatus(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM ' . RealNameAuthentication::class)->execute();
        self::getEntityManager()->flush();

        $user1 = $this->createNormalUser('test1@example.com', 'password123');
        $user2 = $this->createNormalUser('test2@example.com', 'password123');

        $approvedAuth = $this->createAuthenticationWithUserAndStatus($user1, AuthenticationStatus::APPROVED);
        $pendingAuth = $this->createAuthenticationWithUserAndStatus($user2, AuthenticationStatus::PENDING);

        self::getEntityManager()->persist($approvedAuth);
        self::getEntityManager()->persist($pendingAuth);
        self::getEntityManager()->flush();

        $approvedResults = $this->repository->findByStatus(AuthenticationStatus::APPROVED);
        $this->assertCount(1, $approvedResults);
        $this->assertEquals(AuthenticationStatus::APPROVED, $approvedResults[0]->getStatus());
    }

    public function testFindByUser(): void
    {
        $user1 = $this->createNormalUser('test1@example.com', 'password123');
        $user2 = $this->createNormalUser('test2@example.com', 'password123');

        $auth1 = $this->createAuthenticationWithUserAndStatus($user1, AuthenticationStatus::APPROVED);
        $auth2 = $this->createAuthenticationWithUserAndStatus($user1, AuthenticationStatus::PENDING);
        $auth3 = $this->createAuthenticationWithUserAndStatus($user2, AuthenticationStatus::APPROVED);

        self::getEntityManager()->persist($auth1);
        self::getEntityManager()->persist($auth2);
        self::getEntityManager()->persist($auth3);
        self::getEntityManager()->flush();

        $user1Results = $this->repository->findByUser($user1);
        $this->assertCount(2, $user1Results);
        foreach ($user1Results as $result) {
            $this->assertEquals($user1->getUserIdentifier(), $result->getUser()->getUserIdentifier());
        }
    }

    public function testFindByUserAndStatus(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $approvedAuth = $this->createAuthenticationWithUserAndStatus($user, AuthenticationStatus::APPROVED);
        $pendingAuth = $this->createAuthenticationWithUserAndStatus($user, AuthenticationStatus::PENDING);

        self::getEntityManager()->persist($approvedAuth);
        self::getEntityManager()->persist($pendingAuth);
        self::getEntityManager()->flush();

        $approvedResults = $this->repository->findByUserAndStatus($user, AuthenticationStatus::APPROVED);
        $this->assertCount(1, $approvedResults);
        $this->assertEquals(AuthenticationStatus::APPROVED, $approvedResults[0]->getStatus());
        $this->assertEquals($user->getUserIdentifier(), $approvedResults[0]->getUser()->getUserIdentifier());
    }

    public function testFindByUserAndStatusWithLimit(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        // Create 5 approved authentications
        for ($i = 0; $i < 5; ++$i) {
            $auth = $this->createAuthenticationWithUserAndStatus($user, AuthenticationStatus::APPROVED);
            self::getEntityManager()->persist($auth);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findByUserAndStatusWithLimit($user, AuthenticationStatus::APPROVED, 3);
        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertEquals(AuthenticationStatus::APPROVED, $result->getStatus());
            $this->assertEquals($user->getUserIdentifier(), $result->getUser()->getUserIdentifier());
        }
    }

    public function testFindByUserAndType(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $personalAuth = $this->createAuthenticationWithUserAndType($user, AuthenticationType::PERSONAL);
        // 重构后只支持个人认证，不再需要企业认证测试

        self::getEntityManager()->persist($personalAuth);
        self::getEntityManager()->flush();

        $result = $this->repository->findByUserAndType($user, AuthenticationType::PERSONAL);
        $this->assertNotNull($result);
        $this->assertEquals(AuthenticationType::PERSONAL, $result->getType());
        $this->assertEquals($user->getUserIdentifier(), $result->getUser()->getUserIdentifier());
    }

    public function testFindByUserIdentifier(): void
    {
        $user = $this->createNormalUser('testuser@example.com', 'password123');

        $auth = $this->createAuthenticationWithUserAndStatus($user, AuthenticationStatus::APPROVED);
        self::getEntityManager()->persist($auth);
        self::getEntityManager()->flush();

        $results = $this->repository->findByUserIdentifier('testuser@example.com');
        $this->assertCount(1, $results);
        $this->assertEquals($user->getUserIdentifier(), $results[0]->getUser()->getUserIdentifier());
    }

    public function testFindExpiredAuthentications(): void
    {
        // 清理所有现有的过期认证记录以确保测试隔离
        $existingExpiredAuths = $this->repository->findExpiredAuthentications();
        foreach ($existingExpiredAuths as $auth) {
            self::getEntityManager()->remove($auth);
        }
        self::getEntityManager()->flush();

        $user = $this->createNormalUser('test@example.com', 'password123');
        $pastTime = new \DateTimeImmutable('-1 day');

        $expiredAuth = $this->createAuthenticationWithUserAndStatus($user, AuthenticationStatus::APPROVED);
        $expiredAuth->setExpireTime($pastTime);

        $validAuth = $this->createAuthenticationWithUserAndStatus($user, AuthenticationStatus::APPROVED);
        $validAuth->setExpireTime(new \DateTimeImmutable('+1 day'));

        self::getEntityManager()->persist($expiredAuth);
        self::getEntityManager()->persist($validAuth);
        self::getEntityManager()->flush();

        $expiredResults = $this->repository->findExpiredAuthentications();
        $this->assertCount(1, $expiredResults);
        $this->assertEquals(AuthenticationStatus::APPROVED, $expiredResults[0]->getStatus());
        $this->assertLessThan(new \DateTimeImmutable(), $expiredResults[0]->getExpireTime());
    }

    public function testFindLatestByUser(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $olderAuth = $this->createAuthenticationWithUserAndStatus($user, AuthenticationStatus::APPROVED);
        $this->setEntityCreateTime($olderAuth, new \DateTimeImmutable('-2 hours'));

        $newerAuth = $this->createAuthenticationWithUserAndStatus($user, AuthenticationStatus::PENDING);
        $this->setEntityCreateTime($newerAuth, new \DateTimeImmutable('-1 hour'));

        self::getEntityManager()->persist($olderAuth);
        self::getEntityManager()->persist($newerAuth);
        self::getEntityManager()->flush();

        $latestAuth = $this->repository->findLatestByUser($user);
        $this->assertNotNull($latestAuth);
        $this->assertEquals(AuthenticationStatus::PENDING, $latestAuth->getStatus());
    }

    public function testFindValidByUserAndType(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $validAuth = $this->createAuthenticationWithUserAndType($user, AuthenticationType::PERSONAL);
        $validAuth->setStatus(AuthenticationStatus::APPROVED);
        $validAuth->setValid(true);
        $validAuth->setExpireTime(new \DateTimeImmutable('+1 year'));

        $invalidAuth = $this->createAuthenticationWithUserAndType($user, AuthenticationType::PERSONAL);
        $invalidAuth->setStatus(AuthenticationStatus::REJECTED);

        self::getEntityManager()->persist($validAuth);
        self::getEntityManager()->persist($invalidAuth);
        self::getEntityManager()->flush();

        $result = $this->repository->findValidByUserAndType($user, AuthenticationType::PERSONAL);
        $this->assertNotNull($result);
        $this->assertEquals(AuthenticationStatus::APPROVED, $result->getStatus());
        $this->assertTrue($result->isValid());
    }

    public function testFindWithPagination(): void
    {
        // 清理所有现有认证记录以确保测试隔离
        $existingAuths = $this->repository->findAll();
        foreach ($existingAuths as $auth) {
            self::getEntityManager()->remove($auth);
        }
        self::getEntityManager()->flush();

        $user1 = $this->createNormalUser('test1@example.com', 'password123');
        $user2 = $this->createNormalUser('test2@example.com', 'password123');

        $auth1 = $this->createAuthenticationWithUserAndStatus($user1, AuthenticationStatus::APPROVED);
        $auth2 = $this->createAuthenticationWithUserAndStatus($user2, AuthenticationStatus::PENDING);
        $auth3 = $this->createAuthenticationWithUserAndStatus($user1, AuthenticationStatus::REJECTED);

        self::getEntityManager()->persist($auth1);
        self::getEntityManager()->persist($auth2);
        self::getEntityManager()->persist($auth3);
        self::getEntityManager()->flush();

        // Test pagination with size limit
        $page1Results = $this->repository->findWithPagination(1, 2);
        $this->assertCount(2, $page1Results);

        // Test with status filter
        $filteredResults = $this->repository->findWithPagination(1, 10, ['status' => AuthenticationStatus::APPROVED]);
        $this->assertCount(1, $filteredResults);
        $this->assertEquals(AuthenticationStatus::APPROVED, $filteredResults[0]->getStatus());

        // Test with user filter
        $userResults = $this->repository->findWithPagination(1, 10, ['user' => $user1]);
        $this->assertCount(2, $userResults);
        foreach ($userResults as $result) {
            $this->assertEquals($user1->getUserIdentifier(), $result->getUser()->getUserIdentifier());
        }
    }

    protected function createNewEntity(): object
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $entity = new RealNameAuthentication();
        $entity->setUser($user);
        $entity->setType(AuthenticationType::PERSONAL);
        $entity->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $entity->setSubmittedData(['name' => 'Test User', 'id_number' => '123456789012345678']);

        return $entity;
    }

    private function createAuthenticationWithUserAndStatus(UserInterface $user, AuthenticationStatus $status): RealNameAuthentication
    {
        $entity = new RealNameAuthentication();
        $entity->setUser($user);
        $entity->setType(AuthenticationType::PERSONAL);
        $entity->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $entity->setSubmittedData(['name' => 'Test User', 'id_number' => '123456789012345678']);
        $entity->setStatus($status);

        return $entity;
    }

    private function createAuthenticationWithUserAndType(UserInterface $user, AuthenticationType $type): RealNameAuthentication
    {
        $entity = new RealNameAuthentication();
        $entity->setUser($user);
        $entity->setType($type);
        $entity->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $entity->setSubmittedData(['name' => 'Test User', 'id_number' => '123456789012345678']);
        $entity->setStatus(AuthenticationStatus::PENDING);

        return $entity;
    }

    /**
     * 使用反射设置实体的创建时间（仅供测试使用）
     *
     * @phpstan-ignore-next-line 此处使用反射是为了测试日期范围查询功能，实体设计上不允许修改createTime
     */
    private function setEntityCreateTime(RealNameAuthentication $entity, \DateTimeImmutable $createTime): void
    {
        /** @phpstan-ignore-next-line 反射用于测试日期范围查询，实体不提供createTime setter */
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('createTime');
        $property->setAccessible(true);
        $property->setValue($entity, $createTime);
    }

    /**
     * @return ServiceEntityRepository<RealNameAuthentication>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
