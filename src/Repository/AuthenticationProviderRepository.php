<?php

namespace Tourze\RealNameAuthenticationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;

/**
 * 认证提供商Repository实现
 * 
 * @method AuthenticationProvider|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthenticationProvider|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthenticationProvider[] findAll()
 * @method AuthenticationProvider[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthenticationProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthenticationProvider::class);
    }

    /**
     * 查询所有活跃的提供商
     */
    public function findActiveProviders(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据认证方式查询提供商
     */
    public function findByMethod(AuthenticationMethod $method): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->andWhere('JSON_CONTAINS(p.supportedMethods, :method) = 1')
            ->setParameter('method', '"' . $method->value . '"')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据类型和认证方式查询提供商
     */
    public function findByTypeAndMethod(ProviderType $type, AuthenticationMethod $method): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.type = :type')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->andWhere('JSON_CONTAINS(p.supportedMethods, :method) = 1')
            ->setParameter('type', $type)
            ->setParameter('method', '"' . $method->value . '"')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 按优先级排序查询提供商
     */
    public function findByPriority(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据代码查询提供商
     */
    public function findByCode(string $code): ?AuthenticationProvider
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->andWhere('p.valid = true')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 查询支持指定认证方式的最佳提供商
     */
    public function findBestProviderForMethod(AuthenticationMethod $method): ?AuthenticationProvider
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->andWhere('JSON_CONTAINS(p.supportedMethods, :method) = 1')
            ->setParameter('method', '"' . $method->value . '"')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
