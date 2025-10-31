<?php

namespace Tourze\RealNameAuthenticationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;

/**
 * 认证结果Repository实现
 *
 * @template-extends ServiceEntityRepository<AuthenticationResult>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: AuthenticationResult::class)]
class AuthenticationResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthenticationResult::class);
    }

    /**
     * 根据认证记录查询结果
     *
     * @return array<int, AuthenticationResult>
     *
     * @phpstan-return list<AuthenticationResult>
     */
    public function findByAuthentication(RealNameAuthentication $authentication): array
    {
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.authentication = :authentication')
            ->setParameter('authentication', $authentication)
            ->orderBy('r.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationResult> $result */
        return $result;
    }

    /**
     * 根据提供商查询结果
     *
     * @return array<int, AuthenticationResult>
     *
     * @phpstan-return list<AuthenticationResult>
     */
    public function findByProvider(AuthenticationProvider $provider): array
    {
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.provider = :provider')
            ->setParameter('provider', $provider)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationResult> $result */
        return $result;
    }

    /**
     * 根据请求ID查询结果
     *
     * @phpstan-return AuthenticationResult|null
     */
    public function findByRequestId(string $requestId): ?AuthenticationResult
    {
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.requestId = :requestId')
            ->setParameter('requestId', $requestId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof AuthenticationResult ? $result : null;
    }

    /**
     * 查询成功的认证结果
     *
     * @return array<int, AuthenticationResult>
     *
     * @phpstan-return list<AuthenticationResult>
     */
    public function findSuccessfulResults(): array
    {
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.success = true')
            ->andWhere('r.valid = true')
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationResult> $result */
        return $result;
    }

    /**
     * 查询失败的认证结果
     *
     * @return array<int, AuthenticationResult>
     *
     * @phpstan-return list<AuthenticationResult>
     */
    public function findFailedResults(): array
    {
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.success = false')
            ->andWhere('r.valid = true')
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationResult> $result */
        return $result;
    }

    /**
     * 根据置信度范围查询结果
     *
     * @return array<int, AuthenticationResult>
     *
     * @phpstan-return list<AuthenticationResult>
     */
    public function findByConfidenceRange(float $minConfidence, float $maxConfidence = 1.0): array
    {
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.confidence >= :minConfidence')
            ->andWhere('r.confidence <= :maxConfidence')
            ->andWhere('r.valid = true')
            ->setParameter('minConfidence', $minConfidence)
            ->setParameter('maxConfidence', $maxConfidence)
            ->orderBy('r.confidence', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationResult> $result */
        return $result;
    }

    /**
     * 查询处理时间超过指定阈值的结果
     *
     * @return array<int, AuthenticationResult>
     *
     * @phpstan-return list<AuthenticationResult>
     */
    public function findSlowResults(int $thresholdMs = 5000): array
    {
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.processingTime > :threshold')
            ->andWhere('r.valid = true')
            ->setParameter('threshold', $thresholdMs)
            ->orderBy('r.processingTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationResult> $result */
        return $result;
    }

    /**
     * 分页查询认证结果
     *
     * @param array<string, mixed> $criteria
     *
     * @return array<int, AuthenticationResult>
     *
     * @phpstan-return list<AuthenticationResult>
     */
    public function findWithPagination(int $page, int $size, array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('r');

        if (isset($criteria['authentication'])) {
            $qb->andWhere('r.authentication = :authentication')
                ->setParameter('authentication', $criteria['authentication'])
            ;
        }

        if (isset($criteria['provider'])) {
            $qb->andWhere('r.provider = :provider')
                ->setParameter('provider', $criteria['provider'])
            ;
        }

        if (isset($criteria['isSuccess'])) {
            $qb->andWhere('r.success = :success')
                ->setParameter('success', $criteria['isSuccess'])
            ;
        }

        if (isset($criteria['minConfidence'])) {
            $qb->andWhere('r.confidence >= :minConfidence')
                ->setParameter('minConfidence', $criteria['minConfidence'])
            ;
        }

        if (isset($criteria['createTimeStart'])) {
            $qb->andWhere('r.createTime >= :createTimeStart')
                ->setParameter('createTimeStart', $criteria['createTimeStart'])
            ;
        }

        if (isset($criteria['createTimeEnd'])) {
            $qb->andWhere('r.createTime <= :createTimeEnd')
                ->setParameter('createTimeEnd', $criteria['createTimeEnd'])
            ;
        }

        $offset = ($page - 1) * $size;

        $result = $qb->orderBy('r.createTime', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationResult> $result */
        return $result;
    }

    /**
     * 统计成功率
     */
    public function getSuccessRate(?AuthenticationProvider $provider = null): float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) as total, SUM(CASE WHEN r.success = true THEN 1 ELSE 0 END) as success')
            ->andWhere('r.valid = true')
        ;

        if (null !== $provider) {
            $qb->andWhere('r.provider = :provider')
                ->setParameter('provider', $provider)
            ;
        }

        /** @var array{total: int, success: int} $result */
        $result = $qb->getQuery()->getSingleResult();

        if (0 === $result['total']) {
            return 0.0;
        }

        return round(($result['success'] / $result['total']) * 100, 2);
    }

    /**
     * 根据用户查询认证结果
     *
     * @return array<int, AuthenticationResult>
     *
     * @phpstan-return list<AuthenticationResult>
     */
    public function findByUser(UserInterface $user): array
    {
        $result = $this->createQueryBuilder('r')
            ->join('r.authentication', 'a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationResult> $result */
        return $result;
    }

    /**
     * 根据用户和认证方式查询认证结果
     *
     * @return array<int, AuthenticationResult>
     *
     * @phpstan-return list<AuthenticationResult>
     */
    public function findByUserAndMethod(UserInterface $user, AuthenticationMethod $method): array
    {
        $result = $this->createQueryBuilder('r')
            ->join('r.authentication', 'a')
            ->andWhere('a.user = :user')
            ->andWhere('a.method = :method')
            ->setParameter('user', $user)
            ->setParameter('method', $method)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationResult> $result */
        return $result;
    }

    /**
     * 查询用户最新的认证结果
     *
     * @phpstan-return AuthenticationResult|null
     */
    public function findLatestByUser(UserInterface $user): ?AuthenticationResult
    {
        $result = $this->createQueryBuilder('r')
            ->join('r.authentication', 'a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof AuthenticationResult ? $result : null;
    }

    /**
     * 根据认证状态统计结果
     *
     * @return array<string, int>
     */
    public function getStatisticsByStatus(): array
    {
        /** @var array<array{success: bool, count: int|numeric-string}> $result */
        $result = $this->createQueryBuilder('r')
            ->select('r.success, COUNT(r.id) as count')
            ->andWhere('r.valid = true')
            ->groupBy('r.success')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $key = (bool) $row['success'] ? 'success' : 'failed';
            $statistics[$key] = (int) $row['count'];
        }

        return $statistics;
    }

    /**
     * 根据认证方式统计结果
     *
     * @return array<string, int>
     */
    public function getStatisticsByMethod(): array
    {
        /** @var array<array{method: AuthenticationMethod, count: int|numeric-string}> $result */
        $result = $this->createQueryBuilder('r')
            ->select('a.method, COUNT(r.id) as count')
            ->join('r.authentication', 'a')
            ->andWhere('r.valid = true')
            ->groupBy('a.method')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['method']->value] = (int) $row['count'];
        }

        return $statistics;
    }

    public function save(AuthenticationResult $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AuthenticationResult $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
