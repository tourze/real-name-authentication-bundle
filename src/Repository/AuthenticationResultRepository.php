<?php

namespace Tourze\RealNameAuthenticationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;

/**
 * 认证结果Repository实现
 *
 * @method AuthenticationResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthenticationResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthenticationResult[] findAll()
 * @method AuthenticationResult[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthenticationResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthenticationResult::class);
    }

    /**
     * 根据认证记录查询结果
     */
    public function findByAuthentication(RealNameAuthentication $authentication): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.authentication = :authentication')
            ->setParameter('authentication', $authentication)
            ->orderBy('r.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据提供商查询结果
     */
    public function findByProvider(AuthenticationProvider $provider): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.provider = :provider')
            ->setParameter('provider', $provider)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据请求ID查询结果
     */
    public function findByRequestId(string $requestId): ?AuthenticationResult
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.requestId = :requestId')
            ->setParameter('requestId', $requestId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 查询成功的认证结果
     */
    public function findSuccessfulResults(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.success = true')
            ->andWhere('r.valid = true')
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查询失败的认证结果
     */
    public function findFailedResults(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.success = false')
            ->andWhere('r.valid = true')
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据置信度范围查询结果
     */
    public function findByConfidenceRange(float $minConfidence, float $maxConfidence = 1.0): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.confidence >= :minConfidence')
            ->andWhere('r.confidence <= :maxConfidence')
            ->andWhere('r.valid = true')
            ->setParameter('minConfidence', $minConfidence)
            ->setParameter('maxConfidence', $maxConfidence)
            ->orderBy('r.confidence', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查询处理时间超过指定阈值的结果
     */
    public function findSlowResults(int $thresholdMs = 5000): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.processingTime > :threshold')
            ->andWhere('r.valid = true')
            ->setParameter('threshold', $thresholdMs)
            ->orderBy('r.processingTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 分页查询认证结果
     */
    public function findWithPagination(int $page, int $size, array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('r');

        if (isset($criteria['authentication'])) {
            $qb->andWhere('r.authentication = :authentication')
               ->setParameter('authentication', $criteria['authentication']);
        }

        if (isset($criteria['provider'])) {
            $qb->andWhere('r.provider = :provider')
               ->setParameter('provider', $criteria['provider']);
        }

        if (isset($criteria['isSuccess'])) {
            $qb->andWhere('r.success = :success')
               ->setParameter('success', $criteria['isSuccess']);
        }

        if (isset($criteria['minConfidence'])) {
            $qb->andWhere('r.confidence >= :minConfidence')
               ->setParameter('minConfidence', $criteria['minConfidence']);
        }

        if (isset($criteria['createTimeStart'])) {
            $qb->andWhere('r.createTime >= :createTimeStart')
               ->setParameter('createTimeStart', $criteria['createTimeStart']);
        }

        if (isset($criteria['createTimeEnd'])) {
            $qb->andWhere('r.createTime <= :createTimeEnd')
               ->setParameter('createTimeEnd', $criteria['createTimeEnd']);
        }

        $offset = ($page - 1) * $size;

        return $qb->orderBy('r.createTime', 'DESC')
                  ->setFirstResult($offset)
                  ->setMaxResults($size)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * 统计成功率
     */
    public function getSuccessRate(?AuthenticationProvider $provider = null): float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) as total, SUM(CASE WHEN r.success = true THEN 1 ELSE 0 END) as success')
            ->andWhere('r.valid = true');

        if ($provider) {
            $qb->andWhere('r.provider = :provider')
               ->setParameter('provider', $provider);
        }

        $result = $qb->getQuery()->getSingleResult();

        if ($result['total'] == 0) {
            return 0.0;
        }

        return round(($result['success'] / $result['total']) * 100, 2);
    }
}
